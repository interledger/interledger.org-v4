<?php

namespace Drupal\tamper\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Tamper annotation object.
 *
 * Tamperers handle the tampering of data.
 *
 * @Annotation
 *
 * @see \Drupal\tamper\TamperPluginManager
 * @see \Drupal\tamper\TamperInterface
 */
class Tamper extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the tamper plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the tamper plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The category under which the tamper plugin should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * Whether the plugin handles multiples itself.
   *
   * Typically plugins that have handle_multiples as TRUE will expect an array
   * as input and iterate over it themselves, changing the whole array.
   *
   * @var bool
   */
  public $handle_multiples = FALSE;

  /**
   * Describes how (or whether) the plugin uses a tamperable item.
   *
   * Allowed values:
   * - "required": The plugin cannot function without an item.
   * - "optional": The plugin can use an item, but works without it.
   * - "ignored": The plugin completely ignores the item.
   * - null / omitted: Not specified (default behavior).
   *
   * @var string|null
   */
  public $itemUsage = NULL;

}
