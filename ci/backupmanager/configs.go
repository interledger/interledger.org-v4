package backupmanager

import (
	"fmt"
	"os"
	"strings"
)

type EnvironmentConfigs map[string]*EnvironmentConfig

// EnvironmentConfig holds typed configuration values for an environment.
type EnvironmentConfig struct {
	BackupBucket     string
	DBName           string
	StorageBucket    string
	GCPProjectID     string
	CloudSQLInstance string
}

func environmentConfigs() (EnvironmentConfigs, error) {
	configs := make(EnvironmentConfigs)

	// Load all configured environments from ENVIRONMENTS env var
	// Default to staging and production if not specified
	environments := []string{"staging", "production"}
	if envList := os.Getenv("ENVIRONMENTS"); envList != "" {
		environments = strings.Split(envList, ",")
	}

	for _, env := range environments {
		env = strings.TrimSpace(env)
		config, err := environmentConfig(env)
		if err != nil {
			return nil, fmt.Errorf("error loading %s environment config: %v", env, err)
		}
		configs[env] = config
	}

	return configs, nil
}

func environmentConfig(environment string) (*EnvironmentConfig, error) {
	cfg := &EnvironmentConfig{}

	cfg.BackupBucket = os.Getenv("BACKUP_BUCKET")
	if cfg.BackupBucket == "" {
		return nil, fmt.Errorf("missing configuration BACKUP_BUCKET")
	}

	dbName := os.Getenv(fmt.Sprintf("DB_NAME_%s", strings.ToUpper(environment)))
	if dbName == "" {
		return nil, fmt.Errorf("missing configuration DB_NAME_%s", strings.ToUpper(environment))
	}
	cfg.DBName = dbName

	storageBucket := os.Getenv(fmt.Sprintf("STORAGE_BUCKET_%s", strings.ToUpper(environment)))
	if storageBucket == "" {
		return nil, fmt.Errorf("missing configuration STORAGE_BUCKET_%s", strings.ToUpper(environment))
	}
	cfg.StorageBucket = storageBucket

	gcpProjectID := os.Getenv("GCP_PROJECT_ID")
	if gcpProjectID == "" {
		return nil, fmt.Errorf("missing configuration GCP_PROJECT_ID")
	}
	cfg.GCPProjectID = gcpProjectID

	cloudSQLInstance := os.Getenv(fmt.Sprintf("CLOUDSQL_INSTANCE_%s", strings.ToUpper(environment)))
	if cloudSQLInstance == "" {
		return nil, fmt.Errorf("missing configuration CLOUDSQL_INSTANCE_%s", strings.ToUpper(environment))
	}
	cfg.CloudSQLInstance = cloudSQLInstance

	// Return the typed config
	return cfg, nil
}
