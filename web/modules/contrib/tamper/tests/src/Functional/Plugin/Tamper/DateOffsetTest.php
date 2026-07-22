<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the dateoffset plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\DateOffset
 * @group tamper
 */
class DateOffsetTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'dateoffset';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Offset field is required.',
        ],
      ],
      'with values' => [
        'expected' => [
          'date_offset' => '+1 month',
        ],
        'edit' => [
          'date_offset' => '+1 month',
        ],
      ],
    ];
  }

}
