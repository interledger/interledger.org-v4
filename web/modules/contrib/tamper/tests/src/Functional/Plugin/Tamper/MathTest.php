<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the math plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Math
 * @group tamper
 */
class MathTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'math';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Value field is required.',
        ],
      ],
      'only required values' => [
        'expected' => [
          'operation' => 'addition',
          'flip' => FALSE,
          'value' => 8.0,
          'skip_on_nan' => FALSE,
        ],
        'edit' => [
          'value' => '8',
        ],
      ],
      'with values' => [
        'expected' => [
          'operation' => 'multiplication',
          'flip' => TRUE,
          'value' => 3.0,
          'skip_on_nan' => TRUE,
        ],
        'edit' => [
          'operation' => 'multiplication',
          'flip' => '1',
          'value' => '3',
          'skip_on_nan' => '1',
        ],
      ],
      'with decimal value' => [
        'expected' => [
          'operation' => 'addition',
          'flip' => FALSE,
          'value' => 2.312,
          'skip_on_nan' => FALSE,
        ],
        'edit' => [
          'value' => '2.312',
        ],
      ],
    ];
  }

}
