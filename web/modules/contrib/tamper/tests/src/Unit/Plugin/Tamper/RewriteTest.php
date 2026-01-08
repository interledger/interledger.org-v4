<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Rewrite;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperItem;

/**
 * Tests the rewrite plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Rewrite
 * @group tamper
 */
class RewriteTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Rewrite::SETTING_TEXT => '[title] - [body]',
    ];
    return new Rewrite($config, 'rewrite', [], $this->getMockSourceDefinition());
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
   * Tests the rewrite functionality.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param mixed $input
   *   The input data.
   * @param array $config
   *   The config for the plugin.
   * @param array $item_data
   *   The item data.
   *
   * @dataProvider rewriteProvider
   */
  public function testRewrite($expected, $input, array $config, array $item_data) {
    $this->plugin->setConfiguration($config);
    $item = new TamperItem();
    foreach ($item_data as $key => $value) {
      $item->setSourceProperty($key, $value);
    }
    $this->assertSame($expected, $this->plugin->tamper($input, $item));
  }

  /**
   * Data provider for testRewrite().
   */
  public static function rewriteProvider(): array {
    return [
      'simple' => [
        'expected' => 'Yay title! - Yay body!',
        'input' => 'bar',
        'config' => [
          Rewrite::SETTING_TEXT => '[title] - [body]',
        ],
        'item_data' => [
          'title' => 'Yay title!',
          'body' => 'Yay body!',
          'foo' => 'bar',
        ],
      ],
      'array' => [
        'expected' => [
          'foo.jpg',
          'bar.jpg',
          'qux.jpg',
        ],
        'input' => [
          'foo',
          'bar',
          'qux',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[array].jpg',
        ],
        'item_data' => [
          'array' => [
            'foo',
            'bar',
            'qux',
          ],
        ],
      ],
      'indexed array' => [
        'expected' => [
          'foo' => 'foo1.jpg',
          'bar' => 'bar1.jpg',
          'qux' => 'qux1.jpg',
        ],
        'input' => [
          'foo' => 'foo1',
          'bar' => 'bar1',
          'qux' => 'qux1',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[indexed-array].jpg',
        ],
        'item_data' => [
          'indexed-array' => [
            'foo' => 'foo1',
            'bar' => 'bar1',
            'qux' => 'qux1',
          ],
        ],
      ],
      'combined array' => [
        'expected' => [
          'foo.jpg',
          'bar.png',
          'qux.gif',
        ],
        'input' => [
          'foo',
          'bar',
          'qux',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[array].[ext]',
        ],
        'item_data' => [
          'array' => [
            'foo',
            'bar',
            'qux',
          ],
          'ext' => [
            'jpg',
            'png',
            'gif',
          ],
        ],
      ],
      'missing value for a token: fallback to empty string' => [
        'expected' => ' - Yay body!',
        'input' => 'bar',
        'config' => [
          Rewrite::SETTING_TEXT => '[title] - [body]',
        ],
        'item_data' => [
          'label' => 'Yay title!',
          'body' => 'Yay body!',
          'foo' => 'bar',
        ],
      ],
      'self' => [
        'expected' => 'We went to the bar.',
        'input' => 'bar',
        'config' => [
          Rewrite::SETTING_TEXT => 'We went to the [_self].',
        ],
        'item_data' => [],
      ],
      'self multiple' => [
        'expected' => [
          'The cat went to the bar.',
          'The dog went to the bar.',
        ],
        'input' => [
          'The cat',
          'The dog',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[_self] went to the [foo].',
        ],
        'item_data' => [
          'foo' => 'bar',
        ],
      ],
      'nested values: simple' => [
        'expected' => 'Project manager: John Doe',
        'input' => '',
        'config' => [
          Rewrite::SETTING_TEXT => '[job.title]: [name.first] [name.last]',
        ],
        'item_data' => [
          'title' => '',
          'name' => [
            'first' => 'John',
            'last' => 'Doe',
          ],
          'job' => [
            'title' => 'Project manager',
          ],
        ],
      ],
      'nested values: multiple' => [
        'expected' => [
          'A better workflow (DevOps)',
          'Version management with git (DevOps)',
          'Automation (DevOps)',
        ],
        'input' => [
          'A better workflow',
          'Version management with git',
          'Automation',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[articles] ([metadata.category])',
        ],
        'item_data' => [
          'articles' => [
            'A better workflow',
            'Version management with git',
            'Automation',
          ],
          'metadata' => [
            'category' => 'DevOps',
          ],
        ],
      ],
      'nested values: merge student names and grades' => [
        'expected' => [
          'Alice: 8',
          'Bob: 7',
          'Carol: 9',
        ],
        'input' => [
          'Alice',
          'Bob',
          'Carol',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[students]: [data.grades]',
        ],
        'item_data' => [
          'students' => [
            'Alice',
            'Bob',
            'Carol',
          ],
          'data' => [
            'grades' => [
              '8',
              '7',
              '9',
            ],
          ],
        ],
      ],
      'nested values: keyed replacement' => [
        'expected' => [
          'A1' => 'Laptop - €999',
          'B2' => 'Tablet - €499',
          'C3' => 'Smartphone - €799',
        ],
        'input' => [
          'A1' => 'Laptop',
          'B2' => 'Tablet',
          'C3' => 'Smartphone',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[products] - €[pricing.{key}.price]',
        ],
        'item_data' => [
          'products' => [
            'A1' => 'Laptop',
            'B2' => 'Tablet',
            'C3' => 'Smartphone',
          ],
          'pricing' => [
            'A1' => ['price' => '999'],
            'B2' => ['price' => '499'],
            'C3' => ['price' => '799'],
          ],
        ],
      ],
      'missing nested key: fallback to empty string' => [
        'expected' => [
          'A1' => 'Laptop - €999',
          'B2' => 'Tablet - €',
          'C3' => 'Smartphone - €799',
        ],
        'input' => [
          'A1' => 'Laptop',
          'B2' => 'Tablet',
          'C3' => 'Smartphone',
        ],
        'config' => [
          Rewrite::SETTING_TEXT => '[products] - €[pricing.{key}.price]',
        ],
        'item_data' => [
          'products' => [
            'A1' => 'Laptop',
            'B2' => 'Tablet',
            'C3' => 'Smartphone',
          ],
          'pricing' => [
            'A1' => ['price' => '999'],
            // 'B2' is missing on purpose.
            'C3' => ['price' => '799'],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests if no rewrite takes place when there's no tamperable item.
   */
  public function testWithoutTamperableItem() {
    $this->assertEquals('foo', $this->instantiatePlugin()->tamper('foo'));
  }

  /**
   * Tests if the Rewrite plugin returns the right used properties.
   *
   * @param string[] $expected
   *   The expected list of used properties.
   * @param string $text_setting
   *   The configured replacement pattern.
   *
   * @covers ::getUsedSourceProperties
   *
   * @dataProvider getUsedSourcePropertiesProvider
   */
  public function testGetUsedSourceProperties(array $expected, string $text_setting) {
    $this->plugin->setConfiguration([
      Rewrite::SETTING_TEXT => $text_setting,
    ]);
    $item = new TamperItem();
    $this->assertSame($expected, $this->plugin->getUsedSourceProperties($item));
  }

  /**
   * Data provider for testGetUsedSourceProperties().
   */
  public static function getUsedSourcePropertiesProvider(): array {
    return [
      'no tokens' => [
        'expected' => [],
        'text_setting' => 'Foo Bar',
      ],
      'single token' => [
        'expected' => ['Foo'],
        'text_setting' => '[Foo]',
      ],
      'two tokens' => [
        'expected' => ['Foo', 'Bar'],
        'text_setting' => '[Foo] - [Bar]',
      ],
      'a token used multiple times' => [
        'expected' => ['Foo'],
        'text_setting' => 'Put the [Foo] in the [Foo]-basket.',
      ],
      'case sensitive tokens' => [
        'expected' => ['Foo', 'foo'],
        'text_setting' => '[Foo] and [foo] are not the same token.',
      ],
      'tokens with nested values' => [
        'expected' => ['articles', 'metadata'],
        'text_setting' => '[articles] ([metadata.category])',
      ],
      'tokens with keyed replacement' => [
        'expected' => ['products', 'pricing'],
        'text_setting' => '[products] - €[pricing.{key}.price]',
      ],
    ];
  }

}
