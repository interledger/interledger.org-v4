package main

import (
	"context"
	"flag"
	"fmt"
	"os"
	"strings"

	"cloud.google.com/go/storage"
	backupmanager "github.com/interledger/interledger.org-v4/ci/backup-manager"
	"github.com/joho/godotenv"
)

func main() {
	// Load .env files if they exist (ignore errors)
	_ = godotenv.Load()
	_ = godotenv.Load("cli/.env")

	// Define subcommands
	backupCmd := flag.NewFlagSet("backup", flag.ExitOnError)
	restoreCmd := flag.NewFlagSet("restore", flag.ExitOnError)
	preflightCmd := flag.NewFlagSet("preflight", flag.ExitOnError)

	// Backup command flags
	backupEnv := backupCmd.String("env", "", "Environment to backup (staging or production)")
	backupRunID := backupCmd.String("run-id", "", "Unique run ID for this backup")

	// Restore command flags
	restoreEnv := restoreCmd.String("env", "", "Source environment of the backup (staging or production)")
	restoreRunID := restoreCmd.String("run-id", "", "Run ID of the backup to restore")
	restoreDestEnv := restoreCmd.String("dest-env", "", "Destination environment to restore to (staging or production)")

	// Check for subcommand
	if len(os.Args) < 2 {
		printUsage()
		os.Exit(1)
	}

	// Load environment configs
	configs, err := loadConfigs()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error loading configs: %v\n", err)
		fmt.Fprintln(os.Stderr, "\nMake sure you have set the following environment variables:")
		fmt.Fprintln(os.Stderr, "  - BACKUP_BUCKET")
		fmt.Fprintln(os.Stderr, "  - GCP_PROJECT_ID")
		fmt.Fprintln(os.Stderr, "  - DB_NAME_STAGING, DB_NAME_PRODUCTION")
		fmt.Fprintln(os.Stderr, "  - CLOUDSQL_INSTANCE_STAGING, CLOUDSQL_INSTANCE_PRODUCTION")
		fmt.Fprintln(os.Stderr, "  - TARGET_HOST_STAGING, TARGET_HOST_PRODUCTION")
		fmt.Fprintln(os.Stderr, "  - TARGET_USER_STAGING, TARGET_USER_PRODUCTION")
		fmt.Fprintln(os.Stderr, "  - TARGET_PATH_STAGING, TARGET_PATH_PRODUCTION")
		os.Exit(1)
	}

	// Create backup engine
	engine := backupmanager.NewBackupEngineGcp(configs)

	// Parse subcommand
	switch os.Args[1] {
	case "backup":
		backupCmd.Parse(os.Args[2:])
		if *backupEnv == "" || *backupRunID == "" {
			fmt.Fprintln(os.Stderr, "Error: -env and -run-id are required")
			backupCmd.PrintDefaults()
			os.Exit(1)
		}
		if *backupEnv != "staging" && *backupEnv != "production" {
			fmt.Fprintln(os.Stderr, "Error: -env must be 'staging' or 'production'")
			os.Exit(1)
		}

		fmt.Printf("Starting backup for environment '%s' with run ID '%s'...\n", *backupEnv, *backupRunID)
		if err := engine.PerformBackup(*backupEnv, *backupRunID); err != nil {
			fmt.Fprintf(os.Stderr, "Backup failed: %v\n", err)
			os.Exit(1)
		}
		fmt.Println("✓ Backup completed successfully!")

	case "restore":
		restoreCmd.Parse(os.Args[2:])
		if *restoreEnv == "" || *restoreRunID == "" || *restoreDestEnv == "" {
			fmt.Fprintln(os.Stderr, "Error: -env, -run-id, and -dest-env are required")
			restoreCmd.PrintDefaults()
			os.Exit(1)
		}
		if *restoreEnv != "staging" && *restoreEnv != "production" {
			fmt.Fprintln(os.Stderr, "Error: -env must be 'staging' or 'production'")
			os.Exit(1)
		}
		if *restoreDestEnv != "staging" && *restoreDestEnv != "production" {
			fmt.Fprintln(os.Stderr, "Error: -dest-env must be 'staging' or 'production'")
			os.Exit(1)
		}

		fmt.Printf("Starting restore from environment '%s' (run ID '%s') to '%s'...\n",
			*restoreEnv, *restoreRunID, *restoreDestEnv)
		if err := engine.PerformRestore(*restoreEnv, *restoreRunID, *restoreDestEnv); err != nil {
			fmt.Fprintf(os.Stderr, "Restore failed: %v\n", err)
			os.Exit(1)
		}
		fmt.Println("✓ Restore completed successfully!")

	case "preflight":
		preflightCmd.Parse(os.Args[2:])
		if err := runPreflight(configs); err != nil {
			fmt.Fprintf(os.Stderr, "Preflight failed: %v\n", err)
			os.Exit(1)
		}
		fmt.Println("✓ Preflight checks passed: Cloud SQL service agent has required bucket IAM roles.")

	default:
		printUsage()
		os.Exit(1)
	}
}

func printUsage() {
	fmt.Println("Backup Manager CLI")
	fmt.Println()
	fmt.Println("Usage:")
	fmt.Println("  backup-cli backup  -env <environment> -run-id <run-id>")
	fmt.Println("  backup-cli restore   -env <environment> -run-id <run-id> -dest-env <destination-environment>")
	fmt.Println("  backup-cli preflight")
	fmt.Println()
	fmt.Println("Commands:")
	fmt.Println("  backup   Create a backup of the specified environment")
	fmt.Println("  restore   Restore a backup to the specified destination environment")
	fmt.Println("  preflight Validate IAM: Cloud SQL service agent access to BACKUP_BUCKET")
	fmt.Println()
	fmt.Println("Examples:")
	fmt.Println("  backup-cli backup -env staging -run-id 2024-01-15-001")
	fmt.Println("  backup-cli restore -env staging -run-id 2024-01-15-001 -dest-env production")
}

func loadConfigs() (backupmanager.EnvironmentConfigs, error) {
	configs := make(backupmanager.EnvironmentConfigs)

	staging := &backupmanager.EnvironmentConfig{
		BackupBucket:     os.Getenv("BACKUP_BUCKET"),
		DBName:           os.Getenv("DB_NAME_STAGING"),
		GCPProjectID:     os.Getenv("GCP_PROJECT_ID"),
		CloudSQLInstance: os.Getenv("CLOUDSQL_INSTANCE_STAGING"),
		TargetHost:       os.Getenv("TARGET_HOST_STAGING"),
		TargetUser:       os.Getenv("TARGET_USER_STAGING"),
		TargetPath:       os.Getenv("TARGET_PATH_STAGING"),
	}

	production := &backupmanager.EnvironmentConfig{
		BackupBucket:     os.Getenv("BACKUP_BUCKET"),
		DBName:           os.Getenv("DB_NAME_PRODUCTION"),
		GCPProjectID:     os.Getenv("GCP_PROJECT_ID"),
		CloudSQLInstance: os.Getenv("CLOUDSQL_INSTANCE_PRODUCTION"),
		TargetHost:       os.Getenv("TARGET_HOST_PRODUCTION"),
		TargetUser:       os.Getenv("TARGET_USER_PRODUCTION"),
		TargetPath:       os.Getenv("TARGET_PATH_PRODUCTION"),
	}

	// Validate required fields
	if staging.BackupBucket == "" {
		return nil, fmt.Errorf("BACKUP_BUCKET is required")
	}
	if staging.GCPProjectID == "" {
		return nil, fmt.Errorf("GCP_PROJECT_ID is required")
	}
	if staging.DBName == "" || staging.CloudSQLInstance == "" || staging.TargetHost == "" || staging.TargetUser == "" || staging.TargetPath == "" {
		return nil, fmt.Errorf("missing staging environment configuration")
	}
	if production.DBName == "" || production.CloudSQLInstance == "" || production.TargetHost == "" || production.TargetUser == "" || production.TargetPath == "" {
		return nil, fmt.Errorf("missing production environment configuration")
	}

	configs["staging"] = staging
	configs["production"] = production

	return configs, nil
}

// runPreflight validates that the Cloud SQL service agent has viewer and (optionally) creator roles on the BACKUP_BUCKET.
func runPreflight(configs backupmanager.EnvironmentConfigs) error {
	// Use the backup bucket from either environment (they share the same bucket per current config).
	var backupBucket string
	if cfg, ok := configs["staging"]; ok && cfg != nil && cfg.BackupBucket != "" {
		backupBucket = cfg.BackupBucket
	} else if cfg, ok := configs["production"]; ok && cfg != nil && cfg.BackupBucket != "" {
		backupBucket = cfg.BackupBucket
	}
	if backupBucket == "" {
		return fmt.Errorf("BACKUP_BUCKET is not configured")
	}

	ctx := context.Background()
	client, err := storage.NewClient(ctx)
	if err != nil {
		return fmt.Errorf("failed to create storage client: %v", err)
	}
	defer client.Close()

	b := client.Bucket(backupBucket)
	// Use legacy IAM policy helper methods
	policy, err := b.IAM().Policy(ctx)
	if err != nil {
		return fmt.Errorf("failed to read bucket IAM policy: %v", err)
	}

	viewerMembers := policy.Members("roles/storage.objectViewer")
	creatorMembers := policy.Members("roles/storage.objectCreator")
	hasViewer := membersContainCloudSQLSA(viewerMembers)
	hasCreator := membersContainCloudSQLSA(creatorMembers)

	// Ensure every Cloud SQL SA present in creator is also present in viewer
	missing := missingCloudSqlViewers(viewerMembers, creatorMembers)

	if !hasViewer || len(missing) > 0 {
		printFixInstructions(backupBucket)
		if len(missing) > 0 {
			fmt.Printf("[ERROR] missing viewer for: %s\n", strings.Join(missing, ", "))
		}
		return fmt.Errorf("cloud sql service agent missing roles/storage.objectViewer on bucket %s", backupBucket)
	}
	if !hasCreator {
		fmt.Println("[WARN] Cloud SQL service agent missing roles/storage.objectCreator (needed for exports).")
	}
	return nil
}

// membersContainCloudSQLSA returns true if any member looks like a Cloud SQL service agent.
func membersContainCloudSQLSA(members []string) bool {
	for _, m := range members {
		if strings.HasSuffix(m, "@gcp-sa-cloud-sql.iam.gserviceaccount.com") {
			return true
		}
	}
	return false
}

// missingCloudSqlViewers returns Cloud SQL SA identities present in creatorMembers but not in viewerMembers.
func missingCloudSqlViewers(viewerMembers, creatorMembers []string) []string {
	// Build sets
	toSA := func(list []string) map[string]struct{} {
		m := make(map[string]struct{})
		for _, v := range list {
			if strings.HasSuffix(v, "@gcp-sa-cloud-sql.iam.gserviceaccount.com") {
				m[v] = struct{}{}
			}
		}
		return m
	}
	viewers := toSA(viewerMembers)
	creators := toSA(creatorMembers)

	var missing []string
	for k := range creators {
		if _, ok := viewers[k]; !ok {
			missing = append(missing, k)
		}
	}
	return missing
}

func printFixInstructions(bucket string) {
	fmt.Println("[ERROR] Cloud SQL service agent does not have viewer role on the backup bucket.")
	fmt.Println("To fix, run:")
	fmt.Printf("  PROJECT_NUMBER=$(gcloud projects describe \"$GCP_PROJECT_ID\" --format='value(projectNumber)')\n")
	fmt.Printf("  SA=service-$PROJECT_NUMBER@gcp-sa-cloud-sql.iam.gserviceaccount.com\n")
	fmt.Printf("  gcloud storage buckets add-iam-policy-binding gs://%s --member \"serviceAccount:$SA\" --role roles/storage.objectViewer\n", bucket)
}
