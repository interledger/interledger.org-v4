package backupmanager

import (
	"compress/gzip"
	"context"
	"fmt"
	"io"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"cloud.google.com/go/storage"
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

// DownloadFolder downloads all files from the VM via rsync over SSH to a local destination
func (b *BackendGcp) DownloadFolder(envConfig *EnvironmentConfig, destination string) error {
	Info("Starting rsync download from %s@%s:%s to %s", envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath, destination)

	// Build rsync command with SSH options
	// Use -avz for archive mode, verbose, and compression
	// Trailing slash on source ensures we copy contents, not the directory itself
	source := fmt.Sprintf("%s@%s:%s/", envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath)

	cmd := exec.Command("rsync", "-avz", "-e", "ssh", source, destination)

	// Capture combined output for logging
	output, err := cmd.CombinedOutput()
	if err != nil {
		Error("Rsync failed: %v\nOutput: %s", err, string(output))
		return fmt.Errorf("rsync failed: %v\nOutput: %s", err, string(output))
	}

	Info("Rsync output:\n%s", string(output))
	Info("Successfully downloaded files via rsync from %s@%s:%s", envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath)
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

	// Read the SQL file - it's actually gzipped from Cloud SQL export
	// We need to decompress it first to read and modify the content
	Info("Reading and decompressing SQL file")
	sqlFile, err := os.Open(sqlFilePath)
	if err != nil {
		Error("Failed to open SQL file: %v", err)
		return fmt.Errorf("failed to open SQL file: %v", err)
	}
	defer sqlFile.Close()

	// Check if file is gzipped by reading magic bytes
	gzipReader, err := gzip.NewReader(sqlFile)
	var sqlContent []byte
	if err == nil {
		// File is gzipped, decompress it
		Info("SQL file is gzipped, decompressing...")
		sqlContent, err = io.ReadAll(gzipReader)
		gzipReader.Close()
		if err != nil {
			Error("Failed to decompress SQL file: %v", err)
			return fmt.Errorf("failed to decompress SQL file: %v", err)
		}
	} else {
		// File is not gzipped, read it directly
		Info("SQL file is not compressed, reading directly...")
		sqlFile.Seek(0, 0) // Reset file pointer
		sqlContent, err = io.ReadAll(sqlFile)
		if err != nil {
			Error("Failed to read SQL file: %v", err)
			return fmt.Errorf("failed to read SQL file: %v", err)
		}
	}

	// Find the source database name by checking all other environments
	var sourceDBName string
	for _, envConfig := range b.EnvironmentConfigs {
		if envConfig.DBName != config.DBName {
			// Check if this database name appears in the SQL content
			if strings.Contains(string(sqlContent), envConfig.DBName) {
				sourceDBName = envConfig.DBName
				break
			}
		}
	}

	if sourceDBName != "" && sourceDBName != config.DBName {
		Info("Replacing database name '%s' with '%s' in SQL dump", sourceDBName, config.DBName)
		modifiedContent := strings.ReplaceAll(string(sqlContent), sourceDBName, config.DBName)

		// Write modified content to a temporary file
		modifiedSQLPath := sqlFilePath + ".modified"
		if err := os.WriteFile(modifiedSQLPath, []byte(modifiedContent), 0644); err != nil {
			Error("Failed to write modified SQL file: %v", err)
			return fmt.Errorf("failed to write modified SQL file: %v", err)
		}
		defer os.Remove(modifiedSQLPath)
		sqlFilePath = modifiedSQLPath
	}

	// Upload SQL file to temporary location in backup bucket
	sqlFileName := filepath.Base(sqlFilePath)
	tempGcsPath := fmt.Sprintf("temp-imports/%s", sqlFileName)

	Info("Uploading SQL file to temporary GCS location: gs://%s/%s", config.BackupBucket, tempGcsPath)

	bucket := client.Bucket(config.BackupBucket)
	obj := bucket.Object(tempGcsPath)
	writer := obj.NewWriter(ctx)

	sqlFileForUpload, err := os.Open(sqlFilePath)
	if err != nil {
		Error("Failed to open SQL file: %v", err)
		return fmt.Errorf("failed to open SQL file: %v", err)
	}

	if _, err := io.Copy(writer, sqlFileForUpload); err != nil {
		sqlFileForUpload.Close()
		writer.Close()
		Error("Failed to upload SQL file to GCS: %v", err)
		return fmt.Errorf("failed to upload SQL file to GCS: %v", err)
	}
	sqlFileForUpload.Close()

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
	// Info("Cleaning up temporary SQL file from GCS")
	// if err := obj.Delete(ctx); err != nil {
	// 	Warn("Failed to delete temporary SQL file from GCS: %v", err)
	// 	// Don't fail the operation if cleanup fails
	// }

	return nil
}

func (b *BackendGcp) UploadFolder(sourcePath string, envConfig *EnvironmentConfig) error {
	Info("Uploading folder from %s to %s@%s:%s via rsync", sourcePath, envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath)

	// Build rsync command with SSH options
	// Use -rlptz instead of -a to avoid setting directory timestamps and preserve permissions
	// Delete files on destination that don't exist in source
	destination := fmt.Sprintf("%s@%s:%s/", envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath)

	// Add trailing slash to source to copy contents, not the directory itself
	source := sourcePath
	if !strings.HasSuffix(source, "/") {
		source = source + "/"
	}

	// Use -rlpz: recursive, copy symlinks, preserve permissions, compress
	// Use --no-times to skip setting timestamps entirely (avoids permission errors)
	cmd := exec.Command("rsync", "-rlpz", "--delete", "--no-times", "--no-perms", "--chmod=ugo=rwX", "-e", "ssh", source, destination)

	// Capture combined output for logging
	output, err := cmd.CombinedOutput()
	if err != nil {
		Error("Rsync upload failed: %v\nOutput: %s", err, string(output))
		return fmt.Errorf("rsync upload failed: %v\nOutput: %s", err, string(output))
	}

	Info("Rsync output:\n%s", string(output))
	Info("Successfully uploaded files via rsync to %s@%s:%s", envConfig.TargetUser, envConfig.TargetHost, envConfig.TargetPath)
	return nil
}
