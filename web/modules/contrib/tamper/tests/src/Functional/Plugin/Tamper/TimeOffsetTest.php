<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the timeoffset plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TimeOffset
 * @group tamper
 */
class TimeOffsetTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'timeoffset';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    $timezones = timezone_identifiers_list();
    return [
      'default values' => [
        'expected' => [
          'timezone' => reset($timezones),
        ],
      ],
      'specified values' => [
        'expected' => [
          'timezone' => end($timezones),
        ],
        'edit' => [
          'timezone' => end($timezones),
        ],
      ],
    ];
  }

}
