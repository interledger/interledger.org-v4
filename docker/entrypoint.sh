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

# Wait a moment for mounts to be ready
sleep 2

# Fix permissions on mounted volumes
echo "Setting up permissions..."
chown -R www-data:www-data /var/www/html/web/sites/default/files 2>/dev/null || true
chmod -R 775 /var/www/html/web/sites/default/files 2>/dev/null || true

# Create PHP storage directory if it does not exist
mkdir -p /var/www/html/web/sites/default/files/php 2>/dev/null || true
chown -R www-data:www-data /var/www/html/web/sites/default/files/php 2>/dev/null || true
chmod -R 775 /var/www/html/web/sites/default/files/php 2>/dev/null || true

echo "Starting Apache..."
# Start Apache
exec apache2-foreground