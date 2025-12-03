# GitHub Copilot Instructions for Interledger.org Infrastructure

## Project Overview

This is the Interledger Foundation website, a Drupal 10 CMS deployed on Google Cloud Platform. The project is currently migrating from AWS to GCP.

## Infrastructure Architecture

### Hosting Model
- **Compute**: Single GCE instance (`interledger-org-drupal`) running Apache and PHP
- **Database**: Cloud SQL (MySQL) with separate databases for staging and production
- **Load Balancer**: GCP External HTTPS Load Balancer (IP: 34.111.215.251)
- **Files**: Stored locally on VM at `/var/www/{environment}/web/sites/default/files/`
- **Static Portal**: Developers portal runs on Cloud Run (nginx serving Astro-built static site)

### Environment Configuration

**Both production and staging run on the same VM:**
- **Production**: `/var/www/production` → `interledger.org`
- **Staging**: `/var/www/staging` → `staging.interledger.org`
- **Routing**: Apache virtual hosts differentiate based on `Host` header
- **Shared Backend**: Both use `drupal-sites-backend` in the load balancer

**Important**: A VM instance can only belong to one load-balanced instance group. This is why both environments share the `drupal-sites-backend` backend service.

### Load Balancer Routing

The URL map (`interledger-org`) routes traffic:
1. `interledger.org` → `drupal-sites-backend` (VM) for all paths except:
   - `/developers` → `nginx-rewrite-backend` (Cloud Run)
2. `staging.interledger.org` → `drupal-sites-backend` (VM) for all paths except:
   - `/developers` → `nginx-rewrite-backend` (Cloud Run)
3. `uwa.interledger.org` → `umami-analytics-backend` (Cloud Run)

### Database Credentials

**Never hardcode database credentials in settings.php!**

Credentials are supplied via Apache environment variables:
```apache
SetEnv DRUPAL_DB_HOST 127.0.0.1
SetEnv DRUPAL_DB_NAME interledger_production
SetEnv DRUPAL_DB_USER production_user
SetEnv DRUPAL_DB_PASSWORD [password]
```

The `settings.php` file reads these using `getenv()`:
```php
$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DB_NAME'),
  'username' => getenv('DRUPAL_DB_USER'),
  'password' => getenv('DRUPAL_DB_PASSWORD'),
  'host' => getenv('DRUPAL_DB_HOST'),
];
```

## Key Directories

- **`ci/`**: All deployment automation, backups, and CI/CD configuration
  - **`ci/Makefile`**: Commands for deploy, backup, restore operations
  - **`ci/backupmanager/`**: Go application for backup/restore with Cloud SQL
  - **`ci/deploy/`**: Environment-specific configs and load balancer configuration
- **`web/`**: Drupal docroot
- **`local/`**: Local development with Docker Compose
- **`.github/workflows/`**: GitHub Actions workflows

## Deployment Workflows

### Automated Deployments
- **Staging**: Deploys automatically on merge to `main` (workflow: `rebuild-staging.yaml`)
- **Production**: Manual workflow dispatch only (workflow: `deploy-production.yaml`, requires typing "DEPLOY")

### Manual Deployment via Makefile
```bash
cd ci
make deploy ENV=staging RUN=manual-$(date +%Y%m%d)
make deploy ENV=production RUN=manual-$(date +%Y%m%d)
```

Deployment process:
1. rsync code to VM (excluding files, vendor, local dev folders)
2. Copy environment-specific `settings.php`, `htaccess`, `robots.txt`
3. Run cleanup script on VM (composer install, cache clear, etc.)

## Backup and Restore System

### Backup Manager (Go Application)

Location: `ci/backupmanager/`

**Configuration**: Requires `.env` file at `ci/backupmanager/cli/.env` (never commit this!)

**How it works:**
1. **Backup**:
   - Exports database using Cloud SQL native export (creates `.sql.gz` in GCS)
   - Downloads files from VM via rsync
   - Creates `.tar.gz` archive containing both
   - Uploads to `gs://{BACKUP_BUCKET}/backups/{environment}/backup_{run-id}.tar.gz`

2. **Restore**:
   - Downloads archive from GCS
   - Extracts and decompresses SQL dump
   - **Replaces source database name with target database name** (critical for cross-environment restores)
   - Uploads modified SQL to GCS temp location
   - Uses Cloud SQL import to restore database
   - Uploads files to target VM via rsync

### Using Backups

```bash
cd ci

# Create backup
make backup ENV=production RUN=backup-$(date +%Y%m%d)

# List backups
make list-backups ENV=production

# Restore (e.g., promote staging to production)
make restore ENV=staging FROM-RUN=backup-20241203 TARGETENV=production
```

## Working with the VM

### SSH Access
```bash
ssh deployer@34.23.109.31
```

### Drush Commands
**Use the convenience scripts in the deployer home directory:**

```bash
~/production-drush.sh cr              # Clear production cache
~/staging-drush.sh status             # Check staging status
~/staging-drush.sh updb -y           # Run staging updates
```

These scripts:
- Set correct file permissions
- Run as `www-data` user
- Supply database credentials via environment variables
- Set correct site URI

### Common VM Tasks
```bash
# Restart Apache
sudo systemctl restart apache2

# View logs
sudo tail -f /var/log/apache2/error.log

# Fix file permissions
sudo chown -R www-data:www-data /var/www/production/web/sites/default/files/
sudo chmod -R 775 /var/www/production/web/sites/default/files/
```

## GCP Resources

### Backend Services
- `drupal-sites-backend`: Serves both production and staging Drupal
- `nginx-rewrite-backend`: Serves `/developers` portal (Cloud Run)
- `umami-analytics-backend`: Serves Umami analytics (Cloud Run)

### Instance Groups
- `staging-interledger-ig`: Unmanaged instance group containing the VM (serves both environments despite the name)

### Health Checks
- `staging-interledger-health`: HTTP health check on port 80
- Apache default vhost responds to health checks

### Certificates
- `interledger-org`: Production certificate
- `staging-interledger-org`: Staging certificate
- `uwa-interledger-org`: Umami certificate
- Certificate map: `interledger-org-cert-map` with entries for each hostname

## URL Map Management

The URL map configuration is stored at `ci/deploy/urlmap.yaml` with read-only fields removed.

**To modify routing:**
1. Edit `ci/deploy/urlmap.yaml`
2. Import: `gcloud compute url-maps import interledger-org --source=ci/deploy/urlmap.yaml --quiet`
3. Verify: `gcloud compute url-maps describe interledger-org`

**To update local file:**
```bash
gcloud compute url-maps export interledger-org --destination=ci/deploy/urlmap.yaml
# Remove read-only fields: creationTimestamp, fingerprint, id, kind, selfLink
```

## Common Pitfalls

1. **Certificate Map Entries**: When adding a new domain, remember to create both:
   - The certificate: `gcloud certificate-manager certificates create ...`
   - The certificate map entry: `gcloud certificate-manager maps entries create ...`
   - Map entries take 5-10 minutes to become ACTIVE

2. **Database Name in SQL Dumps**: Cloud SQL exports include the source database name. The backupmanager automatically replaces this during restore, but if you manually restore, you must replace all occurrences of the source database name with the target database name.

3. **File Permissions**: Always use `www-data:www-data` ownership and `775` permissions for files directories after upload or restore.

4. **VM Instance Group Limitation**: A VM can only belong to one load-balanced instance group. Don't try to create separate instance groups for production and staging.

5. **Cache Layers**: Remember there are multiple cache layers:
   - Drupal cache (clear with drush: `~/production-drush.sh cr`)
   - Cloud CDN (invalidate with: `gcloud compute url-maps invalidate-cdn-cache`)
   - Browser cache

## GitHub Actions Secrets

Required secrets for CI/CD:
- `GSA`: Service account credentials JSON for GCP
- `DEPLOYER_KEY`: SSH private key for deployer user
- `TARGET_HOST_STAGING`, `TARGET_HOST_PRODUCTION`: VM IP addresses
- `BACKUP_BUCKET`: GCS bucket for backups
- `DB_NAME_STAGING`, `DB_NAME_PRODUCTION`: Database names
- `CLOUDSQL_INSTANCE_STAGING`, `CLOUDSQL_INSTANCE_PRODUCTION`: Cloud SQL instance names
- Plus other environment-specific variables

## Testing Deployments

After deployment, verify:
1. Site loads: `curl -I https://interledger.org/` (or staging)
2. Developers portal: `curl -I https://interledger.org/developers/`
3. Backend health: `gcloud compute backend-services get-health drupal-sites-backend --global`
4. SSL certificate: Check in browser or use `openssl s_client`
5. Drupal status: `ssh deployer@34.23.109.31` then `~/production-drush.sh status`

## Documentation

- **Main README**: Project overview, links to wiki for content management
- **CI README**: `ci/README.md` - Deployment and backup operations
- **Deploy README**: `ci/deploy/README.md` - Infrastructure and load balancer details
- **Wiki**: Content creation, site building, multilingual support

## Development Philosophy

- Keep credentials out of git (use environment variables)
- Automate staging deployments (manual for production)
- Always backup before major changes
- Use Cloud SQL native export/import for reliability
- Leverage Apache vhosts for environment separation
- Minimize infrastructure complexity (one VM, one backend, Apache routing)
