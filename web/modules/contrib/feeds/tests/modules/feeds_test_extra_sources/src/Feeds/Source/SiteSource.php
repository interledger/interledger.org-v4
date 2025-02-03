<?php

namespace Drupal\feeds_test_extra_sources\Feeds\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Plugin\Type\Source\SourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A source exposing site config.
 *
 * @FeedsSource(
 *   id = "site"
 * )
 */
final class SiteSource extends SourceBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a SiteSource object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function sources(array &$sources, FeedTypeInterface $feed_type, array $definition) {
    $sources['site:name'] = [
      'label' => t('Site name'),
      'id' => $definition['id'],
    ];
    $sources['site:mail'] = [
      'label' => t('Site mail'),
      'id' => $definition['id'],
    ];
    $sources['site:slogan'] = [
      'label' => t('Site slogan'),
      'id' => $definition['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(FeedInterface $feed, ItemInterface $item) {
    [, $field_name] = explode(':', $this->configuration['source']);

    return $this->config->get('system.site')->get($field_name);
  }

}
