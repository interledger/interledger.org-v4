<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Debug: Force log what environment variables we see
error_log("SETTINGS DEBUG: DRUPAL_DB_HOST=" . (getenv('DRUPAL_DB_HOST') ?: 'NOT_SET'));
error_log("SETTINGS DEBUG: DRUPAL_DB_NAME=" . (getenv('DRUPAL_DB_NAME') ?: 'NOT_SET'));
error_log("SETTINGS DEBUG: DRUPAL_DB_USER=" . (getenv('DRUPAL_DB_USER') ?: 'NOT_SET'));

// Database configuration for Docker/Cloud environment
if (getenv('DRUPAL_DB_HOST') || getenv('K_SERVICE')) {
  error_log("DEBUG: DRUPAL_DB_HOST=" . getenv('DRUPAL_DB_HOST'));
  error_log("DEBUG: Inside database configuration block");
  
  // For Cloud Run, use Unix socket connection to Cloud SQL Proxy
  if (getenv('K_SERVICE')) {
    // We're running on Cloud Run, use Unix socket
    $db_unix_socket = '/cloudsql/interledger-websites:us-east1:websites';
    $db_host = 'localhost';  // This will be ignored when unix_socket is set
    $db_port = '';  // Port is not used with Unix sockets
    error_log("DEBUG: Using Cloud Run Unix socket - socket=" . $db_unix_socket);
    
    // Cloud Run environment
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
  } else {
    // VM/Docker environment - use TCP connection
    $db_host = getenv('DRUPAL_DB_HOST') ?: 'localhost';
    $db_port = '3306';
    
    $databases['default']['default'] = array (
      'database' => getenv('DRUPAL_DB_NAME') ?: 'drupal',
      'username' => getenv('DRUPAL_DB_USER') ?: 'drupal',
      'password' => getenv('DRUPAL_DB_PASSWORD') ?: 'drupal123',
      'prefix' => '',
      'host' => $db_host,
      'port' => $db_port,
      'isolation_level' => 'READ COMMITTED',
      'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
      'driver' => 'mysql',
      'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
    );
  }
  
  // Debug: Log the final database configuration
  error_log("DEBUG: Final DB config - host=" . $databases['default']['default']['host'] . 
           " port=" . $databases['default']['default']['port'] . 
           " unix_socket=" . ($databases['default']['default']['unix_socket'] ?: 'NOT_SET') .
           " database=" . $databases['default']['default']['database']);
           
} else {
    error_log("ERROR: No Docker/Cloud database environment variables found, using default settings.");
    exit(1); // Exit with error
}

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
    '^10\.142\.0\.2$', # The internal IP of the Cloud Run instance. Used for health checks
    '^interledger\.org$',
  ];
}
  
// File system paths
$settings['file_public_path'] = '/var/drupal/files';
$settings['file_public_base_url'] = '/sites/default/files';
$settings['file_private_path'] = '/var/drupal/private';

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
