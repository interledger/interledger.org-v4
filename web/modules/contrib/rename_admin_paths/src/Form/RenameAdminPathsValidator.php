<?php

namespace Drupal\rename_admin_paths\Form;

use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathsEventSubscriber;

class RenameAdminPathsValidator {

  /**
   * Force path replacement values to contain only lowercase letters, numbers,
   * and underscores.
   *
   * @param string $value
   *
   * @return boolean
   */
  public static function isValidPath(string $value): bool {
    return (bool) preg_match('~^[a-zA-Z0-9_-]+$~', $value);
  }

  /**
   * Verify users not overwriting with the default path names, could lead to
   * broken routes
   *
   * @param string $value
   *
   * @return bool
   */
  public static function isDefaultPath(string $value): bool {
    return in_array(
      strtolower($value),
      RenameAdminPathsEventSubscriber::ADMIN_PATHS
    );
  }
}
