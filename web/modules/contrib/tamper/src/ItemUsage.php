<?php

namespace Drupal\tamper;

/**
 * Enum-style constants describing how a plugin uses a tamperable item.
 */
final class ItemUsage {

  /**
   * Must receive a tamperable item.
   */
  public const REQUIRED = 'required';

  /**
   * Uses item if provided, but works without.
   */
  public const OPTIONAL = 'optional';

  /**
   * Item can be given but is never used.
   */
  public const IGNORED = 'ignored';

  /**
   * Whether or not the item is used, is unknown.
   */
  public const UNSPECIFIED = NULL;

  /**
   * Returns a list of allowed values.
   *
   * @return string[]
   *   A list of possible values for item usage.
   */
  public static function cases(): array {
    return [
      self::REQUIRED,
      self::OPTIONAL,
      self::IGNORED,
    ];
  }

}
