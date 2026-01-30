<?php

namespace Drupal\Tests\tamper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperItem;

/**
 * Tests chaining multiple tampers together.
 *
 * @group tamper
 */
class ChainedTamperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tamper'];

  /**
   * Tests the outcome of chaining multiple tamper plugins together.
   *
   * @param mixed $expected
   *   The expected outcome.
   * @param array $item_data
   *   The item data.
   * @param string $source_key
   *   The field to pick from the item.
   * @param array $tampers
   *   (optional) A list of tampers to apply along with their config. Each item
   *   in the array should consist of the following:
   *   - plugin: (string) the ID of the Tamper plugin to apply.
   *   - config: (array) Configuration for the plugin.
   *   - expected_exception: (string, optional) if applying a plugin should
   *     result into an exception, this key should be set to the expected
   *     Exception class.
   *
   * @dataProvider chainedTampersDataProvider
   */
  public function testChainedTampers($expected, array $item_data, string $source_key, array $tampers = []) {
    $manager = \Drupal::service('plugin.manager.tamper');
    $multiple = FALSE;
    $item = new TamperItem();
    foreach ($item_data as $key => $value) {
      $item->setSourceProperty($key, $value);
    }

    // Get the value for a source.
    $value = $item->getSourceProperty($source_key);

    foreach ($tampers as $plugin_data) {
      $plugin_data['config']['source_definition'] = $this->createMock(SourceDefinitionInterface::class);
      $tamper = $manager->createInstance($plugin_data['plugin'], $plugin_data['config']);

      if (isset($plugin_data['expected_exception'])) {
        $this->expectException($plugin_data['expected_exception']);
      }

      $definition = $tamper->getPluginDefinition();
      // Many plugins expect a scalar value but the current value of the
      // pipeline might be multiple scalars (this is set by the previous
      // plugin) and in this case the current value needs to be iterated
      // and each scalar separately transformed.
      if ($multiple && !$definition['handle_multiples']) {
        $new_value = [];
        foreach ($value as $scalar_value) {
          $new_value[] = $tamper->tamper($scalar_value, $item);
        }
        $value = $new_value;
      }
      else {
        $value = $tamper->tamper($value, $item);
        $multiple = $tamper->multiple();
      }

      // Write the changed value.
      $item->setSourceProperty($source_key, $value);
    }

    $this->assertEquals($expected, $item->getSourceProperty($source_key));
  }

  /**
   * Data provider for testChainedTampers().
   */
  public static function chainedTampersDataProvider() {
    return [
      'explode-implode' => [
        'expected' => 'a|b|c',
        'item_data' => [
          'foo' => 'a,b,c',
        ],
        'source_key' => 'foo',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => '|',
            ],
          ],
        ],
      ],
      'explode-double-implode' => [
        'expected' => 'a|b|c',
        'item_data' => [
          'foo' => 'a,b,c',
        ],
        'source_key' => 'foo',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => '|',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => ';',
            ],
          ],
        ],
      ],
      'explode-too-much' => [
        'expected' => NULL,
        'item_data' => [
          'foo' => 'a,b,c',
        ],
        'source_key' => 'foo',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => '|',
            ],
          ],
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => '|',
            ],
          ],
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => '|',
            ],
            'expected_exception' => TamperException::class,
          ],
        ],
      ],
      'multiple-explode' => [
        'expected' => [
          ['a', 'b', 'c'],
          [1, 2],
        ],
        'item_data' => [
          'foo' => 'a,b,c;1,2',
        ],
        'source_key' => 'foo',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ';',
            ],
          ],
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
        ],
      ],
      'replace and rewrite' => [
        'expected' => '(123)456-7890',
        'item_data' => [
          'phone' => '123/456-7890',
        ],
        'source_key' => 'phone',
        'tampers' => [
          [
            'plugin' => 'find_replace',
            'config' => [
              'find' => '/',
              'replace' => ')',
            ],
          ],
          [
            'plugin' => 'rewrite',
            'config' => [
              'text' => '([phone]',
            ],
          ],
        ],
      ],
    ];
  }

}
