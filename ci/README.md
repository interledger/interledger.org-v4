# CI/CD and Deployment Infrastructure

This directory contains all the deployment automation, backup management, and CI/CD configuration for the Interledger Foundation website.

## Directory Structure

```
ci/
├── Makefile                    # Deployment and backup commands
├── backupmanager/              # Go application for backup/restore operations
│   ├── cli/                   # CLI interface
│   ├── backendgcp.go          # GCP-specific backup backend
│   └── engine.go              # Core backup/restore engine
├── deploy/                     # Environment-specific deployment configs
│   ├── staging/               # Staging environment configs
│   └── production/            # Production environment configs
└── scripts/                    # Utility scripts
```

## Quick Reference

### Deployments

Deployments are automated via GitHub Actions but can also be triggered manually:

```bash
# Deploy to staging (automated on merge to main)
make deploy ENV=staging RUN=manual-$(date +%Y%m%d)

# Deploy to production (manual workflow dispatch required)
# Trigger via GitHub Actions UI: "Deploy to Production" workflow
```

### Backups

**Prerequisites:**
- Authenticate with GCP: `gcloud auth login`
- Ensure `.env` file exists at `ci/backupmanager/cli/.env`

```bash
# Backup staging environment
make backup ENV=staging RUN=backup-$(date +%Y%m%d)

# Backup production environment
make backup ENV=production RUN=backup-$(date +%Y%m%d)

# List available backups
make list-backups ENV=staging
make list-backups ENV=production
```

### Restores

```bash
# Restore staging from a backup
make restore ENV=staging FROM-RUN=backup-20241203 TARGETENV=staging

# Restore production from a staging backup (common workflow)
make restore ENV=staging FROM-RUN=backup-20241203 TARGETENV=production

# Restore production from a production backup
make restore ENV=production FROM-RUN=backup-20241203 TARGETENV=production
```

## GitHub Actions Workflows

Located in `.github/workflows/`:

### Automated Workflows

- **`rebuild-staging.yaml`**: Automatically deploys to staging on merge to `main`
- **`backup.yml`**: Manual workflow to create backups of staging or production
- **`restore.yml`**: Manual workflow to restore from backups

### Manual Workflows

- **`deploy-production.yaml`**: Manual deployment to production (requires typing "DEPLOY" to confirm)

## Backup Manager

The backup manager is a Go application that handles database and file backups/restores using GCP services.

### Configuration

Create `ci/backupmanager/cli/.env` based on `sample.env`:

```bash
cp ci/backupmanager/cli/sample.env ci/backupmanager/cli/.env
# Edit .env with your configuration
```

Required environment variables:
- `GCP_PROJECT_ID`: GCP project ID
- `BACKUP_BUCKET`: GCS bucket for storing backups
- `DB_NAME_STAGING`, `DB_NAME_PRODUCTION`: Database names
- `CLOUDSQL_INSTANCE_STAGING`, `CLOUDSQL_INSTANCE_PRODUCTION`: Cloud SQL instance names
- `TARGET_HOST_STAGING`, `TARGET_HOST_PRODUCTION`: VM hostnames/IPs
- `TARGET_USER_STAGING`, `TARGET_USER_PRODUCTION`: SSH usernames
- `TARGET_PATH_STAGING`, `TARGET_PATH_PRODUCTION`: File paths on VMs

### How Backups Work

1. **Database Export**: Uses Cloud SQL native export to create compressed SQL dump (`.sql.gz`) in GCS
2. **File Download**: Uses rsync over SSH to download files from VM
3. **Archive Creation**: Creates a `.tar.gz` archive containing both database dump and files
4. **Upload**: Stores archive in GCS at `gs://{BACKUP_BUCKET}/backups/{environment}/backup_{run-id}.tar.gz`

### How Restores Work

1. **Download Archive**: Retrieves backup archive from GCS
2. **Extract**: Extracts database dump and files from archive
3. **Database Import**: 
   - Decompresses SQL dump
   - Replaces source database name with target database name
   - Uploads modified SQL to GCS temporary location
   - Uses Cloud SQL import to restore database
4. **File Upload**: Uses rsync over SSH to upload files to destination VM

## Environment-Specific Configurations

### Staging (`deploy/staging/`)
- `settings.php`: Drupal settings for staging
- `htaccess`: Apache .htaccess rules
- `robots.txt`: Search engine indexing rules (disallow all for staging)
- `cleanup.sh`: Post-deployment cleanup script

### Production (`deploy/production/`)
- `settings.php`: Drupal settings for production
- `htaccess`: Apache .htaccess rules
- `robots.txt`: Search engine indexing rules (allow all)
- `cleanup.sh`: Post-deployment cleanup script

## Common Tasks

### Creating a Backup Before Deployment

```bash
cd ci
make backup ENV=production RUN=pre-deploy-$(date +%Y%m%d-%H%M%S)
```

### Promoting Staging to Production

```bash
# 1. Backup current production (safety measure)
make backup ENV=production RUN=pre-promotion-$(date +%Y%m%d)

# 2. Backup staging (this will be promoted)
make backup ENV=staging RUN=promotion-$(date +%Y%m%d)

# 3. Restore staging backup to production
make restore ENV=staging FROM-RUN=promotion-$(date +%Y%m%d) TARGETENV=production
```

### Troubleshooting

**Issue: "Error: .env file not found"**
```bash
cp ci/backupmanager/cli/sample.env ci/backupmanager/cli/.env
# Edit the .env file with correct values
```

**Issue: Authentication errors**
```bash
gcloud auth login
gcloud config set project interledger-websites
```

**Issue: SSH connection failures**
- Ensure SSH keys are properly configured
- Verify the target host is accessible
- Check that the deployer user has appropriate permissions

## Development

To work on the backup manager:

```bash
cd ci/backupmanager

# Install dependencies
go mod download

# Build
go build -o backup-cli ./cli

# Run tests
go test ./...

# Run locally
go run cli/main.go backup -env staging -run-id test-backup
```

## Security Notes

- Never commit `.env` files or credentials to the repository
- All secrets are managed via GitHub Secrets for CI/CD
- SSH keys are securely stored in GitHub Secrets
- GCP authentication uses service account credentials stored as GitHub Secrets
