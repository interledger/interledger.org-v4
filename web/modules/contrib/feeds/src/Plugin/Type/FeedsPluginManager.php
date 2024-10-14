<?php

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Plugin\Discovery\OverridableDerivativeDiscoveryDecorator;

/**
 * Manages Feeds plugins.
 */
class FeedsPluginManager extends DefaultPluginManager {

  /**
   * The plugin being managed.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Constructs a new \Drupal\feeds\Plugin\Type\FeedsPluginManager object.
   *
   * @param string $type
   *   The plugin type. Either fetcher, parser, or processor, handler, source,
   *   target, or other.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $type_annotations = [
      'fetcher' => 'Drupal\feeds\Annotation\FeedsFetcher',
      'parser' => 'Drupal\feeds\Annotation\FeedsParser',
      'processor' => 'Drupal\feeds\Annotation\FeedsProcessor',
      'source' => 'Drupal\feeds\Annotation\FeedsSource',
      'custom_source' => 'Drupal\feeds\Annotation\FeedsCustomSource',
      'target' => 'Drupal\feeds\Annotation\FeedsTarget',
    ];

    $this->pluginType = $type;
    $this->subdir = 'Feeds/' . ucfirst($type);
    if ($type == 'custom_source') {
      $this->subdir = 'Feeds/CustomSource';
    }
    $this->discovery = new AnnotatedClassDiscovery($this->subdir, $namespaces, $type_annotations[$type]);
    $this->discovery = new OverridableDerivativeDiscoveryDecorator($this->discovery);
    $this->moduleHandler = $module_handler;
    $this->alterInfo("feeds_{$type}_plugins");
    $this->setCacheBackend($cache_backend, "feeds_{$type}_plugins");
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Add plugin_type key so that we can determine the plugin type later.
    $definition['plugin_type'] = $this->pluginType;

    // If no default form is defined and this plugin implements
    // \Drupal\Core\Plugin\PluginFormInterface, use that for the default form.
    if (!isset($definition['form']['configuration']) && isset($definition['class']) && is_subclass_of($definition['class'], PluginFormInterface::class)) {
      $definition['form']['configuration'] = $definition['class'];
    }
  }

}
