<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\ConvertBoolean;

/**
 * Tests the convert boolean plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ConvertBoolean
 * @group tamper
 */
class ConvertBooleanTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => FALSE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
    ];
    return new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
  }

  /**
   * Test convert to boolean basic functionality.
   */
  public function testConvertBooleanBasicFunctionality() {
    $this->assertTrue($this->plugin->tamper('A'));
    $this->assertTrue($this->plugin->tamper('a'));
    $this->assertFalse($this->plugin->tamper('B'));
    $this->assertFalse($this->plugin->tamper('b'));
    $this->assertSame('No match', $this->plugin->tamper('c'));
    $this->assertSame('No match', $this->plugin->tamper('C'));
  }

  /**
   * Test convert to boolean no match false case.
   */
  public function testConvertBooleanNoMatchFalse() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => FALSE,
      ConvertBoolean::SETTING_NO_MATCH => 'pass',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertTrue($plugin->tamper('A'));
    $this->assertTrue($plugin->tamper('a'));
    $this->assertFalse($plugin->tamper('B'));
    $this->assertFalse($plugin->tamper('b'));
    $this->assertSame('c', $plugin->tamper('c'));
    $this->assertSame('C', $plugin->tamper('C'));
  }

  /**
   * Test convert to boolean no match true case.
   */
  public function testConvertBooleanNoMatchTrue() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertTrue($plugin->tamper('A'));
    $this->assertNotSame(TRUE, $plugin->tamper('a'));
    $this->assertFalse($plugin->tamper('B'));
    $this->assertNotSame(FALSE, $plugin->tamper('b'));
  }

  /**
   * Test convert to boolean no match true case.
   */
  public function testConvertBooleanNoMatchNull() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => NULL,
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertTrue($plugin->tamper('A'));
    $this->assertNull($plugin->tamper('a'));
    $this->assertFalse($plugin->tamper('B'));
    $this->assertNull($plugin->tamper('b'));
    $this->assertNull($plugin->tamper('c'));
    $this->assertNull($plugin->tamper('C'));
  }

  /**
   * Test convert to boolean other text case.
   */
  public function testConvertBooleanOtherText() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => 'other text',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertSame('other text', $plugin->tamper('a'));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    $this->assertSame('No match', $this->plugin->tamper(NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    $this->assertSame('No match', $this->plugin->tamper(''));
  }

}
