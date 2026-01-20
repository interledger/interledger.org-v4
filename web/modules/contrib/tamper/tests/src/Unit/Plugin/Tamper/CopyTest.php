<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\MissingItemException;
use Drupal\tamper\Plugin\Tamper\Copy;
use Drupal\tamper\TamperItem;

/**
 * Tests the copy plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Copy
 * @group tamper
 */
class CopyTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Copy([], 'copy', [], $this->getMockSourceDefinition());
  }

  /**
   * Get a tamper item to use in the test.
   *
   * @return \Drupal\tamper\TamperItem
   *   The tamperable item to use in the test.
   */
  protected function getTamperItem(): TamperItem {
    $item = new TamperItem();
    $item->setSourceProperty('title', 'Robots are cool.');
    $item->setSourceProperty('body', 'Robots are scary!');

    return $item;
  }

  /**
   * Test copy to.
   */
  public function testCopyTo() {
    $config = [
      Copy::SETTING_TO_FROM => 'to',
      Copy::SETTING_SOURCE => 'title',
    ];

    $plugin = new Copy($config, 'copy', [], $this->getMockSourceDefinition());
    $item = $this->getTamperItem();
    $this->assertEquals('Robots are cool.', $item->getSourceProperty('title'));
    // When copying to, the original data stays the same.
    $this->assertEquals('foo', $plugin->tamper('foo', $item));
    // However the source property should have changed.
    $this->assertEquals('foo', $item->getSourceProperty('title'));
  }

  /**
   * Test copy from.
   */
  public function testCopyFrom() {
    $config = [
      Copy::SETTING_TO_FROM => 'from',
      Copy::SETTING_SOURCE => 'title',
    ];

    $plugin = new Copy($config, 'copy', [], $this->getMockSourceDefinition());
    $item = $this->getTamperItem();

    $this->assertEquals('Robots are cool.', $item->getSourceProperty('title'));
    // The return value is now that of the title source.
    $this->assertEquals('Robots are cool.', $plugin->tamper('foo', $item));
    // The title source property has not been changed.
    $this->assertEquals('Robots are cool.', $item->getSourceProperty('title'));
  }

  /**
   * Test the plugin behavior without a tamperable item.
   */
  public function testEmptyTamperableItem() {
    $this->expectException(MissingItemException::class);
    $this->expectExceptionMessage('The copy plugin requires a tamperable item.');
    $this->plugin->tamper('foo');
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    $this->assertEquals(NULL, $this->plugin->tamper(NULL, $this->getTamperItem()));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    $this->assertEquals('', $this->plugin->tamper('', $this->getTamperItem()));
  }

  /**
   * Tests if the Copy plugin returns the right used properties.
   *
   * @covers ::getUsedSourceProperties
   *
   * @dataProvider getUsedSourcePropertiesProvider
   */
  public function testGetUsedSourceProperties(array $expected, array $config) {
    $this->plugin->setConfiguration($config);
    $item = new TamperItem();
    $this->assertSame($expected, $this->plugin->getUsedSourceProperties($item));
  }

  /**
   * Data provider for testGetUsedSourceProperties().
   */
  public static function getUsedSourcePropertiesProvider(): array {
    return [
      'no config' => [
        'expected' => [],
        'config' => [],
      ],
      'configured as to' => [
        'expected' => [],
        'config' => [
          Copy::SETTING_TO_FROM => 'to',
          Copy::SETTING_SOURCE => 'title',
        ],
      ],
      'configured as from' => [
        'expected' => ['title'],
        'config' => [
          Copy::SETTING_TO_FROM => 'from',
          Copy::SETTING_SOURCE => 'title',
        ],
      ],
    ];
  }

}
