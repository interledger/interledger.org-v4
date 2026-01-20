package backupmanager

import (
	"archive/tar"
	"compress/gzip"
	"fmt"
	"io"
	"os"
	"path/filepath"
)

type BackupBackend interface {
	DownloadFolder(envConfig *EnvironmentConfig, destination string) error
	ExportDatabase(databaseName string, dumpPath string) error
	UploadArchive(archivePath string, destination string) error
	DownloadArchive(archivePath string, destination string) error
	ImportDatabase(databaseName string, dumpPath string) error
	UploadFolder(source string, envConfig *EnvironmentConfig) error
}

type BackupEngineCloud struct {
	backupBackend BackupBackend
	configs       EnvironmentConfigs
}

func NewBackupEngineGcp(configs EnvironmentConfigs) *BackupEngineCloud {
	backend := NewBackendGcp(configs)
	return &BackupEngineCloud{
		backupBackend: backend,
		configs:       configs,
	}
}

// Will trigger a backup for the given environment and use the runId for tracking
// purposes. A backup involves
//  1. Sql dump environment specific database
//  2. Copy files from environment specific storage bucket
//  3. Create backup archive containig the sql dump and the copied files. Store the
//     archive in a central backup bucket with a name containing the environment and runId
func (e *BackupEngineCloud) PerformBackup(environment string, runId string) error {
	Info("Starting backup for environment '%s' with run ID '%s'", environment, runId)
	// Get environment config
	envConfig, ok := e.configs[environment]
	if !ok {
		Error("Unknown environment: %s", environment)
		return fmt.Errorf("unknown environment: %s", environment)
	}

	tmpFolder := "/tmp/backup_" + runId
	filesFolder := tmpFolder + "/files"
	Info("Creating temporary folders at %s", tmpFolder)
	err := os.MkdirAll(filesFolder, 0755)
	if err != nil {
		Error("Failed to create files folder: %v", err)
		return fmt.Errorf("failed to create files folder: %v", err)
	}

	// Export database dump
	dumpPath := tmpFolder + "/db_dump.sql"
	databaseName := envConfig.DBName
	Info("Step 1/4: Exporting database %s", databaseName)
	err = e.backupBackend.ExportDatabase(databaseName, dumpPath)
	if err != nil {
		Error("ExportDatabase failed: %v", err)
		return fmt.Errorf("ExportDatabase failed: %v", err)
	}

	// Download files from VM via rsync
	Info("Step 2/4: Downloading files from VM via rsync")
	err = e.backupBackend.DownloadFolder(envConfig, filesFolder)
	if err != nil {
		Error("DownloadFolder failed: %v", err)
		return fmt.Errorf("DownloadFolder failed: %v", err)
	}

	// Create backup archive
	archivePath := tmpFolder + "/backup_archive.tar.gz"
	Info("Step 3/4: Creating backup archive")
	err = CreateBackupArchive(archivePath, dumpPath, filesFolder)
	if err != nil {
		Error("CreateBackupArchive failed: %v", err)
		return fmt.Errorf("CreateBackupArchive failed: %v", err)
	}

	// Upload archive to central backup bucket
	destinationStoragePath := fmt.Sprintf("gs://%s/backups/%s/backup_%s.tar.gz", envConfig.BackupBucket, environment, runId)
	Info("Step 4/4: Uploading archive to backup bucket")
	err = e.backupBackend.UploadArchive(archivePath, destinationStoragePath)
	if err != nil {
		Error("UploadArchive failed: %v", err)
		return fmt.Errorf("UploadArchive failed: %v", err)
	}

	// Clean up temporary files
	Info("Cleaning up temporary files at %s", tmpFolder)
	err = os.RemoveAll(tmpFolder)
	if err != nil {
		Error("Failed to clean up temporary files: %v", err)
		// Not a fatal error, so we don't return
	}

	Info("Backup completed successfully for environment '%s' with run ID '%s'", environment, runId)
	return nil
}

// Will trigger a restore for the given environment and runId to the destinationEnvironment. A restore involves
//  1. Retrieving the backup archive from the central backup bucket using the environment and runId
//  2. Extracting the sql dump and copied files from the archive
//  3. Restoring the sql dump to the destinationEnvironment specific database
//  4. Copying the extracted files to the destinationEnvironment specific storage bucket
func (e *BackupEngineCloud) PerformRestore(environment string, runId string, destinationEnvironment string) error {
	Info("Starting restore from environment '%s' (run ID '%s') to '%s'", environment, runId, destinationEnvironment)

	// Get source environment config
	srcConfig, ok := e.configs[environment]
	if !ok {
		Error("Unknown source environment: %s", environment)
		return fmt.Errorf("unknown source environment: %s", environment)
	}

	// Get destination environment config
	destConfig, ok := e.configs[destinationEnvironment]
	if !ok {
		Error("Unknown destination environment: %s", destinationEnvironment)
		return fmt.Errorf("unknown destination environment: %s", destinationEnvironment)
	}

	tmpFolder := "/tmp/restore_" + runId
	filesFolder := tmpFolder + "/files"
	Info("Creating temporary folders at %s", tmpFolder)
	err := os.MkdirAll(filesFolder, 0755)
	if err != nil {
		Error("Failed to create restore folders: %v", err)
		return fmt.Errorf("failed to create restore folders: %v", err)
	}

	// Step 1: Download backup archive from central bucket
	archivePath := tmpFolder + "/backup_archive.tar.gz"
	sourceArchivePath := fmt.Sprintf("gs://%s/backups/%s/backup_%s.tar.gz", srcConfig.BackupBucket, environment, runId)
	Info("Step 1/4: Downloading backup archive from %s", sourceArchivePath)
	err = e.backupBackend.DownloadArchive(sourceArchivePath, archivePath)
	if err != nil {
		Error("DownloadArchive failed: %v", err)
		return fmt.Errorf("DownloadArchive failed: %v", err)
	}

	// Step 2: Extract archive
	Info("Step 2/4: Extracting backup archive")
	err = ExtractBackupArchive(archivePath, tmpFolder)
	if err != nil {
		Error("ExtractBackupArchive failed: %v", err)
		return fmt.Errorf("ExtractBackupArchive failed: %v", err)
	}

	// Step 3: Import database to destination
	dumpPath := tmpFolder + "/db_dump.sql"
	databaseName := destConfig.DBName
	Info("Step 3/4: Importing database to %s", databaseName)
	err = e.backupBackend.ImportDatabase(databaseName, dumpPath)
	if err != nil {
		Error("ImportDatabase failed: %v", err)
		return fmt.Errorf("ImportDatabase failed: %v", err)
	}

	// Step 4: Upload files to destination VM via rsync
	Info("Step 4/4: Uploading files to destination VM via rsync")
	err = e.backupBackend.UploadFolder(filesFolder, destConfig)
	if err != nil {
		Error("UploadFolder failed: %v", err)
		return fmt.Errorf("UploadFolder failed: %v", err)
	}

	// Clean up temporary files
	Info("Cleaning up temporary files at %s", tmpFolder)
	err = os.RemoveAll(tmpFolder)
	if err != nil {
		Error("Failed to clean up temporary files: %v", err)
		// Not a fatal error, so we don't return
	}

	Info("Restore completed successfully from '%s' to '%s' using run ID '%s'", environment, destinationEnvironment, runId)
	return nil
}

func CreateBackupArchive(archivePath string, sqlDumpPath string, filesFolder string) error {
	Info("Creating archive at %s", archivePath)
	// Create the output file
	file, err := os.Create(archivePath)
	if err != nil {
		Error("Failed to create archive file: %v", err)
		return fmt.Errorf("failed to create archive file: %v", err)
	}
	defer file.Close()

	// Create a gzip writer
	gzipWriter := gzip.NewWriter(file)
	defer gzipWriter.Close()

	// Create a tar writer
	tarWriter := tar.NewWriter(gzipWriter)
	defer tarWriter.Close()

	// Add SQL dump file to the tar
	Info("Adding SQL dump to archive")
	if err := addFileToTar(tarWriter, sqlDumpPath, "db_dump.sql"); err != nil {
		Error("Failed to add SQL dump: %v", err)
		return fmt.Errorf("failed to add SQL dump: %v", err)
	}

	// Add all files from the files folder to the tar (recursively)
	Info("Adding files from %s to archive", filesFolder)
	err = filepath.Walk(filesFolder, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		// Skip directories
		if info.IsDir() {
			return nil
		}
		// Get relative path for the file in the archive
		relPath, err := filepath.Rel(filesFolder, path)
		if err != nil {
			return fmt.Errorf("failed to get relative path: %v", err)
		}
		archiveName := filepath.Join("files", relPath)
		return addFileToTar(tarWriter, path, archiveName)
	})
	if err != nil {
		Error("Failed to add files to archive: %v", err)
		return fmt.Errorf("failed to add files to archive: %v", err)
	}

	Info("Archive created successfully at %s", archivePath)
	return nil
}

func ExtractBackupArchive(archivePath string, destinationFolder string) error {
	Info("Extracting archive from %s to %s", archivePath, destinationFolder)

	// Open the archive file
	file, err := os.Open(archivePath)
	if err != nil {
		Error("Failed to open archive file: %v", err)
		return fmt.Errorf("failed to open archive file: %v", err)
	}
	defer file.Close()

	// Create gzip reader
	gzipReader, err := gzip.NewReader(file)
	if err != nil {
		Error("Failed to create gzip reader: %v", err)
		return fmt.Errorf("failed to create gzip reader: %v", err)
	}
	defer gzipReader.Close()

	// Create tar reader
	tarReader := tar.NewReader(gzipReader)

	// Extract all files
	fileCount := 0
	for {
		header, err := tarReader.Next()
		if err == io.EOF {
			break
		}
		if err != nil {
			Error("Failed to read tar header: %v", err)
			return fmt.Errorf("failed to read tar header: %v", err)
		}

		// Determine the output path
		targetPath := filepath.Join(destinationFolder, header.Name)

		// Check for directory traversal
		if !filepath.HasPrefix(targetPath, filepath.Clean(destinationFolder)+string(os.PathSeparator)) {
			Error("Invalid file path in archive: %s", header.Name)
			return fmt.Errorf("invalid file path in archive: %s", header.Name)
		}

		switch header.Typeflag {
		case tar.TypeDir:
			// Create directory
			if err := os.MkdirAll(targetPath, 0755); err != nil {
				Error("Failed to create directory %s: %v", targetPath, err)
				return fmt.Errorf("failed to create directory %s: %v", targetPath, err)
			}
		case tar.TypeReg:
			// Create file
			if err := os.MkdirAll(filepath.Dir(targetPath), 0755); err != nil {
				Error("Failed to create parent directory: %v", err)
				return fmt.Errorf("failed to create parent directory: %v", err)
			}

			outFile, err := os.Create(targetPath)
			if err != nil {
				Error("Failed to create file %s: %v", targetPath, err)
				return fmt.Errorf("failed to create file %s: %v", targetPath, err)
			}

			if _, err := io.Copy(outFile, tarReader); err != nil {
				outFile.Close()
				Error("Failed to extract file %s: %v", targetPath, err)
				return fmt.Errorf("failed to extract file %s: %v", targetPath, err)
			}
			outFile.Close()
			fileCount++
		}
	}

	Info("Successfully extracted %d files from archive", fileCount)
	return nil
}

func addFileToTar(tarWriter *tar.Writer, filePath string, archiveName string) error {
	file, err := os.Open(filePath)
	if err != nil {
		return fmt.Errorf("failed to open file %s: %v", filePath, err)
	}
	defer file.Close()

	info, err := file.Stat()
	if err != nil {
		return fmt.Errorf("failed to stat file %s: %v", filePath, err)
	}

	header, err := tar.FileInfoHeader(info, "")
	if err != nil {
		return fmt.Errorf("failed to create tar header for %s: %v", filePath, err)
	}

	// Use the provided archive name for the header
	header.Name = archiveName
	if err := tarWriter.WriteHeader(header); err != nil {
		return fmt.Errorf("failed to write header for %s: %v", archiveName, err)
	}

	if _, err := io.Copy(tarWriter, file); err != nil {
		return fmt.Errorf("failed to write file %s to tar: %v", filePath, err)
	}

	return nil
}
