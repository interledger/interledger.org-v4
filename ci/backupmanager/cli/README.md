# Backup Manager CLI

A command-line tool for testing backup and restore operations.

## Setup

1. Copy the sample environment file and configure it:
   ```bash
   cp sample.env .env
   # Edit .env with your actual values
   ```

2. Build the CLI:
   ```bash
   go build -o backup-cli
   ```

## Usage

### Backup Command

Create a backup of an environment:

```bash
# Load environment variables
source .env

# Run backup
./backup-cli backup -env staging -run-id $(date +%Y%m%d-%H%M%S)
```

### Restore Command

Restore a backup to a destination environment:

```bash
# Load environment variables
source .env

# Run restore
./backup-cli restore -env staging -run-id 20241128-120000 -dest-env production
```

## Environment Variables

The following environment variables are required:

- `GCP_PROJECT_ID` - Your GCP project ID
- `BACKUP_BUCKET` - Bucket where backups are stored
- `DB_NAME_STAGING` - Staging database name
- `STORAGE_BUCKET_STAGING` - Staging files storage bucket
- `CLOUDSQL_INSTANCE_STAGING` - Staging Cloud SQL instance name (just the instance name, NOT the full connection string like `project:region:instance`)
- `DB_NAME_PRODUCTION` - Production database name
- `STORAGE_BUCKET_PRODUCTION` - Production files storage bucket
- `CLOUDSQL_INSTANCE_PRODUCTION` - Production Cloud SQL instance name (just the instance name, NOT the full connection string like `project:region:instance`)

## Examples

### Full Backup Workflow

```bash
# 1. Set up environment
source .env

# 2. Create a backup with a unique run ID
RUN_ID=$(date +%Y%m%d-%H%M%S)
./backup-cli backup -env staging -run-id $RUN_ID

# 3. Verify the backup (check GCS bucket)
gsutil ls gs://${BACKUP_BUCKET}/backups/staging/

# 4. Restore to another environment (if needed)
./backup-cli restore -env staging -run-id $RUN_ID -dest-env production
```

### Testing Locally

For local testing, you can use the mock backend by running the tests:

```bash
cd ..
go test -v
```
