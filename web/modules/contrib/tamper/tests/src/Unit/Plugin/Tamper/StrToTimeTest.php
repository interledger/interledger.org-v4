<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StrToTime;

/**
 * Tests the strtotime plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrToTime
 * @group tamper
 */
class StrToTimeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StrToTime([], 'strtotime', [], $this->getMockSourceDefinition());
  }

  /**
   * Test converting string to time.
   *
   * @covers ::tamper
   */
  public function testStrToTimeFormat() {
    $this->assertEquals(515995200, $this->plugin->tamper('1986-05-09 04:00:00 GMT'));
    $this->assertEquals(515995200, $this->plugin->tamper('May 9, 1986 04:00:00 GMT'));
    $this->assertEquals(515995200, $this->plugin->tamper('Fri, 09 May 1986 04:00:00 GMT'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper(new \stdClass());
  }

  /**
   * Test converting string to time.
   *
   * @covers ::tamper
   */
  public function testCustomDateFormat() {
    // Parses as the 1st of December 2010 by default.
    $this->assertEquals(1291161600, $this->plugin->tamper('12/01/2010 UTC'));
    $this->plugin->setConfiguration([
      'date_format' => 'd/m/Y T',
    ]);
    // Parses as the 12th of January 2010.
    $this->assertEquals(1263254400, $this->plugin->tamper('12/01/2010 UTC'));
  }

  /**
   * Test converting string to time.
   *
   * @covers ::tamper
   */
  public function testCustomDateFormatWithFallback() {
    // Parses as the 1st of December 2010 by default.
    $this->assertEquals(1291161600, $this->plugin->tamper('12/01/2010 UTC'));
    $this->plugin->setConfiguration([
      'date_format' => 'd/m/Y T',
      'fallback' => TRUE,
    ]);
    // Parses as the 12th of January 2010.
    $this->assertEquals(1263254400, $this->plugin->tamper('12/01/2010 UTC'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidDateFormat() {
    $this->expectException(TamperException::class);
    $this->plugin->setConfiguration([
      'date_format' => 'd/m/y',
    ]);
    // Attempt to parse using an incorrect format.
    $this->plugin->tamper('12-01-2010');
  }

  /**
   * @covers ::tamper
   */
  public function testIncompatibleDateFormatWithFallback() {
    $this->plugin->setConfiguration([
      'date_format' => 'd/m/y',
      'fallback' => TRUE,
    ]);
    $this->assertEquals(1263214800, $this->plugin->tamper('12-01-2010'));
  }

}
