<?php

namespace Drupal\tamper;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Tamper plugin manager.
 */
class TamperManager extends DefaultPluginManager implements TamperManagerInterface {

  /**
   * Constructs a TamperManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // Check if there is support for attributed plugins.
    // @todo Remove BC layer when dropping support for Drupal < 10.2.0.
    if (!class_exists('\Drupal\Component\Plugin\Attribute\Plugin')) {
      // No attribute support yet.
      parent::__construct(
        'Plugin/Tamper',
        $namespaces,
        $module_handler,
        'Drupal\tamper\TamperInterface',
        'Drupal\tamper\Annotation\Tamper',
      );
    }
    else {
      parent::__construct(
        'Plugin/Tamper',
        $namespaces,
        $module_handler,
        'Drupal\tamper\TamperInterface',
        'Drupal\tamper\Attribute\Tamper',
        'Drupal\tamper\Annotation\Tamper',
      );
    }
    $this->alterInfo('tamper_info');
    $this->setCacheBackend($cache_backend, 'tamper_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($configuration, $plugin_id, $plugin_definition, $configuration['source_definition']);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (!array_key_exists('itemUsage', $definition)) {
      $definition['itemUsage'] = NULL;
    }

    // Validate the itemUsage value.
    $this->validateItemUsageValue($definition['itemUsage'], $plugin_id);
  }

  /**
   * Validates if the provided value is a known itemUsage type.
   *
   * @param mixed $value
   *   The value to check.
   * @param string $plugin_id
   *   The ID of the plugin for which the value gets checked.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   In case the value is not correct.
   */
  protected function validateItemUsageValue($value, string $plugin_id) {
    if ($value !== NULL && !in_array($value, ItemUsage::cases(), TRUE)) {
      throw new PluginException(sprintf(
        'Plugin "%s" has invalid itemUsage "%s". Allowed: %s.',
        $plugin_id,
        is_scalar($value) ? $value : gettype($value),
        implode(', ', ItemUsage::cases())
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(?array $definitions = NULL) {
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions());
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\tamper\TamperInterface[]
   *   List of tamper plugins.
   */
  public function getSortedDefinitions(?array $definitions = NULL) {
    // Sort the plugins first by category, then by label.
    $definitions = $definitions ?? $this->getDefinitions();
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['label'], $b['label']);
    });
    return $definitions;
  }

}
