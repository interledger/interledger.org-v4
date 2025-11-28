package backupmanager

import (
	"context"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"cloud.google.com/go/storage"
	"google.golang.org/api/iterator"
	sqladmin "google.golang.org/api/sqladmin/v1beta4"
)

type BackendGcp struct {
	EnvironmentConfigs EnvironmentConfigs
}

func NewBackendGcp(configs EnvironmentConfigs) *BackendGcp {
	return &BackendGcp{
		EnvironmentConfigs: configs,
	}
}

// DownloadFolder downloads all files from a GCS bucket path to a local destination
// source should be in format: gs://bucket-name/path/
func (b *BackendGcp) DownloadFolder(source string, destination string) error {
	Info("Starting download from %s to %s", source, destination)
	ctx := context.Background()
	client, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	// Parse the GCS path
	if !strings.HasPrefix(source, "gs://") {
		return fmt.Errorf("source must start with gs://")
	}
	source = strings.TrimPrefix(source, "gs://")
	parts := strings.SplitN(source, "/", 2)
	if len(parts) < 1 {
		return fmt.Errorf("invalid GCS path: %s", source)
	}

	bucketName := parts[0]
	prefix := ""
	if len(parts) == 2 {
		prefix = parts[1]
	}

	bucket := client.Bucket(bucketName)

	// List all objects with the given prefix
	Info("Listing objects in bucket %s with prefix %s", bucketName, prefix)
	query := &storage.Query{Prefix: prefix}
	it := bucket.Objects(ctx, query)

	// Collect all files to download
	type fileToDownload struct {
		objectName string
		localPath  string
	}
	var files []fileToDownload

	for {
		attrs, err := it.Next()
		if err == iterator.Done {
			break
		}
		if err != nil {
			Error("Error iterating objects: %v", err)
			return fmt.Errorf("error iterating objects: %v", err)
		}

		// Skip directories
		if strings.HasSuffix(attrs.Name, "/") {
			continue
		}

		// Determine the local file path
		relativePath := strings.TrimPrefix(attrs.Name, prefix)
		relativePath = strings.TrimPrefix(relativePath, "/")
		localPath := filepath.Join(destination, relativePath)

		files = append(files, fileToDownload{
			objectName: attrs.Name,
			localPath:  localPath,
		})
	}

	Info("Found %d files to download, starting parallel download", len(files))

	// Download files in parallel with a worker pool
	const maxWorkers = 20
	workers := maxWorkers
	if len(files) < workers {
		workers = len(files)
	}

	type result struct {
		err error
	}

	results := make(chan result, len(files))
	semaphore := make(chan struct{}, workers)

	for _, f := range files {
		semaphore <- struct{}{} // Acquire worker slot
		go func(objectName, localPath string) {
			defer func() { <-semaphore }() // Release worker slot

			// Create directory structure
			if err := os.MkdirAll(filepath.Dir(localPath), 0755); err != nil {
				results <- result{err: fmt.Errorf("failed to create directory: %v", err)}
				return
			}

			// Download the object
			Info("Downloading %s to %s", objectName, localPath)
			obj := bucket.Object(objectName)
			reader, err := obj.NewReader(ctx)
			if err != nil {
				Error("Failed to read object %s: %v", objectName, err)
				results <- result{err: fmt.Errorf("failed to read object %s: %v", objectName, err)}
				return
			}
			defer reader.Close()

			file, err := os.Create(localPath)
			if err != nil {
				Error("Failed to create local file %s: %v", localPath, err)
				results <- result{err: fmt.Errorf("failed to create local file %s: %v", localPath, err)}
				return
			}
			defer file.Close()

			if _, err := io.Copy(file, reader); err != nil {
				Error("Failed to download file %s: %v", objectName, err)
				results <- result{err: fmt.Errorf("failed to download file %s: %v", objectName, err)}
				return
			}

			results <- result{err: nil}
		}(f.objectName, f.localPath)
	}

	// Wait for all downloads to complete and check for errors
	fileCount := 0
	for i := 0; i < len(files); i++ {
		res := <-results
		if res.err != nil {
			return res.err
		}
		fileCount++
	}

	Info("Successfully downloaded %d files from %s", fileCount, source)
	return nil
}

// ExportDatabase uses Cloud SQL's native export to export a MySQL database to GCS,
// then downloads it to the local dumpPath
func (b *BackendGcp) ExportDatabase(databaseName string, dumpPath string) error {
	ctx := context.Background()

	// Get the environment config based on database name
	// Try to find a matching environment by checking if the database name contains the environment key
	var config *EnvironmentConfig
	for envName, envConfig := range b.EnvironmentConfigs {
		if strings.Contains(strings.ToLower(databaseName), strings.ToLower(envName)) {
			config = envConfig
			break
		}
	}
	if config == nil {
		return fmt.Errorf("unable to determine environment for database: %s", databaseName)
	}

	// Create Cloud SQL Admin service
	sqlAdminService, err := sqladmin.NewService(ctx)
	if err != nil {
		Error("Failed to create Cloud SQL Admin service: %v", err)
		return fmt.Errorf("failed to create Cloud SQL Admin service: %v", err)
	}

	// Generate a unique filename for the export in GCS
	timestamp := time.Now().Format("20060102-150405")
	exportFileName := fmt.Sprintf("db-exports/%s-export-%s.sql.gz", databaseName, timestamp)
	exportURI := fmt.Sprintf("gs://%s/%s", config.BackupBucket, exportFileName)
	Info("Exporting database to %s", exportURI)

	// Create the export request
	exportRequest := &sqladmin.InstancesExportRequest{
		ExportContext: &sqladmin.ExportContext{
			FileType:  "SQL",
			Uri:       exportURI,
			Databases: []string{config.DBName},
		},
	}

	// Start the export operation
	Info("Starting Cloud SQL export operation for instance %s", config.CloudSQLInstance)
	op, err := sqlAdminService.Instances.Export(config.GCPProjectID, config.CloudSQLInstance, exportRequest).Context(ctx).Do()
	if err != nil {
		Error("Failed to start database export: %v", err)
		return fmt.Errorf("failed to start database export: %v", err)
	}

	// Wait for the export operation to complete
	Info("Waiting for export operation to complete...")
	for {
		// Check operation status
		opStatus, err := sqlAdminService.Operations.Get(config.GCPProjectID, op.Name).Context(ctx).Do()
		if err != nil {
			Error("Failed to get operation status: %v", err)
			return fmt.Errorf("failed to get operation status: %v", err)
		}

		if opStatus.Status == "DONE" {
			if opStatus.Error != nil {
				var errMessages []string
				for _, e := range opStatus.Error.Errors {
					errMessages = append(errMessages, fmt.Sprintf("Code: %s, Message: %s", e.Code, e.Message))
				}
				errMsg := strings.Join(errMessages, "; ")
				Error("Export operation failed: %s", errMsg)
				return fmt.Errorf("export operation failed: %s", errMsg)
			}
			Info("Export operation completed successfully")
			break
		}

		// Wait before checking again
		time.Sleep(5 * time.Second)
	}

	// Download the exported file from GCS to local path
	Info("Downloading exported database from GCS to %s", dumpPath)
	storageClient, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer storageClient.Close()

	bucket := storageClient.Bucket(config.BackupBucket)
	obj := bucket.Object(exportFileName)
	reader, err := obj.NewReader(ctx)
	if err != nil {
		Error("Failed to read exported file from GCS: %v", err)
		return fmt.Errorf("failed to read exported file from GCS: %v", err)
	}
	defer reader.Close()

	// Create local file
	file, err := os.Create(dumpPath)
	if err != nil {
		Error("Failed to create local dump file: %v", err)
		return fmt.Errorf("failed to create local dump file: %v", err)
	}
	defer file.Close()

	// Copy from GCS to local file
	if _, err := io.Copy(file, reader); err != nil {
		Error("Failed to download exported database: %v", err)
		return fmt.Errorf("failed to download exported database: %v", err)
	}

	Info("Successfully exported database %s to %s", databaseName, dumpPath)
	return nil
}

// UploadArchive uploads a file to GCS
// destination should be in format: gs://bucket-name/path/filename.tar.gz
func (b *BackendGcp) UploadArchive(archivePath string, destination string) error {
	Info("Uploading archive from %s to %s", archivePath, destination)
	ctx := context.Background()
	client, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	// Parse the GCS path
	if !strings.HasPrefix(destination, "gs://") {
		return fmt.Errorf("destination must start with gs://")
	}
	destination = strings.TrimPrefix(destination, "gs://")
	parts := strings.SplitN(destination, "/", 2)
	if len(parts) != 2 {
		return fmt.Errorf("invalid GCS path: %s", destination)
	}

	bucketName := parts[0]
	objectName := parts[1]

	// Open the local file
	file, err := os.Open(archivePath)
	if err != nil {
		return fmt.Errorf("failed to open archive file: %v", err)
	}
	defer file.Close()

	// Create the GCS object writer
	bucket := client.Bucket(bucketName)
	obj := bucket.Object(objectName)
	writer := obj.NewWriter(ctx)

	// Copy the file to GCS
	if _, err := io.Copy(writer, file); err != nil {
		writer.Close()
		Error("Failed to upload archive: %v", err)
		return fmt.Errorf("failed to upload archive: %v", err)
	}

	// Close the writer to commit the upload
	if err := writer.Close(); err != nil {
		Error("Failed to close writer: %v", err)
		return fmt.Errorf("failed to close writer: %v", err)
	}

	Info("Successfully uploaded archive to %s", destination)
	return nil
}

func (b *BackendGcp) DownloadArchive(archivePath string, destinationPath string) error {
	Info("Downloading archive %s", archivePath)

	ctx := context.Background()
	client, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	// Parse the GCS path
	if !strings.HasPrefix(archivePath, "gs://") {
		return fmt.Errorf("archivePath must start with gs://")
	}
	archivePathClean := strings.TrimPrefix(archivePath, "gs://")
	parts := strings.SplitN(archivePathClean, "/", 2)
	if len(parts) != 2 {
		return fmt.Errorf("invalid GCS path: %s", archivePath)
	}

	bucketName := parts[0]
	objectName := parts[1]

	// Get bucket and object
	bucket := client.Bucket(bucketName)
	obj := bucket.Object(objectName)

	// Create the destination file
	destFile, err := os.Create(destinationPath)
	if err != nil {
		Error("Failed to create destination file: %v", err)
		return fmt.Errorf("failed to create destination file: %v", err)
	}
	defer destFile.Close()

	// Download the object
	reader, err := obj.NewReader(ctx)
	if err != nil {
		Error("Failed to create object reader: %v", err)
		return fmt.Errorf("failed to create object reader: %v", err)
	}
	defer reader.Close()

	// Copy to destination
	bytesWritten, err := io.Copy(destFile, reader)
	if err != nil {
		Error("Failed to download archive: %v", err)
		return fmt.Errorf("failed to download archive: %v", err)
	}

	Info("Successfully downloaded archive (%d bytes) to %s", bytesWritten, destinationPath)
	return nil
}

func (b *BackendGcp) ImportDatabase(databaseName string, sqlFilePath string) error {
	Info("Importing database %s from %s", databaseName, sqlFilePath)

	ctx := context.Background()

	// Get the environment config based on database name
	var config *EnvironmentConfig
	for envName, envConfig := range b.EnvironmentConfigs {
		if strings.Contains(strings.ToLower(databaseName), strings.ToLower(envName)) {
			config = envConfig
			break
		}
	}
	if config == nil {
		return fmt.Errorf("unable to determine environment for database: %s", databaseName)
	}

	// Create storage client
	client, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	// Upload SQL file to temporary location in backup bucket
	sqlFileName := filepath.Base(sqlFilePath)
	tempGcsPath := fmt.Sprintf("temp-imports/%s", sqlFileName)

	Info("Uploading SQL file to temporary GCS location: gs://%s/%s", config.BackupBucket, tempGcsPath)

	bucket := client.Bucket(config.BackupBucket)
	obj := bucket.Object(tempGcsPath)
	writer := obj.NewWriter(ctx)

	sqlFile, err := os.Open(sqlFilePath)
	if err != nil {
		Error("Failed to open SQL file: %v", err)
		return fmt.Errorf("failed to open SQL file: %v", err)
	}

	if _, err := io.Copy(writer, sqlFile); err != nil {
		sqlFile.Close()
		writer.Close()
		Error("Failed to upload SQL file to GCS: %v", err)
		return fmt.Errorf("failed to upload SQL file to GCS: %v", err)
	}
	sqlFile.Close()

	if err := writer.Close(); err != nil {
		Error("Failed to finalize SQL file upload: %v", err)
		return fmt.Errorf("failed to finalize SQL file upload: %v", err)
	}

	Info("SQL file uploaded successfully, initiating database import")

	// Create Cloud SQL Admin service
	sqlAdminService, err := sqladmin.NewService(ctx)
	if err != nil {
		Error("Failed to create Cloud SQL Admin service: %v", err)
		return fmt.Errorf("failed to create Cloud SQL Admin service: %v", err)
	}

	// Create import request
	importRequest := &sqladmin.InstancesImportRequest{
		ImportContext: &sqladmin.ImportContext{
			Uri:      fmt.Sprintf("gs://%s/%s", config.BackupBucket, tempGcsPath),
			Database: config.DBName,
			FileType: "SQL",
		},
	}

	// Start import operation
	op, err := sqlAdminService.Instances.Import(config.GCPProjectID, config.CloudSQLInstance, importRequest).Context(ctx).Do()
	if err != nil {
		Error("Failed to start database import: %v", err)
		return fmt.Errorf("failed to start database import: %v", err)
	}

	Info("Database import operation started: %s", op.Name)

	// Poll for completion
	for {
		opStatus, err := sqlAdminService.Operations.Get(config.GCPProjectID, op.Name).Context(ctx).Do()
		if err != nil {
			Error("Failed to check import operation status: %v", err)
			return fmt.Errorf("failed to check import operation status: %v", err)
		}

		if opStatus.Status == "DONE" {
			if opStatus.Error != nil {
				var errMsgs []string
				for _, e := range opStatus.Error.Errors {
					errMsgs = append(errMsgs, fmt.Sprintf("%s: %s", e.Code, e.Message))
				}
				Error("Database import failed: %s", strings.Join(errMsgs, "; "))
				return fmt.Errorf("database import failed: %s", strings.Join(errMsgs, "; "))
			}
			Info("Database import completed successfully")
			break
		}

		Info("Import operation in progress (status: %s), waiting...", opStatus.Status)
		time.Sleep(5 * time.Second)
	}

	// Clean up temporary SQL file from GCS
	Info("Cleaning up temporary SQL file from GCS")
	if err := obj.Delete(ctx); err != nil {
		Warn("Failed to delete temporary SQL file from GCS: %v", err)
		// Don't fail the operation if cleanup fails
	}

	return nil
}

func (b *BackendGcp) UploadFolder(sourcePath string, gcsDestination string) error {
	Info("Uploading folder from %s to %s", sourcePath, gcsDestination)

	ctx := context.Background()
	client, err := storage.NewClient(ctx)
	if err != nil {
		Error("Failed to create storage client: %v", err)
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	// Parse the GCS path
	if !strings.HasPrefix(gcsDestination, "gs://") {
		return fmt.Errorf("gcsDestination must start with gs://")
	}
	gcsDestinationClean := strings.TrimPrefix(gcsDestination, "gs://")
	parts := strings.SplitN(gcsDestinationClean, "/", 2)
	if len(parts) < 1 {
		return fmt.Errorf("invalid GCS path: %s", gcsDestination)
	}

	bucketName := parts[0]
	prefix := ""
	if len(parts) == 2 {
		prefix = parts[1]
	}

	bucket := client.Bucket(bucketName) // Count total files first
	totalFiles := 0
	filepath.Walk(sourcePath, func(path string, info os.FileInfo, err error) error {
		if err == nil && !info.IsDir() {
			totalFiles++
		}
		return nil
	})

	Info("Found %d files to upload", totalFiles)

	// Use parallel uploads
	const maxWorkers = 10
	sem := make(chan struct{}, maxWorkers)
	errChan := make(chan error, totalFiles)
	var wg sync.WaitGroup

	uploadCount := 0

	err = filepath.Walk(sourcePath, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}

		if info.IsDir() {
			return nil
		}

		wg.Add(1)
		sem <- struct{}{} // Acquire semaphore

		go func(filePath string) {
			defer wg.Done()
			defer func() { <-sem }() // Release semaphore

			// Get relative path
			relPath, err := filepath.Rel(sourcePath, filePath)
			if err != nil {
				errChan <- fmt.Errorf("failed to get relative path for %s: %v", filePath, err)
				return
			}

			// Convert to GCS path format (use forward slashes)
			gcsPath := filepath.ToSlash(relPath)
			if prefix != "" {
				gcsPath = prefix + "/" + gcsPath
			}

			// Open source file
			file, err := os.Open(filePath)
			if err != nil {
				errChan <- fmt.Errorf("failed to open file %s: %v", filePath, err)
				return
			}
			defer file.Close()

			// Upload to GCS
			obj := bucket.Object(gcsPath)
			writer := obj.NewWriter(ctx)

			if _, err := io.Copy(writer, file); err != nil {
				writer.Close()
				errChan <- fmt.Errorf("failed to upload file %s: %v", filePath, err)
				return
			}

			if err := writer.Close(); err != nil {
				errChan <- fmt.Errorf("failed to finalize upload for %s: %v", filePath, err)
				return
			}

			Info("Uploaded: %s -> gs://%s/%s", relPath, bucketName, gcsPath)
		}(path)

		uploadCount++
		return nil
	})

	if err != nil {
		Error("Error walking directory: %v", err)
		return fmt.Errorf("error walking directory: %v", err)
	}

	// Wait for all uploads to complete
	wg.Wait()
	close(errChan)

	// Check for errors
	var uploadErrors []string
	for err := range errChan {
		uploadErrors = append(uploadErrors, err.Error())
	}

	if len(uploadErrors) > 0 {
		Error("Failed to upload %d files", len(uploadErrors))
		return fmt.Errorf("failed to upload files: %s", strings.Join(uploadErrors, "; "))
	}

	Info("Successfully uploaded %d files to %s", totalFiles, gcsDestination)
	return nil
}
