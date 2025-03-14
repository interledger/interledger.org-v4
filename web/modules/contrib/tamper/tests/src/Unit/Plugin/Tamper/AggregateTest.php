<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Aggregate;

/**
 * Tests the aggregate plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Aggregate
 * @group tamper
 */
class AggregateTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Aggregate([], 'aggregate', [], $this->getMockSourceDefinition());
  }

  /**
   * Instantiates the plugin with the given configuration.
   *
   * @param array $config
   *   The config to set.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Aggregate
   *   An instance of the plugin.
   */
  protected function instantiatePluginWithConfig(array $config) {
    return new Aggregate($config, 'aggregate', [], $this->getMockSourceDefinition());
  }

  /**
   * Data sets for each function.
   *
   * @return array
   *   A list of data sets, used by ::functionValues().
   */
  protected static function dataSets(): array {
    return [
      // Example taken from https://en.wikipedia.org/wiki/Average.
      [
        'values' => [1, 2, 2, 3, 4, 7, 9],
        'average' => 4,
        'count' => 7,
        'max' => 9,
        'median' => 3,
        'min' => 1,
        'mode' => 2,
        'range' => 8,
        'sum' => 28,
      ],
      // A single number.
      [
        'values' => [3],
        'average' => 3,
        'count' => 1,
        'max' => 3,
        'median' => 3,
        'min' => 3,
        'mode' => 3,
        // With a single number there is no difference between minimum and
        // maximum.
        'range' => 0,
        'sum' => 3,
      ],
      // Small sets of numbers.
      [
        'values' => [2, 5],
        'average' => 3.5,
        'count' => 2,
        'max' => 5,
        'median' => 3.5,
        'min' => 2,
        // With no single number appearing the most, we pick the first number
        // that appears the most instead.
        'mode' => 2,
        'range' => 3,
        'sum' => 7,
      ],
      // Odd number of numbers in random order.
      [
        'values' => [33, 45, 21, 50, 21, 33, 21],
        'average' => 32,
        'count' => 7,
        'max' => 50,
        'median' => 33,
        'min' => 21,
        'mode' => 21,
        'range' => 29,
        'sum' => 224,
      ],
      // Even number of numbers in random order.
      [
        'values' => [33, 45, 21, 51, 21, 45],
        'average' => 36,
        'count' => 6,
        'max' => 51,
        // On an even sets of numbers, the median is the average of the two
        // middle numbers.
        'median' => 39,
        'min' => 21,
        // With no single number appearing the most, we pick the first number
        // that appears the most instead.
        'mode' => 45,
        'range' => 30,
        'sum' => 216,
      ],
      // Empty array.
      [
        'values' => [],
        'average' => NULL,
        'count' => 0,
        'max' => NULL,
        'median' => NULL,
        'min' => NULL,
        'mode' => NULL,
        'range' => NULL,
        'sum' => 0,
      ],
    ];
  }

  /**
   * Tests calculating the resulting value.
   *
   * @dataProvider functionValues
   */
  public function testFunction(string $function, ?float $expected, array $values) {
    $plugin = $this->instantiatePluginWithConfig([
      Aggregate::SETTING_FUNCTION => $function,
    ]);

    $this->assertEquals($expected, $plugin->tamper($values));
  }

  /**
   * Data provider for ::testFunction().
   */
  public static function functionValues() {
    $functions = [
      'average',
      'count',
      'max',
      'median',
      'min',
      'mode',
      'range',
      'sum',
    ];

    $return = [];
    foreach (static::dataSets() as $set) {
      foreach ($functions as $function) {
        $return[] = [
          'function' => $function,
          'expected' => $set[$function],
          'values' => $set['values'],
        ];
      }
    }
    return $return;
  }

  /**
   * Tests the count function.
   *
   * @dataProvider countValues
   */
  public function testCount(string $mode, float $expected, array $values) {
    $plugin = $this->instantiatePluginWithConfig([
      aggregate::SETTING_FUNCTION => 'count',
      aggregate::SETTING_COUNT => $mode,
    ]);

    $this->assertEquals($expected, $plugin->tamper($values));
  }

  /**
   * Data provider for ::testCount().
   */
  public static function countValues() {
    return [
      [
        'mode' => 'normal',
        'expected' => 3,
        'values' => [
          [0, 1],
          [2, 3],
          [5, 6, 7],
        ],
      ],
      [
        'mode' => 'recursive',
        'expected' => 10,
        'values' => [
          [0, 1],
          [2, 3],
          [5, 6, 7],
        ],
      ],
    ];
  }

  /**
   * Test invalid data throws exception.
   */
  public function testInvalidDataUntouched() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $plugin = $this->instantiatePluginWithConfig([
      Aggregate::SETTING_FUNCTION => 'average',
    ]);
    $plugin->tamper('boo');
  }

}
