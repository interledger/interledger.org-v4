<?php

namespace Drupal\tamper;

/**
 * Provides convenience methods for accessing the itemUsage plugin definition.
 *
 * This trait can be used by Tamper plugins to easily check whether they
 * require, optionally use, or ignore a tamperable item (an object implementing
 * \Drupal\tamper\TamperableItemInterface).
 *
 * The method getUsedSourceProperties() allows a Tamper plugin to specify which
 * item properties it uses. This information is required by systems that support
 * lazy loading of item properties.
 */
trait ItemUsageTrait {

  /**
   * Returns the raw "itemUsage" value from the plugin definition.
   *
   * @return string|null
   *   The value from the plugin definition, or NULL if not specified.
   *   Allowed values are defined in \Drupal\tamper\ItemUsage.
   */
  public function getItemUsage(): ?string {
    return $this->pluginDefinition['itemUsage'] ?? NULL;
  }

  /**
   * Returns the effective "itemUsage" value, applying the default if NULL.
   *
   * @return string
   *   One of the ItemUsage::* constants. Defaults to ItemUsage::OPTIONAL
   *   if the plugin definition does not specify a value.
   */
  public function getEffectiveItemUsage(): string {
    return $this->getItemUsage() ?? ItemUsage::OPTIONAL;
  }

  /**
   * Checks whether this plugin requires a tamperable item to function.
   *
   * @return bool
   *   TRUE if the plugin requires an item, FALSE otherwise.
   */
  public function requiresItem(): bool {
    return $this->getEffectiveItemUsage() === ItemUsage::REQUIRED;
  }

  /**
   * Checks whether this plugin uses a tamperable item.
   *
   * This method returns TRUE for both "required" and "optional" usage types.
   *
   * @return bool
   *   TRUE if the plugin either requires or optionally uses an item,
   *   FALSE if it ignores it entirely.
   */
  public function usesItem(): bool {
    $usage = $this->getEffectiveItemUsage();
    return $usage === ItemUsage::REQUIRED || $usage === ItemUsage::OPTIONAL;
  }

  /**
   * Checks whether this plugin ignores any tamperable item provided.
   *
   * @return bool
   *   TRUE if the plugin ignores the item completely, FALSE otherwise.
   */
  public function ignoresItem(): bool {
    return $this->getEffectiveItemUsage() === ItemUsage::IGNORED;
  }

  /**
   * Gets the source properties from the item that are used by this plugin.
   *
   * @param \Drupal\tamper\TamperableItemInterface $item
   *   The tamperable item.
   *
   * @return string[]
   *   A list of source properties.
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array {
    // By default, we assume that a plugin uses no properties from the item.
    return [];
  }

}
