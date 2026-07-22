<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\TimeOffset;

/**
 * Tests the timeoffset plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TimeOffset
 * @group tamper
 */
class TimeOffsetTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new TimeOffset([], 'timeoffset', [], $this->getMockSourceDefinition());
  }

  /**
   * Data provider for testApplyTimeOffset().
   */
  public static function timezoneDataProvider(): \Generator {
    $utc_timezone = new \DateTimeZone('UTC');
    $utc_time = new \DateTime('now', $utc_timezone);
    $current_timestamp = $utc_time->getTimestamp();
    $timezones = [
      'with negative offset' => 'America/Anchorage',
      'with zero offset' => 'UTC',
      'with positive offset' => 'Africa/Cairo',
    ];

    foreach ($timezones as $case => $zone) {
      $offset = (new \DateTimeZone($zone))->getOffset($utc_time);

      yield $case => [
        'expected' => $current_timestamp + $offset,
        'input' => $current_timestamp,
        'config' => [TimeOffset::SETTING_TIMEZONE => $zone],
      ];
    }
  }

  /**
   * Test applying timezone offset.
   *
   * @covers ::tamper
   *
   * @dataProvider timezoneDataProvider
   */
  public function testApplyTimeOffset(int $expected, int $input, array $config) {
    $this->plugin->setConfiguration($config);
    $this->assertEquals($expected, $this->plugin->tamper($input));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->plugin->setConfiguration([
      TimeOffset::SETTING_TIMEZONE => 'UTC',
    ]);
    $this->expectException(TamperException::class);
    $this->plugin->tamper('not a timestamp');
  }

}
