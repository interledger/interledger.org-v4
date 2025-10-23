<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Database configuration for Docker environment
if (getenv('DRUPAL_DB_HOST')) {
  // Docker environment
  $databases['default']['default'] = array (
    'database' => getenv('DRUPAL_DB_NAME') ?: 'drupal',
    'username' => getenv('DRUPAL_DB_USER') ?: 'drupal',
    'password' => getenv('DRUPAL_DB_PASSWORD') ?: 'drupal123',
    'prefix' => '',
    'host' => getenv('DRUPAL_DB_HOST') ?: 'db',
    'port' => '3306',
    'isolation_level' => 'READ COMMITTED',
    'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
    'driver' => 'mysql',
    'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
  );
} else {
  // Fallback for non-Docker environments
  $databases['default']['default'] = array (
    'database' => 'DB_NAME',
    'username' => 'DB_USER_NAME',
    'password' => 'DB_PASSWORD',
    'prefix' => '',
    'host' => 'localhost',
    'port' => '3306',
    'isolation_level' => 'READ COMMITTED',
    'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
    'driver' => 'mysql',
    'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
  );
}

/**
 * Site configuration
 */
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * Configuration for Docker development environment
 */
if (getenv('DRUPAL_DB_HOST')) {
  // Trusted host settings for Docker
  $settings['trusted_host_patterns'] = [
    '^localhost$',
    '^127\.0\.0\.1$',
    '^.+\.localhost$',
    '^.+\.local$',
  ];
  
  // Note that we are not mounting files and private under the normal web root. This is so that
  // we can safely mount these RW folders from the host without exposing them to the web server directly.
  // We use an Apache alias to serve files from /var/drupal/files at /sites/default/files URL path
  $settings['file_public_path'] = 'sites/default/files';
  $settings['file_private_path'] = '/var/drupal/private';
  
  // PHP storage directory (for compiled PHP code)
  $settings['php_storage']['default'] = [
    'class' => 'Drupal\Component\PhpStorage\FileStorage',
    'directory' => '/tmp/drupal-php',
  ];
  
  // Configuration sync directory
  $settings['config_sync_directory'] = '/var/drupal/files/config';
  
  // Hash salt for security
  $settings['hash_salt'] = 'docker-development-salt-change-in-production';
  
  // Disable CSS and JS aggregation for development
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
  
  // Show all error messages during development
  $config['system.logging']['error_level'] = 'verbose';
  
  // Disable caching for development
  $settings['cache']['bins']['render'] = 'cache.backend.memory';
  $settings['cache']['bins']['page'] = 'cache.backend.memory';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.memory';
  
  // Skip permissions hardening for development
  $settings['skip_permissions_hardening'] = TRUE;
}