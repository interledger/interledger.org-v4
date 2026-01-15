<?php

namespace Drupal\feeds_tamper_test\Plugin\Tamper;

use Drupal\tamper\ItemUsageInterface;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Provides a plugin that can tell which source fields on the item are used.
 *
 * @Tamper(
 *   id = "feeds_tamper_test_source_user",
 *   label = @Translation("Source user"),
 *   description = @Translation("Uses source_context if present."),
 *   category = @Translation("Other")
 * )
 */
class SourceUser extends TamperBase implements ItemUsageInterface {

  const SETTING_USED_SOURCES = 'used_sources';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_USED_SOURCES] = [];
    return $config;
  }

  /**
   * Returns a list of used sources by this plugin.
   *
   * This way, providers know which data possibly needs to be lazy loaded.
   *
   * @param \Drupal\tamper\TamperableItemInterface $item
   *   The tamperable item.
   *
   * @return string[]
   *   A list of source properties.
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array {
    return $this->getSetting(static::SETTING_USED_SOURCES);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    foreach ($this->getSetting(static::SETTING_USED_SOURCES) as $source_field) {
      $data .= ';' . $item->getSourceProperty($source_field);
    }
    return $data;
  }

}
