#!/bin/bash
echo "Starting Drupal container..."

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