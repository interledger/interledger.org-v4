# Interledger.org Infrastructure

## Overview

The Interledger websites are served through a Google Cloud Platform (GCP) External HTTPS Load Balancer that routes traffic to various backends based on hostname and path.

### Entrypoints

- **interledger.org** (production)
  - Production Drupal website
  - Hosted on GCE VM at `/var/www/production`
  - Shared Cloud SQL database
  - `/developers` path serves the developers portal (static site via nginx on Cloud Run)
  
- **staging.interledger.org**
  - Staging Drupal website
  - Hosted on same GCE VM at `/var/www/staging`
  - Shared Cloud SQL database
  - `/developers` path serves the developers portal (static site via nginx on Cloud Run)
  
- **uwa.interledger.org**
  - Umami Analytics instance
  - Hosted on Cloud Run

### VM Architecture

Both production and staging environments run on a single GCE instance (`interledger-org-drupal`):
- **Apache Virtual Hosts**: Separate vhosts for production and staging, routing based on hostname
- **Drupal Installations**: Isolated at `/var/www/production` and `/var/www/staging`
- **Health Checks**: Default Apache site responds to GCP health checks
- **Database**: Both environments connect to Cloud SQL (separate databases: `interledger_production` and `interledger_staging`)
- **Database Credentials**: Supplied via environment variables in Apache configuration (not in `settings.php` checked into git)
- **Files**: Stored locally on VM in each environment's `web/sites/default/files/` directory

#### Database Configuration

Database credentials are managed through Apache environment variables rather than being hardcoded in `settings.php`. This approach:
- Keeps credentials out of version control
- Allows different credentials per environment on the same codebase
- Follows security best practices

The Apache virtual host configuration sets environment variables:
```apache
SetEnv DRUPAL_DB_HOST 127.0.0.1
SetEnv DRUPAL_DB_NAME interledger_production
SetEnv DRUPAL_DB_USER production
SetEnv DRUPAL_DB_PASSWORD [secure-password]
```

These are then read by `settings.php`:
```php
$databases['default']['default'] = [
  'database' => getenv('DRUPAL_DB_NAME'),
  'username' => getenv('DRUPAL_DB_USER'),
  'password' => getenv('DRUPAL_DB_PASSWORD'),
  'host' => getenv('DRUPAL_DB_HOST'),
  // ...
];
```

## Architecture

The infrastructure uses GCP-native technologies for SSL termination, routing, and load balancing:

```
Client (Browser)
    ↓
[1] Forwarding Rule (34.111.215.251:443)
    ↓ "Incoming HTTPS traffic"
    ↓
[2] Target HTTPS Proxy (interledger-org-target-proxy)
    ↓ "SSL/TLS termination"
    ↓ Certificate Map → selects certificate based on SNI hostname
    ↓
[3] URL Map (interledger-org)
    ↓ "Route based on hostname/path"
    ↓
    ├─ interledger.org
    │   ├─ /developers → nginx-rewrite-backend (Cloud Run + CDN)
    │   └─ /* → drupal-sites-backend (GCE VM)
    ├─ staging.interledger.org
    │   ├─ /developers → nginx-rewrite-backend (Cloud Run + CDN)
    │   └─ /* → drupal-sites-backend (GCE VM)
    ├─ uwa.interledger.org → umami-analytics-backend (Cloud Run)

    ↓
[4] Backend Services
    ↓
    ├─ drupal-sites-backend → staging-interledger-ig (unmanaged instance group)
    │                          └─ interledger-org-drupal VM (Apache vhosts)
    │                             ├─ /var/www/production (interledger.org)
    │                             └─ /var/www/staging (staging.interledger.org)
    ├─ nginx-rewrite-backend → Cloud Run NEG
    └─ umami-analytics-backend → Cloud Run NEG
```

**Key architectural decisions:**

- **Single Backend for Both Environments**: Both production and staging use `drupal-sites-backend` because a VM instance can only belong to one load-balanced instance group
- **Apache Virtual Hosts**: The VM's Apache configuration differentiates between production and staging based on the `Host` header
- **Shared Infrastructure**: The same VM, Cloud SQL instance, and load balancer serve both environments
- **Path-Based Routing**: The `/developers` path is routed to a separate Cloud Run service for both environments

### SSL Certificates

We use GCP Certificate Manager with DNS authorization. Certificates are mapped to hostnames via a Certificate Map, which allows multiple certificates to coexist and be selected based on SNI.

### URL Routing

The URL Map (`interledger-org`) is the central routing component. It uses:
- **Host matchers**: Route by hostname (e.g., `interledger.org`, `staging.interledger.org`)
- **Path matchers**: Route by path within a hostname (e.g., `/developers`)
- **Route rules**: Priority-based routing with flexible matching

### Health Checks

The load balancer performs health checks on the GCE instance:
- **Default Apache Site**: A dedicated default virtual host responds to health check requests
- **Health Check Path**: `/` on the default site returns 200 OK
- **Purpose**: Ensures traffic is only routed to healthy instances
- **Configuration**: Apache serves a simple response for health check requests without a valid hostname header

## The /developers Portal

The developers portal (`https://interledger.org/developers` and `https://staging.interledger.org/developers`) is a static Astro site served via nginx on Cloud Run.

**Note**: Unlike the main Drupal sites which run on the GCE VM, the developers portal continues to use Cloud Run for its static content delivery.

### Architecture

1. **Source**: `interledger.org-developers` GitHub repository
2. **Build**: Astro builds static site in GitHub Actions
3. **Storage**: Synced to `gs://interledger-org-developers-portal/developers`
4. **Container**: Multi-stage Docker build fetches content from GCS at build time
5. **Serving**: nginx on Cloud Run serves baked-in static files
6. **Caching**: Cloud CDN enabled (1hr TTL for HTML, 24hr max)
7. **Routing**: Both production and staging `/developers` paths route to the same Cloud Run service

### Deployment Flow

```
GitHub Push to main
  ↓
GitHub Actions (.github/workflows/deploy_gcs.yml)
  ↓
  1. Build Astro site (bun run build)
  2. Sync to GCS bucket
  3. Build nginx container (fetches from GCS)
  4. Deploy to Cloud Run (nginx-rewrite service)
  5. Invalidate CDN cache (/developers/*)
  ↓
interledger.org/developers
staging.interledger.org/developers
```

### Nginx Configuration

Simple configuration using standard nginx patterns:
- **Root**: `/usr/share/nginx/html` (contains `developers/` folder)
- **try_files**: `$uri $uri/ $uri/index.html =404` for pretty URLs
- **Redirects**: `/developers` → `/developers/` (301)
- **absolute_redirect off**: Ensures relative redirects for load balancer compatibility

See `interledger.org-developers/ci/nginx-rewrite/` for nginx config and Dockerfile.

## Common Operations

### Manually Invalidate CDN Cache

To force immediate cache refresh (e.g., after hotfix):

```bash
# Invalidate all /developers content
gcloud compute url-maps invalidate-cdn-cache interledger-org \
  --path "/developers/*" \
  --async

# Invalidate specific path
gcloud compute url-maps invalidate-cdn-cache interledger-org \
  --path "/developers/blog/index.html" \
  --async

# Check invalidation status
gcloud compute operations list \
  --filter="operationType:compute.urlMaps.invalidateCache" \
  --limit=5
```

**Note**: Cache invalidation is automatically triggered by the GitHub Actions deployment workflow.

### Update URL Map

The URL Map defines all routing rules. The current configuration is stored in `ci/deploy/urlmap.yaml`.

#### Make Changes to URL Map

1. **Edit the local file**: Modify `ci/deploy/urlmap.yaml` as needed
   - Add/remove hostRules for new domains
   - Add/remove pathMatchers for routing logic
   - Add/remove routeRules for path-based routing

2. **Import the updated configuration**:
   ```bash
   gcloud compute url-maps import interledger-org --source=ci/deploy/urlmap.yaml --quiet
   ```

3. **Verify changes**:
   ```bash
   gcloud compute url-maps describe interledger-org --format='yaml(hostRules,pathMatchers)'
   ```

4. **Update the local file** (if you made changes directly in GCP):
   ```bash
   gcloud compute url-maps export interledger-org --destination=ci/deploy/urlmap.yaml
   # Clean up read-only fields manually (creationTimestamp, fingerprint, id, kind, selfLink)
   ```

**Important Notes**:
- Route rules are evaluated by priority (lower numbers first)
- The local `urlmap.yaml` file has read-only fields removed for easy editing
- Always test changes before applying to production traffic

### Add a New Cloud Run Service

To add a new Cloud Run service to the load balancer (similar to Umami or the developers portal):

#### 1. Deploy Your Service

```bash
gcloud run deploy my-service \
  --image gcr.io/interledger-websites/my-service:latest \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --port 8080
```

#### 2. Create Serverless NEG

```bash
gcloud compute network-endpoint-groups create my-service-neg \
  --region=us-central1 \
  --network-endpoint-type=serverless \
  --cloud-run-service=my-service
```

#### 3. Create Backend Service

```bash
gcloud compute backend-services create my-service-backend \
  --global \
  --load-balancing-scheme=EXTERNAL_MANAGED \
  --protocol=HTTP
```

#### 4. Add NEG to Backend

```bash
gcloud compute backend-services add-backend my-service-backend \
  --global \
  --network-endpoint-group=my-service-neg \
  --network-endpoint-group-region=us-central1
```

#### 5. (Optional) Enable CDN

```bash
gcloud compute backend-services update my-service-backend \
  --global \
  --enable-cdn \
  --cache-mode=CACHE_ALL_STATIC \
  --default-ttl=3600 \
  --max-ttl=86400 \
  --client-ttl=3600
```

#### 6. Update URL Map

Edit `ci/deploy/urlmap.yaml` to add routing rules for your new service.

**Example: Add path-based routing to existing domain**

```yaml
pathMatchers:
- defaultService: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/drupal-sites-backend
  name: staging-matcher
  routeRules:
  - priority: 1
    matchRules:
    - prefixMatch: /my-path
    service: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/my-service-backend
  - priority: 2
    matchRules:
    - prefixMatch: /developers
    service: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/nginx-rewrite-backend
```

**Example: Add new hostname**

```yaml
hostRules:
- hosts:
  - my-new-domain.interledger.org
  pathMatcher: my-service-matcher

pathMatchers:
- defaultService: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/my-service-backend
  name: my-service-matcher
```

Then import the changes:
```bash
gcloud compute url-maps import interledger-org --source=ci/deploy/urlmap.yaml --quiet
```

#### 7. Test

```bash
curl -I https://staging.interledger.org/my-path
# or
curl -I https://my-new-domain.interledger.org
```

### Working with the GCE VM

To access and manage the Drupal installations on the VM:

#### SSH Access

```bash
# SSH to the VM
gcloud compute ssh interledger-org-drupal --zone=us-east1-b

# Or use deployer user
ssh deployer@34.23.109.31
```

#### File Locations

- **Production**: `/var/www/production`
- **Staging**: `/var/www/staging`
- **Apache Config**: `/etc/apache2/sites-available/`
- **Apache Logs**: `/var/log/apache2/`

#### Common VM Tasks

```bash
# Restart Apache
sudo systemctl restart apache2

# View Apache logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# Check Apache configuration
sudo apache2ctl configtest

# Set file permissions (after file uploads/changes)
sudo chown -R www-data:www-data /var/www/production/web/sites/default/files/
sudo chmod -R 775 /var/www/production/web/sites/default/files/
```

#### Using Drush on the VM

Convenience scripts are provided in the `deployer` user's home directory to simplify running Drush commands:

**For staging:**
```bash
# Run any drush command
~/staging-drush.sh [command] [arguments]

# Examples:
~/staging-drush.sh cr                    # Clear cache
~/staging-drush.sh status                # Check site status
~/staging-drush.sh updb -y              # Run database updates
~/staging-drush.sh cex -y               # Export configuration
```

**For production:**
```bash
# Run any drush command
~/production-drush.sh [command] [arguments]

# Examples:
~/production-drush.sh cr                 # Clear cache
~/production-drush.sh status             # Check site status
```

**What these scripts do:**
1. Set correct file permissions before running Drush
2. Run Drush as `www-data` user (same as Apache)
3. Supply database credentials via environment variables
4. Set correct site URI for Drupal
5. Reset file permissions after running Drush

**Script structure:**
```bash
#!/bin/bash
# Fix permissions before drush
sudo chown -R www-data:www-data /var/www/staging/web/sites/default/files/
sudo chmod -R 775 /var/www/staging/web/sites/default/files/

# Run drush with environment variables
cd /var/www/staging/
sudo -u www-data env \
   DRUPAL_DB_HOST=127.0.0.1 \
   DRUPAL_DB_NAME=interledger_org_staging \
   DRUPAL_DB_USER=staging \
   DRUPAL_DB_PASSWORD='[password]' \
   ./vendor/bin/drush --uri="https://staging.interledger.org" "$@"

# Fix permissions after drush
sudo chown -R www-data:www-data /var/www/staging/web/sites/default/files/
sudo chmod -R 775 /var/www/staging/web/sites/default/files/
```

**Note**: These scripts pass all arguments through to Drush using `"$@"`, so you can use them exactly like you would use Drush directly.

## Backend Services

Current backend services in use:

- **`drupal-sites-backend`**: Serves both production and staging Drupal sites
  - Backend: `staging-interledger-ig` (unmanaged instance group containing `interledger-org-drupal` VM)
  - Health Check: `staging-interledger-health` (HTTP on port 80)
  - CDN: Enabled (cache-mode: CACHE_ALL_STATIC, 1hr default TTL)
  
- **`nginx-rewrite-backend`**: Serves the `/developers` portal
  - Backend: Cloud Run serverless NEG (`nginx-rewrite` service)
  - CDN: Enabled
  
- **`umami-analytics-backend`**: Serves Umami analytics
  - Backend: Cloud Run serverless NEG (`umami-analytics` service)

**Note**: The instance group is named `staging-interledger-ig` for historical reasons, but it serves both environments.

## Files in This Directory

- **`urlmap.yaml`**: Current URL map configuration
  - Export latest: `gcloud compute url-maps export interledger-org --destination=ci/deploy/urlmap.yaml`
  - Import changes: `gcloud compute url-maps import interledger-org --source=ci/deploy/urlmap.yaml --quiet`
  - Read-only fields removed (creationTimestamp, fingerprint, id, kind, selfLink)
  
- **`staging/`**: Staging environment configuration files
  - `settings.php`, `htaccess`, `robots.txt`, `cleanup.sh`
  
- **`production/`**: Production environment configuration files  
  - `settings.php`, `htaccess`, `robots.txt`, `cleanup.sh`

## Troubleshooting

### Changes Not Visible

1. **Check Drupal cache**: Clear Drupal cache on the VM
   ```bash
   ssh deployer@34.23.109.31
   ~/production-drush.sh cr   # or ~/staging-drush.sh cr
   ```
2. **Check CDN cache**: Invalidate cache manually (see above) for `/developers` content
3. **Verify URL map**: `gcloud compute url-maps describe interledger-org`
4. **Check backend health**: `gcloud compute backend-services get-health <backend-name> --global`
5. **Review Apache logs**: `sudo tail -f /var/log/apache2/error.log` on the VM
6. **Review Cloud Run logs**: `gcloud run services logs read nginx-rewrite --region us-central1` (for /developers)

### 502/504 Errors

**For Drupal sites (production/staging):**
- VM may be unresponsive or Apache may be down
- Check VM status: `gcloud compute instances describe interledger-org-drupal --zone=us-east1-b`
- SSH to VM and check Apache: `sudo systemctl status apache2`
- Review Apache error logs: `sudo tail -f /var/log/apache2/error.log`
- Check database connectivity to Cloud SQL

**For Cloud Run services (developers portal, Umami):**
- Service may be down or timing out
- Check memory/CPU limits: `gcloud run services describe <service-name> --region us-central1`
- Review Cloud Run logs for errors

### Health Check Failures

- Verify default Apache site is configured and responding
- Check health check settings in backend service configuration
- SSH to VM and test: `curl -I localhost`
- Review Apache configuration: `sudo apache2ctl -S`

### SSL Certificate Issues

- Verify certificate map: `gcloud certificate-manager maps describe interledger-org-cert-map`
- Check certificate status: `gcloud certificate-manager certificates list`
- DNS propagation can take 24-48 hours for new certificates

### Deployment Issues

- Check GitHub Actions workflow logs
- Verify deployer user has correct permissions on VM
- Ensure rsync is working: test manual rsync to VM
- Check that settings.php has correct database credentials
