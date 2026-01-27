<?php

/**
 * @file
 * Traditional hook file to ensure discovery during updates.
 */

use Drupal\admin_audit_trail\Hook\AdminAuditTrailViewsHooks;

/**
 * Implements hook_views_data().
 */
function admin_audit_trail_views_data() {
  // We manually instantiate the class and call the method.
  // This bypasses the Attribute discovery failure during updates.
  $discovery_class = new AdminAuditTrailViewsHooks();
  return $discovery_class->viewsData();
}
