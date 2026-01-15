<?php

namespace Drupal\tamper\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Tamper attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\Tamper.
 *
 * Tamperers handle the tampering of data. Each Tamper plugin accepts an input
 * value, manipulates it and then returns an output value.
 *
 * @see \Drupal\tamper\TamperPluginManager
 * @see \Drupal\tamper\TamperInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Tamper extends Plugin {

  /**
   * Constructs a Tamper attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the tamper plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A short description of the tamper plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $category
   *   (optional) The category for listing the tamper plugin in the UI.
   * @param bool $handle_multiples
   *   Whether the plugin handles multiples itself.
   *   Typically, plugins where handle_multiples is TRUE will expect an array
   *   as input and iterate over it themselves, changing the whole array.
   * @param string $itemUsage
   *   Describes how (or whether) the plugin uses a tamperable item.
   *   Allowed values:
   *   - "required": The plugin cannot function without an item.
   *   - "optional": The plugin can use an item, but works without it.
   *   - "ignored": The plugin completely ignores the item.
   *   - null / omitted: Not specified (default behavior).
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly ?TranslatableMarkup $category = NULL,
    public readonly bool $handle_multiples = FALSE,
    public readonly ?string $itemUsage = NULL,
  ) {}

}
