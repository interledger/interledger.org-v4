<?php

declare(strict_types=1);

namespace Drupal\rename_admin_paths\Form;

use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathsEventSubscriber;

/**
 * Validates renamed admin paths.
 */
class RenameAdminPathsValidator {

  /**
   * Force path replacement values to contain only valid characters.
   *
   * Valid characters are lowercase letters, numbers, and underscores.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if valid.
   */
  public static function isValidPath(string $value): bool {
    return (bool) preg_match('~^[a-zA-Z0-9_-]+$~', $value);
  }

  /**
   * Verify users are not overwriting the default path names.
   *
   * Overwriting the default path names could lead to broken routes.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a default path.
   */
  public static function isDefaultPath(string $value): bool {
    return in_array(
      strtolower($value),
      RenameAdminPathsEventSubscriber::ADMIN_PATHS,
      TRUE
    );
  }

}
