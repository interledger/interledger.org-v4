# Interledger.org Infrastructure

## Overview

The Interledger websites are served through a Google Cloud Platform (GCP) External HTTPS Load Balancer that routes traffic to various backends based on hostname and path.

### Entrypoints

- **staging.interledger.org**
  - Staging Drupal website
  - Backed by Cloud Run instance and shared Cloud SQL database
  - `/developers` path serves the developers portal (static site via nginx)
  
- **uwa.interledger.org**
  - Umami Analytics instance
  - Backed by Cloud Run

- **interledger.org** (production)
  - **Coming soon** - Production website migration pending
  - Will follow same architecture as staging

## Architecture

The infrastructure uses GCP-native technologies for SSL termination, routing, and caching:

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
    ├─ staging.interledger.org
    │   ├─ /developers → nginx-rewrite-backend (Cloud Run + CDN)
    │   └─ /* → staging-website-backend (Drupal Cloud Run)
    ├─ uwa.interledger.org → umami-analytics-backend

    ↓
[4] Backend Service (with optional Cloud CDN caching)
    ↓
[5] Cloud Run / GCS Bucket / Internet NEG
```

### SSL Certificates

We use GCP Certificate Manager with DNS authorization. Certificates are mapped to hostnames via a Certificate Map, which allows multiple certificates to coexist and be selected based on SNI.

### URL Routing

The URL Map (`interledger-org`) is the central routing component. It uses:
- **Host matchers**: Route by hostname (e.g., `staging.interledger.org`)
- **Path matchers**: Route by path within a hostname (e.g., `/developers`)
- **Route rules**: Priority-based routing with flexible matching

## The /developers Portal

The developers portal (`https://staging.interledger.org/developers`) is a static Astro site served via nginx on Cloud Run.

### Architecture

1. **Source**: `interledger.org-developers` GitHub repository
2. **Build**: Astro builds static site in GitHub Actions
3. **Storage**: Synced to `gs://interledger-org-developers-portal/developers`
4. **Container**: Multi-stage Docker build fetches content from GCS at build time
5. **Serving**: nginx on Cloud Run serves baked-in static files
6. **Caching**: Cloud CDN enabled (1hr TTL for HTML, 24hr max)

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

The URL Map defines all routing rules. To modify:

#### Export Current Configuration

```bash
gcloud compute url-maps describe interledger-org --format=yaml > urlmap-staging.yaml
```

#### Edit the YAML

Remove read-only fields before editing:
- `creationTimestamp`
- `fingerprint`
- `id`
- `kind`
- `selfLink`

Edit the `pathMatchers` and `routeRules` sections as needed.

#### Import Updated Configuration

```bash
gcloud compute url-maps import interledger-org --source=urlmap-staging.yaml --quiet
```

#### Verify Changes

```bash
gcloud compute url-maps describe interledger-org --format='yaml(pathMatchers)'
```

**Important**: Route rules are evaluated by priority (lower numbers first). Ensure your priorities are sequential and non-conflicting.

### Add a New Service

To add a new service to the load balancer:

#### 1. Deploy Your Service

For Cloud Run:
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
  --load-balancing-scheme=EXTERNAL_MANAGED
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

Export, edit, and re-import the URL map (see above) to add routing rules for your new service.

Example addition to `pathMatchers`:

```yaml
- defaultService: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/staging-website-backend
  name: staging-matcher
  routeRules:
  - priority: 1
    matchRules:
    - prefixMatch: /my-path
    service: https://www.googleapis.com/compute/v1/projects/interledger-websites/global/backendServices/my-service-backend
```

Or add a new host matcher for a new domain:

```yaml
hostRules:
- hosts:
  - my-new-domain.interledger.org
  pathMatcher: my-service-matcher
```

#### 7. Test

```bash
curl -I https://staging.interledger.org/my-path
```

## Files in This Directory

- `urlmap-staging.yaml`: Exported URL map configuration (regenerate with `gcloud compute url-maps describe`)

## Troubleshooting

### Changes Not Visible

1. Check CDN cache: Invalidate cache manually (see above)
2. Verify URL map: `gcloud compute url-maps describe interledger-org`
3. Check backend health: `gcloud compute backend-services get-health <backend-name> --global`
4. Review Cloud Run logs: `gcloud run services logs read <service-name> --region us-central1`

### 502/504 Errors

- Cloud Run service may be down or timing out
- Check memory/CPU limits: `gcloud run services describe <service-name> --region us-central1`
- Review Cloud Run logs for errors

### SSL Certificate Issues

- Verify certificate map: `gcloud certificate-manager maps describe interledger-org-cert-map`
- Check certificate status: `gcloud certificate-manager certificates list`
- DNS propagation can take 24-48 hours for new certificates
