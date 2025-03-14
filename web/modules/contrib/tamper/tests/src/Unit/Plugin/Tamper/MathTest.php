<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Math;

/**
 * Tests the math plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Math
 * @group tamper
 */
class MathTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Math([], 'math', [], $this->getMockSourceDefinition());
  }

  /**
   * Test addition.
   */
  public function testAddition() {
    $config = [
      Math::SETTING_OPERATION => 'addition',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(4, $plugin->tamper(2));
  }

  /**
   * Tests addition using a decimal value.
   */
  public function testAdditionWithDecimalValue() {
    $config = [
      Math::SETTING_OPERATION => 'addition',
      Math::SETTING_VALUE => 2.312,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(3.772, $plugin->tamper(1.46));
  }

  /**
   * Test addition with weird values cast to int.
   */
  public function testAdditionWithCasting() {
    $config = [
      Math::SETTING_OPERATION => 'addition',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(3, $plugin->tamper(TRUE));
    $this->assertEquals(2, $plugin->tamper(FALSE));
    $this->assertEquals(2, $plugin->tamper(NULL));
  }

  /**
   * Test subtraction.
   */
  public function testSubtraction() {
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(0, $plugin->tamper(2));
  }

  /**
   * Test multiplication.
   */
  public function testMultiplication() {
    $config = [
      Math::SETTING_OPERATION => 'multiplication',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(4, $plugin->tamper(2));
  }

  /**
   * Test division.
   */
  public function testDivision() {
    $config = [
      Math::SETTING_OPERATION => 'division',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(1, $plugin->tamper(2));
  }

  /**
   * Test flip out with division.
   */
  public function testFlipDivision() {
    $config = [
      Math::SETTING_OPERATION => 'division',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(3 / 2, $plugin->tamper(2));
  }

  /**
   * Test flip out with subtraction.
   */
  public function testFlipSubtraction() {
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(1, $plugin->tamper(2));
  }

  /**
   * Test invalid data throws exception.
   */
  public function testInvalidDataUntouched() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Math plugin failed because data was not numeric.');
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $plugin->tamper('boo');
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    $this->assertSame(0, $this->plugin->tamper(NULL));
    $this->plugin->setConfiguration([
      Math::SETTING_SKIP_ON_NAN => TRUE,
    ]);
    $this->assertNull($this->plugin->tamper(NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    $this->assertSame(0, $this->plugin->tamper(''));
    $this->plugin->setConfiguration([
      Math::SETTING_SKIP_ON_NAN => TRUE,
    ]);
    $this->assertSame('', $this->plugin->tamper(''));
  }

  /**
   * Test with an empty string.
   *
   * @dataProvider emptyDataDataProvider
   */
  public function testWithEmptyDataForeachOperation(?int $expected, array $config, ?string $exception_message = NULL) {
    $this->plugin->setConfiguration($config);
    if (isset($exception_message)) {
      $this->expectException(TamperException::class);
      $this->expectExceptionMessage($exception_message);
    }
    $this->assertEquals($expected, $this->plugin->tamper(''));
    $this->assertEquals($expected, $this->plugin->tamper(NULL));
    $this->assertEquals($expected, $this->plugin->tamper(FALSE));
  }

  /**
   * Data provider for testWithEmptyStringForeachOperation().
   */
  public static function emptyDataDataProvider(): array {
    return [
      'addition' => [
        'expected' => 2,
        'config' => [
          Math::SETTING_OPERATION => 'addition',
          Math::SETTING_VALUE => 2,
        ],
      ],
      'subtraction' => [
        'expected' => -2,
        'config' => [
          Math::SETTING_OPERATION => 'subtraction',
          Math::SETTING_VALUE => 2,
        ],
      ],
      'subtraction-flipped' => [
        'expected' => 2,
        'config' => [
          Math::SETTING_OPERATION => 'subtraction',
          Math::SETTING_FLIP => TRUE,
          Math::SETTING_VALUE => 2,
        ],
      ],
      'multiplication' => [
        'expected' => 0,
        'config' => [
          Math::SETTING_OPERATION => 'multiplication',
          Math::SETTING_VALUE => 2,
        ],
      ],
      'division' => [
        'expected' => 0,
        'config' => [
          Math::SETTING_OPERATION => 'division',
          Math::SETTING_VALUE => 2,
        ],
      ],
      'division-flipped' => [
        'expected' => NULL,
        'config' => [
          Math::SETTING_OPERATION => 'division',
          Math::SETTING_FLIP => TRUE,
          Math::SETTING_VALUE => 2,
        ],
        'exception_message' => 'Math plugin failed because divide by zero was attempted.',
      ],
    ];
  }

}
