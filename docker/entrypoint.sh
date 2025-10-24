#!/bin/bash
echo "Starting our Drupal container..."

# Debug environment variables - explicitly redirect to stdout
echo "=== Environment Variables ==="
echo "K_SERVICE: $K_SERVICE"
echo "DRUPAL_DB_HOST: $DRUPAL_DB_HOST"
echo "DRUPAL_DB_NAME: $DRUPAL_DB_NAME"
echo "DRUPAL_DB_USER: $DRUPAL_DB_USER"
echo "ENVIRONMENT: $ENVIRONMENT"
echo "DRUPAL_HASH_SALT: $DRUPAL_HASH_SALT"
echo "==============================="

# Debug file structure
echo "=== File Structure Debug ==="
echo "Contents of /var/www/html:"
ls -la /var/www/html/ || echo "Directory not found"
echo "Contents of /var/www/html/web:"
ls -la /var/www/html/web/ || echo "Directory not found"
echo "Apache config:"
cat /etc/apache2/sites-available/000-default.conf | head -10
echo "========================="

# Wait a moment for mounts to be ready
sleep 2

# Fix permissions on mounted volumes
echo "Setting up permissions..."

chown -R www-data:www-data /var/drupal/files # 2>/dev/null || true
chmod -R 775 /var/drupal/files # 2>/dev/null || true

chown -R www-data:www-data /var/drupal/private # 2>/dev/null || true
chmod -R 775 /var/drupal/private # 2>/dev/null || true

# Create PHP storage directory if it does not exist
mkdir -p /var/drupal/files/php 2>/dev/null || true
chown -R www-data:www-data /var/drupal/files/php 2>/dev/null || true
chmod -R 775 /var/drupal/files/php 2>/dev/null || true

# Create config directory if it does not exist
mkdir -p /var/drupal/files/config 2>/dev/null || true
chown -R www-data:www-data /var/drupal/files/config 2>/dev/null || true
chmod -R 775 /var/drupal/files/config 2>/dev/null || true

# Create symlink from sites/default/files to /var/drupal/files if it doesn't exist
if [ ! -L /var/www/html/web/sites/default/files ]; then
    echo "Creating symlink from /var/www/html/web/sites/default/files to /var/drupal/files..."
    rm -rf /var/www/html/web/sites/default/files 2>/dev/null || true
    ln -sf /var/drupal/files /var/www/html/web/sites/default/files
    echo "Symlink created successfully"
fi

echo "Starting Apache..."
# Start Apache
exec apache2-foreground