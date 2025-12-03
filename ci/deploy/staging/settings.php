<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Database configuration from environment variables
$databases['default']['default'] = array (
  'database' => getenv('DRUPAL_DB_NAME') ?: 'drupal',
  'username' => getenv('DRUPAL_DB_USER') ?: 'drupal',
  'password' => getenv('DRUPAL_DB_PASSWORD') ?: 'drupal123',
  'prefix' => '',
  'host' => getenv('DRUPAL_DB_HOST') ?: '127.0.0.1',
  'port' => '3306',
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

// Trusted host settings
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^.+\.localhost$',
  '^.+\.local$',
  '^.+\.us-east1\.run\.app$',
  '^10\.142\.0\.2$',
  '^staging\.interledger\.org$',    
  '^interledger\.org$',
];

// Reverse proxy settings for HTTPS
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = [
  $_SERVER['REMOTE_ADDR'],
];

// Force HTTPS for all URLs
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
  $_SERVER['HTTPS'] = 'on';
}
if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
  $_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_FORWARDED_PORT'];
}
  
// File system paths
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = '/var/www/staging/private';

// Configuration sync directory
$settings['config_sync_directory'] = '/var/www/staging/config';

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

// Configure oEmbed to use staging URLs to avoid X-Frame-Options issues
$config['media.settings']['iframe_domain'] = 'https://staging.interledger.org';