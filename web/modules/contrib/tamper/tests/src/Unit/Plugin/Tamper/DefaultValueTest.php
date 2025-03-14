<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\DefaultValue;

/**
 * Tests the default value plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\DefaultValue
 * @group tamper
 */
class DefaultValueTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      DefaultValue::SETTING_DEFAULT_VALUE => 'HEYO!',
      DefaultValue::SETTING_ONLY_IF_EMPTY => FALSE,
    ];

    return new DefaultValue($config, 'default_value', [], $this->getMockSourceDefinition());
  }

  /**
   * Test anything changed to the value, even if value existed before.
   */
  public function testAnythingToDefaultValue() {
    $config = [
      DefaultValue::SETTING_DEFAULT_VALUE => 'HEYO!',
      DefaultValue::SETTING_ONLY_IF_EMPTY => FALSE,
    ];
    $plugin = new DefaultValue($config, 'default_value', [], $this->getMockSourceDefinition());
    $this->assertEquals('HEYO!', $plugin->tamper('hello world'));
    $this->assertEquals('HEYO!', $plugin->tamper(['supercalifragilisticexpialidocious']));
    $this->assertEquals('HEYO!', $plugin->tamper([]));
  }

  /**
   * Test only empty value changed to the default value.
   */
  public function testOnlyIfEmptyToDefaultValue() {
    $config = [
      DefaultValue::SETTING_DEFAULT_VALUE => 'HEYO!',
      DefaultValue::SETTING_ONLY_IF_EMPTY => TRUE,
    ];
    $plugin = new DefaultValue($config, 'default_value', [], $this->getMockSourceDefinition());
    $this->assertEquals('HEYO!', $plugin->tamper([]));
    $this->assertEquals([1], $plugin->tamper([1]));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    $this->assertEquals('HEYO!', $this->plugin->tamper(NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    $this->assertEquals('HEYO!', $this->plugin->tamper(''));
  }

}
