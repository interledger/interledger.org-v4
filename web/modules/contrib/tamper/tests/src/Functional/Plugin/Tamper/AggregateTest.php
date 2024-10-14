<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the aggregate plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Aggregate
 * @group tamper
 */
class AggregateTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'aggregate';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Function field is required.',
        ],
      ],
      'with values' => [
        'expected' => [
          'function' => 'max',
          'count' => NULL,
        ],
        'edit' => [
          'function' => 'max',
        ],
      ],
      'count' => [
        'expected' => [
          'function' => 'count',
          'count' => 'normal',
        ],
        'edit' => [
          'function' => 'count',
        ],
      ],
      'count recursive' => [
        'expected' => [
          'function' => 'count',
          'count' => 'recursive',
        ],
        'edit' => [
          'function' => 'count',
          'count' => 'recursive',
        ],
      ],
    ];
  }

}
