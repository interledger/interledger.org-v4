sudo chmod g+wx /var/www/staging

cd /var/www/staging

# Make all Drush binaries executable
sudo chmod +x vendor/bin/drush
sudo chmod +x vendor/bin/drush.php
sudo chmod +x vendor/drush/drush/drush

# Also ensure www-data can read the entire vendor tree
sudo chown -R deployer:www-data vendor
sudo chmod -R g+rX vendor

# Test
# sudo -u www-data env \
#   DRUPAL_DB_HOST=127.0.0.1 \
#   DRUPAL_DB_NAME=interledger_org_staging \
#   DRUPAL_DB_USER=staging \
#   DRUPAL_DB_PASSWORD='XXXXXXXXXXXXXXXXXXXXXXX' \
#   ./vendor/bin/drush --uri=staging.interledger.org cr