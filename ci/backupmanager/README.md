Interledger Org Backup Manager

Golang based scripts to facilitate the backup management of the interledger websites.

## Architecture

The backup manager now uses a hybrid approach:
- **Database**: Cloud SQL (managed via Cloud SQL Admin API)
- **Files**: VM-based storage (accessed via SSH/rsync)
- **Backups**: Google Cloud Storage (backup archives)

## How It Works

### Backup Process
1. **Database Export**: Uses Cloud SQL Admin API to export database to GCS temporarily, then downloads
2. **Files Download**: Uses `rsync` over SSH to download files from VM at `/var/www/$ENV/web/sites/default/files`
3. **Archive Creation**: Creates local tar.gz archive containing database dump and files
4. **Upload**: Uploads archive to `gs://$BACKUP_BUCKET/backups/$ENV/backup_$RUN_ID.tar.gz`

### Restore Process
1. **Download Archive**: Downloads backup archive from GCS
2. **Extract**: Extracts database dump and files locally
3. **Database Import**: Uses Cloud SQL Admin API to import database
4. **Files Upload**: Uses `rsync` over SSH to upload files back to VM

## Configuration

Required environment variables:

### Common
- `BACKUP_BUCKET` - GCS bucket for backup archives
- `GCP_PROJECT_ID` - GCP project ID

### Per Environment (staging/production)
- `DB_NAME_<ENV>` - Database name
- `CLOUDSQL_INSTANCE_<ENV>` - Cloud SQL instance name
- `TARGET_HOST_<ENV>` - VM hostname/IP (e.g., "34.23.109.31")
- `TARGET_USER_<ENV>` - SSH username (e.g., "deployer")
- `TARGET_PATH_<ENV>` - Path to files directory on VM (e.g., "/var/www/staging/web/sites/default/files")

## Prerequisites

- SSH access configured (GitHub Actions workflows handle this automatically)
- `rsync` installed (available by default on ubuntu-latest runners)
- Google Cloud authentication configured
- Cloud SQL service account needs `roles/storage.objectViewer` and `roles/storage.objectCreator` on backup bucket

## Usage

```bash
# Backup
./backup-cli backup -env staging -run-id 2024-12-03-001

# Restore
./backup-cli restore -env staging -run-id 2024-12-03-001 -dest-env production

# Preflight checks
./backup-cli preflight
```