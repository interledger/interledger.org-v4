<?php

namespace Drupal\feeds_tamper_test\Feeds\Source;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Plugin\Type\Source\SourceBase;

/**
 * Provides a simple FeedsSource plugin.
 *
 * @see \Drupal\Tests\feeds_tamper\Kernel\FeedsSourceUsageTest
 *
 * @FeedsSource(
 *   id = "dummy_source",
 *   category = @Translation("Feeds Tamper"),
 * )
 */
class DummySource extends SourceBase {

  /**
   * Keeps track of wether or not this plugin was used.
   */
  public static bool $called = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function sources(array &$sources, FeedTypeInterface $feed_type, array $definition) {
    $sources['dummy_source:source_context'] = [
      'label' => t('Context value'),
      'description' => t('A static context value.'),
      'id' => $definition['id'],
      'type' => (string) $definition['category'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(FeedInterface $feed, ItemInterface $item) {
    self::$called = TRUE;

    [, $field_name] = explode(':', $this->configuration['source']);

    if ($field_name === 'source_context') {
      return 'context_value';
    }

    return NULL;
  }

}
