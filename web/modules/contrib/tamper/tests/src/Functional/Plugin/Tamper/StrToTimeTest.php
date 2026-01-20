<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the strtotime plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrToTime
 * @group tamper
 */
class StrToTimeTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'strtotime';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        // There should be no 'date_format' setting set if the format is empty.
        'expected' => [],
        'edit' => [
          'date_format' => '',
        ],
      ],
      'ignore fallback without date_format' => [
        // Fallback should be ignored if no date format was set.
        'expected' => [],
        'edit' => [
          'date_format' => '',
          'fallback' => '1',
        ],
      ],
      'with values' => [
        'expected' => [
          'date_format' => 'd/m/Y',
          'fallback' => FALSE,
        ],
        'edit' => [
          'date_format' => 'd/m/Y',
        ],
      ],
      'with fallback' => [
        'expected' => [
          'date_format' => 'd/m/Y',
          'fallback' => TRUE,
        ],
        'edit' => [
          'date_format' => 'd/m/Y',
          'fallback' => '1',
        ],
      ],
    ];
  }

}
