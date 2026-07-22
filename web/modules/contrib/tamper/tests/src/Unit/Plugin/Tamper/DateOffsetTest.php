<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\DateOffset;

/**
 * Tests the dateoffset plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\DateOffset
 * @group tamper
 */
class DateOffsetTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      DateOffset::SETTING_DATE_OFFSET => '+1 month',
    ];
    return new DateOffset($config, 'dateoffset', [], $this->getMockSourceDefinition());
  }

  /**
   * Test applying date offset.
   *
   * @covers ::tamper
   */
  public function testApplyDateOffset() {
    // Sun Mar 29 2026 18:33:24 UTC.
    $current_timestamp = 1774809204;
    // Wed Apr 29 2026 18:33:24 UTC.
    $new_timestamp = 1777487604;
    $this->assertEquals($new_timestamp, $this->plugin->tamper($current_timestamp));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper('not a timestamp');
  }

}
