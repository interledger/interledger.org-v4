package backupmanager

import (
	"fmt"
	"os"
	"path/filepath"
	"testing"
)

type MockBackend struct {
	failDownload   bool
	archiveToServe string
}

func NewMockBackend() *MockBackend {
	return &MockBackend{}
}

func (b *MockBackend) DownloadFolder(source string, destination string) error {
	if b.failDownload {
		return fmt.Errorf("simulated download failure")
	}

	// Create dummy files to simulate download
	err := os.MkdirAll(destination, 0755)
	if err != nil {
		return err
	}
	dummyFilePath := filepath.Join(destination, "dummy.txt")
	err = os.WriteFile(dummyFilePath, []byte("This is a test file."), 0644)
	if err != nil {
		return err
	}

	return nil
}

func (b *MockBackend) ExportDatabase(databaseName string, dumpPath string) error {
	// Mock export logic here
	err := os.WriteFile(dumpPath, []byte("CREATE TABLE test (id INT);"), 0644)
	if err != nil {
		return err
	}
	return nil
}

func (b *MockBackend) UploadArchive(archivePath string, destination string) error {
	// Mock upload logic here
	return nil
}

func (b *MockBackend) DownloadArchive(archivePath string, destinationPath string) error {
	// Copy the prepared archive to the destination
	if b.archiveToServe == "" {
		return fmt.Errorf("no mock archive set")
	}
	data, err := os.ReadFile(b.archiveToServe)
	if err != nil {
		return fmt.Errorf("failed to read mock archive: %v", err)
	}
	return os.WriteFile(destinationPath, data, 0644)
}

func (b *MockBackend) ImportDatabase(databaseName string, sqlFilePath string) error {
	// Mock import logic - just verify the SQL file exists
	if _, err := os.Stat(sqlFilePath); err != nil {
		return fmt.Errorf("SQL file not found: %v", err)
	}
	return nil
}

func (b *MockBackend) UploadFolder(sourcePath string, gcsDestination string) error {
	// Mock folder upload logic - just verify source exists
	if _, err := os.Stat(sourcePath); err != nil {
		return fmt.Errorf("source path not found: %v", err)
	}
	return nil
}

func mockConfigs() EnvironmentConfigs {
	return EnvironmentConfigs{
		"staging": &EnvironmentConfig{
			BackupBucket:     "test-backup-bucket",
			DBName:           "staging_db",
			StorageBucket:    "test-storage-bucket",
			GCPProjectID:     "test-project",
			CloudSQLInstance: "test-instance-staging",
		},
		"production": &EnvironmentConfig{
			BackupBucket:     "test-backup-bucket",
			DBName:           "production_db",
			StorageBucket:    "test-storage-bucket",
			GCPProjectID:     "test-project",
			CloudSQLInstance: "test-instance-production",
		},
	}
}

func TestFullBackup(t *testing.T) {
	backend := NewMockBackend()
	configs := mockConfigs()
	engine := &BackupEngineCloud{
		backupBackend: backend,
		configs:       configs,
	}

	err := engine.PerformBackup("staging", "test-run-001")
	if err != nil {
		t.Errorf("PerformBackup failed: %v", err)
	}

}

func TestFullBackupButDownloadFails(t *testing.T) {
	backend := NewMockBackend()
	backend.failDownload = true
	configs := mockConfigs()
	engine := &BackupEngineCloud{
		backupBackend: backend,
		configs:       configs,
	}

	err := engine.PerformBackup("staging", "test-run-002")
	if err == nil {
		t.Errorf("PerformBackup should have failed due to download error")
	}
}

func TestFullRestore(t *testing.T) {
	backend := NewMockBackend()
	configs := mockConfigs()
	engine := &BackupEngineCloud{
		backupBackend: backend,
		configs:       configs,
	}

	// First create a test backup archive
	tmpFolder := "/tmp/test_restore_archive"
	filesFolder := tmpFolder + "/files"
	err := os.MkdirAll(filesFolder, 0755)
	if err != nil {
		t.Fatalf("Failed to create files folder: %v", err)
	}
	defer os.RemoveAll(tmpFolder)

	// Create some test files
	dummyFilePath := filesFolder + "/test.txt"
	err = os.WriteFile(dummyFilePath, []byte("test content"), 0644)
	if err != nil {
		t.Fatalf("Failed to create test file: %v", err)
	}

	// Create a test SQL dump
	sqlDumpPath := tmpFolder + "/database.sql.gz"
	err = os.WriteFile(sqlDumpPath, []byte("CREATE TABLE test (id INT);"), 0644)
	if err != nil {
		t.Fatalf("Failed to create SQL dump: %v", err)
	}

	// Create archive
	archivePath := tmpFolder + "/backup_archive.tar.gz"
	err = CreateBackupArchive(archivePath, sqlDumpPath, filesFolder)
	if err != nil {
		t.Fatalf("Failed to create archive: %v", err)
	}

	// Now test restore
	backend.archiveToServe = archivePath
	err = engine.PerformRestore("staging", "test-run-restore-001", "production")
	if err != nil {
		t.Errorf("PerformRestore failed: %v", err)
	}
}

func TestCreateBackupArchive(t *testing.T) {
	// Create tmp folder with dummy files
	tmpFolder := "/tmp/test_backup_archive"
	filesFolder := tmpFolder + "/files"
	err := os.MkdirAll(filesFolder, 0755)
	if err != nil {
		t.Fatalf("Failed to create files folder: %v", err)
	}
	dummyFilePath := filesFolder + "/dummy.txt"
	err = os.WriteFile(dummyFilePath, []byte("This is a test file."), 0644)
	if err != nil {
		t.Fatalf("Failed to create dummy file: %v", err)
	}
	dumpPath := tmpFolder + "/db_dump.sql"
	err = os.WriteFile(dumpPath, []byte("CREATE TABLE test (id INT);"), 0644)
	if err != nil {
		t.Fatalf("Failed to create dummy db dump: %v", err)
	}

	// Create backup archive
	archivePath := tmpFolder + "/backup_archive.tar.gz"
	err = CreateBackupArchive(archivePath, dumpPath, filesFolder)
	if err != nil {
		t.Fatalf("CreateBackupArchive failed: %v", err)
	}

	// Check if archive file exists
	if _, err := os.Stat(archivePath); os.IsNotExist(err) {
		t.Fatalf("Backup archive was not created")
	}
}
