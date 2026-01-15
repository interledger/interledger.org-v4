# Configuration Management Workflow

This project uses Drupal's configuration management system to track site settings (fields, content types, views, etc.) in code. The active configuration is stored in the `config/` directory at the project root.

## Initial Setup: Syncing with Production (One-Time)

When initializing the configuration directory or when you suspect severe drift, it is best to start by exporting the current production configuration to ensure your local setup matches the live site.

### 1. Export Config on Production Server
We use the `production-drush.sh` convenience script. We export to `/tmp` to avoid permission issues with the `deployer` vs `www-data` users.

```bash
ssh deployer@IP

# 1. Create a temp directory
mkdir -p /tmp/prod-config-export

# 2. Make it writable by everyone (solves www-data write permission isssues)
chmod 777 /tmp/prod-config-export

# 3. Running the export
~/production-drush.sh config:export --destination=/tmp/prod-config-export -y
```

### 2. Download to Local Machine
From your local machine (not inside the SSH session), download the files using `scp`.

```bash
# Navigate to project root
cd /path/to/interledger.org-v4

# Secure copy from the temp folder on the server
scp -r deployer@34.23.109.31:/tmp/prod-config-export/ .
```

### 3. Establish Baseline
Copy these files into your version-controlled config directory.

```bash
# Clean existing config to avoid stale files
rm -rf config/*

# Copy production files
cp -r prod-config-export/* config/
```

### 4. Apply Local Changes
Once you have the production baseline, re-apply your specific changes locally.

```bash
# Example: Disable alt text requirement
drush config:set field.field.media.image.field_media_image settings.alt_field_required 0 -y

# Export just the changes (will modify only the relevant files)
drush config:export --destination=/var/www/html/config -y
```

## Routine Workflow

For regular updates:

1.  **Make changes locally**:
    - **Option A (Drupal Admin UI)**: Log in to your local site (e.g., `ddev login`), navigate to the configuration page (e.g., *Structure > Content types*), and make your changes. Save the form.
    - **Option B (Drush)**: Run `drush config:set ...` commands.
2.  Run `drush config:export --destination=/var/www/html/config -y`.
3.  Commit the changed YAML files to git.
4.  Deploy to Staging/Production.
5.  **Deployment:** The deployment process (via GitHub Actions or `make deploy`) now **automatically** runs:
    - `drush config:import -y` (Configuration import)
    - `drush cr` (Cache rebuild)

6.  **Verify:** Check the changes on the site.

    > [!NOTE]
    > If you need to debug or run it manually:
    > ```bash
    > ssh deployer@34.23.109.31
    > ~/staging-drush.sh config:import -y
    > ```

## Performance Considerations & Future Improvements

Currently, `config:import` runs on every deployment.
- **Efficiency**: Drush first checks for differences. If no configuration files have changed, the operation is very fast (seconds) and effectively skipped.
- **Scaling**: As the site grows, if deployment times become a concern, we may strictly separate code deployment from configuration updates by moving `config:import` to a separate, manually triggered GitHub Action or Makefile target.
