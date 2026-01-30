<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Hash;
use Drupal\tamper\TamperableItemInterface;

/**
 * Tests the hash plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Hash
 * @group tamper
 */
class HashTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Hash([], 'hash', [], $this->getMockSourceDefinition());
  }

  /**
   * Get a mock item to use in the test.
   *
   * @return \Drupal\tamper\TamperableItemInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock of a tamperable item to use in the test.
   */
  protected function getMockItem() {
    $item = $this->createMock(TamperableItemInterface::class);
    $item->expects($this->any())
      ->method('getSource')
      ->willReturn([
        'title' => 'Yay title!',
        'body' => 'Yay body!',
        'foo' => 'bar',
      ]);
    return $item;
  }

  /**
   * Tests the default behavior of the hash plugin.
   */
  public function testHash() {
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    // Hash should be generated also if there is an input value.
    $this->assertEquals($hashed_values, $this->plugin->tamper('', $this->getMockItem()));
    $this->assertEquals($hashed_values, $this->plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Tests the hash functionality.
   */
  public function testHashOnlyIfEmpty() {
    $this->plugin->setConfiguration([
      Hash::SETTING_ONLY_IF_EMPTY => TRUE,
    ]);
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    // Hash should only be generated if the input value is empty.
    $this->assertEquals($hashed_values, $this->plugin->tamper('', $this->getMockItem()));
    $this->assertEquals('foo', $this->plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Tests hashing the input value.
   */
  public function testHashInputValue() {
    $this->plugin->setConfiguration([
      Hash::SETTING_DATA_TO_HASH => 'data',
      Hash::SETTING_ONLY_IF_EMPTY => FALSE,
    ]);
    $this->assertEquals('acbd18db4cc2f85cedef654fccc4a4d8', $this->plugin->tamper('foo'));
  }

  /**
   * Tests the plugin behavior without a tamperable item.
   */
  public function testEmptyTamperableItem() {
    $this->assertEquals('d41d8cd98f00b204e9800998ecf8427e', $this->plugin->tamper(''));
  }

  /**
   * Tests without tamperable item, but with a value.
   */
  public function testEmptyTamperableItemButWithValue() {
    $this->plugin->setConfiguration([
      Hash::SETTING_ONLY_IF_EMPTY => TRUE,
    ]);
    $this->assertEquals('foo', $this->plugin->tamper('foo'));
  }

  /**
   * Tests without tamperable item, but with a value and override.
   */
  public function testEmptyTamperableItemButWithValueAndOverride() {
    $this->plugin->setConfiguration([
      Hash::SETTING_ONLY_IF_EMPTY => FALSE,
    ]);
    $this->assertEquals('acbd18db4cc2f85cedef654fccc4a4d8', $this->plugin->tamper('foo'));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    $this->assertEquals('2b0eeb49f0ad7ef475c49c652cc22a3a', $this->plugin->tamper(NULL, $this->getMockItem()));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    $this->assertEquals('2b0eeb49f0ad7ef475c49c652cc22a3a', $this->plugin->tamper('', $this->getMockItem()));
  }

  /**
   * Tests with the old override setting enabled.
   *
   * Hash should be generated also if there is an input value.
   */
  public function testWithDeprecatedOverrideSettingEnabled() {
    $this->plugin->setConfiguration([
      Hash::SETTING_OVERRIDE => TRUE,
    ]);
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    $this->assertEquals($hashed_values, $this->plugin->tamper('', $this->getMockItem()));
    $this->assertEquals($hashed_values, $this->plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Tests with the old override setting disabled.
   *
   * Hash should only be generated if the input value is empty.
   */
  public function testWithDeprecatedOverrideSettingDisabled() {
    $this->plugin->setConfiguration([
      Hash::SETTING_OVERRIDE => FALSE,
    ]);
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    $this->assertEquals($hashed_values, $this->plugin->tamper('', $this->getMockItem()));
    $this->assertEquals('foo', $this->plugin->tamper('foo', $this->getMockItem()));
  }

}
