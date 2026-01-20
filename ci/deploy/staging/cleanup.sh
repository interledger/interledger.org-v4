sudo chmod g+wx /var/www/staging

cd /var/www/staging

# Install environment-specific configuration files
if [ -f "settings.php.new" ]; then
	sudo chmod 644 web/sites/default/settings.php 2>/dev/null || true
	sudo mv settings.php.new web/sites/default/settings.php
	echo "Installed settings.php"
fi
if [ -f ".htaccess.new" ]; then
	sudo mv .htaccess.new web/.htaccess
	echo "Installed .htaccess"
fi
if [ -f "robots.txt.new" ]; then
	sudo mv robots.txt.new web/robots.txt
	echo "Installed robots.txt"
fi

# Ensure deployer owns vendor directory for composer install
sudo chown -R deployer:www-data vendor 2>/dev/null || true
sudo chmod -R g+w vendor 2>/dev/null || true

# Install/update composer dependencies
composer install --no-dev --optimize-autoloader

# Make all Drush binaries executable
sudo chmod +x vendor/bin/drush
sudo chmod +x vendor/bin/drush.php
sudo chmod +x vendor/drush/drush/drush

# Set final ownership and permissions for vendor
sudo chown -R deployer:www-data vendor
sudo chmod -R g+rX vendor

# Create private files and config directories
sudo mkdir -p private config
sudo chown -R www-data:www-data private
sudo chmod -R 770 private
sudo chown -R deployer:www-data config
sudo chmod -R 775 config

# Protect sites/default directory (security requirement)
sudo chmod 555 web/sites/default
sudo chmod 444 web/sites/default/settings.php

# Ensure files directory is writable by www-data
sudo chown -R www-data:www-data web/sites/default/files
sudo chmod -R 775 web/sites/default/files

# Rebuild cache after deployment
sudo /home/deployer/staging-drush.sh cr