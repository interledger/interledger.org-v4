<?php

namespace Drupal\tamper;

/**
 * Declares methods for plugins that define an itemUsage property.
 *
 * Implement this interface (or use ItemUsageTrait) in Tamper plugins
 * that need to specify whether they require, optionally use, or ignore
 * a tamperable item (an object implementing
 * \Drupal\tamper\TamperableItemInterface).
 */
interface ItemUsageInterface {

  /**
   * Returns the raw "itemUsage" value from the plugin definition.
   *
   * @return string|null
   *   The value from the plugin definition, or NULL if not specified.
   *   Allowed values are defined in \Drupal\tamper\ItemUsage.
   */
  public function getItemUsage(): ?string;

  /**
   * Returns the effective "itemUsage" value, applying the default if NULL.
   *
   * @return string
   *   One of the ItemUsage::* constants. Defaults to ItemUsage::OPTIONAL
   *   if the plugin definition does not specify a value.
   */
  public function getEffectiveItemUsage(): string;

  /**
   * Checks whether this plugin requires a tamperable item to function.
   *
   * @return bool
   *   TRUE if the plugin requires an item, FALSE otherwise.
   */
  public function requiresItem(): bool;

  /**
   * Checks whether this plugin uses a tamperable item.
   *
   * @return bool
   *   TRUE if the plugin either requires or optionally uses an item,
   *   FALSE if it ignores it entirely.
   */
  public function usesItem(): bool;

  /**
   * Checks whether this plugin ignores any tamperable item provided.
   *
   * @return bool
   *   TRUE if the plugin ignores the item completely, FALSE otherwise.
   */
  public function ignoresItem(): bool;

  /**
   * Gets the source properties from the item that are used by this plugin.
   *
   * @param \Drupal\tamper\TamperableItemInterface $item
   *   The tamperable item.
   *
   * @return string[]
   *   A list of source properties.
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array;

}
