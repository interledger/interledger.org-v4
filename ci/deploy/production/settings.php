<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Default configuration
$db_host = getenv('DRUPAL_DB_HOST') ?: 'localhost';
$db_port = '3306';
$db_unix_socket = '';

// Docker/Cloud Run environment
$databases['default']['default'] = array (
  'database' => getenv('DRUPAL_DB_NAME') ?: 'drupal',
  'username' => getenv('DRUPAL_DB_USER') ?: 'drupal',
  'password' => getenv('DRUPAL_DB_PASSWORD') ?: 'drupal123',
  'prefix' => '',
  'host' => $db_host,
  'port' => $db_port,
  'unix_socket' => $db_unix_socket,
  'isolation_level' => 'READ COMMITTED',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);



// Hash salt for security
$settings['hash_salt'] = 'QDtxU5tjEIsxnYw0PyeovfnXD0UmXrY-TC6rNMuxoEmXQirsJL2tE47GLwr69F6UsHNhIohvug';

/**
 * Site configuration
 */
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;
$settings['state_cache'] = TRUE;
$settings['migrate_node_migrate_type_classic'] = FALSE;

/**
 * Configuration for Docker development environment
 */
if (getenv('DRUPAL_DB_HOST')) {
  // Trusted host settings for Docker and Cloud Run
  $settings['trusted_host_patterns'] = [
    '^localhost$',
    '^127\.0\.0\.1$',
    '^.+\.localhost$',
    '^.+\.local$',
    '^.+\.us-east1\.run\.app$',
    '^.+\.us-east1\.run\.app$',    
    '^10\.142\.0\.2$', # The internal IP of the Cloud Run instance. Used for health checks
    '^interledger\.org$',
  ];
}
  
// File system paths
$settings['file_public_path'] = '/var/www/staging/web/sites/default/files';
$settings['file_public_base_url'] = '/sites/default/files';
$settings['file_assets_path'] = 'sites/default/assets';
$settings['file_private_path'] = '/tmp/drupal_private_files';

// PHP storage directory (for compiled PHP code)
// Use /tmp for PHP storage to avoid GCS latency - this needs to be fast local storage
$settings['php_storage']['default'] = [
'class' => 'Drupal\Component\PhpStorage\FileStorage',
'directory' => '/tmp/drupal_php_storage',
];

// Configuration sync directory
$settings['config_sync_directory'] = '/var/drupal/files/config';

// Enable CSS and JS aggregation for production
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

// Hide error messages in production
$config['system.logging']['error_level'] = 'hide';

// Enable caching for production
$settings['cache']['bins']['render'] = 'cache.backend.database';
$settings['cache']['bins']['page'] = 'cache.backend.database';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.database';

// Enable permissions hardening for production
$settings['skip_permissions_hardening'] = FALSE;
