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
   * Tests converting boolean.
   *
   * @param mixed $expected
   *   The expected result.
   * @param mixed $input
   *   The input data.
   * @param array $config
   *   The plugin configuration.
   *
   * @dataProvider dataProviderConvertBoolean
   */
  public function testConvertBoolean($expected, $input, array $config) {
    $this->plugin->setConfiguration($config);
    $this->assertSame($expected, $this->plugin->tamper($input));
  }

  /**
   * Data provider for testConvertBoolean().
   */
  public static function dataProviderConvertBoolean(): array {
    // The default configuration for these test cases makes the comparison case
    // insensitive. If the input value is not 'A', 'a', 'B' or 'b', the output
    // value will be literally set to 'No match'. Per case settings may get
    // overridden.
    $default_config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => FALSE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
    ];

    // Configuration where the comparison is case sensitive. If the input value
    // is not 'A' or 'B', there is no match and the output value will be
    // literally set to 'No match'.
    $config_case_sensitive = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
    ];

    return [
      // Basic cases with ignore case.
      'ignore case - truth value uppercase' => [
        'expected' => TRUE,
        'input' => 'A',
        'config' => $default_config,
      ],
      'ignore case - truth value lowercase' => [
        'expected' => TRUE,
        'input' => 'a',
        'config' => $default_config,
      ],
      'ignore case - false value uppercase' => [
        'expected' => FALSE,
        'input' => 'B',
        'config' => $default_config,
      ],
      'ignore case - false value lowercase' => [
        'expected' => FALSE,
        'input' => 'b',
        'config' => $default_config,
      ],
      'ignore case - no match value' => [
        'expected' => 'No match',
        'input' => 'C',
        'config' => $default_config,
      ],

      // Basic cases with case sensitivity.
      'case sensitive - truth value uppercase' => [
        'expected' => TRUE,
        'input' => 'A',
        'config' => $config_case_sensitive,
      ],
      'case sensitive - truth value lowercase' => [
        'expected' => 'No match',
        'input' => 'a',
        'config' => $config_case_sensitive,
      ],
      'case sensitive - false value uppercase' => [
        'expected' => FALSE,
        'input' => 'B',
        'config' => $config_case_sensitive,
      ],
      'case sensitive - false value lowercase' => [
        'expected' => 'No match',
        'input' => 'b',
        'config' => $config_case_sensitive,
      ],

      // No match cases. The output value will be set to the value that is
      // configured for the setting ConvertBoolean::SETTING_NO_MATCH. However,
      // if the option 'pass' is chosen, the input value is expected to be
      // returned as is.
      'true' => [
        'expected' => TRUE,
        'input' => 'C',
        'config' => [
          ConvertBoolean::SETTING_NO_MATCH => TRUE,
        ] + $default_config,
      ],
      'false' => [
        'expected' => FALSE,
        'input' => 'C',
        'config' => [
          ConvertBoolean::SETTING_NO_MATCH => FALSE,
        ] + $default_config,
      ],
      'null' => [
        'expected' => NULL,
        'input' => 'C',
        'config' => [
          ConvertBoolean::SETTING_NO_MATCH => NULL,
        ] + $default_config,
      ],
      'pass' => [
        'expected' => 'C',
        'input' => 'C',
        'config' => [
          ConvertBoolean::SETTING_NO_MATCH => 'pass',
        ] + $default_config,
      ],
      'other' => [
        'expected' => 'other text',
        'input' => 'C',
        'config' => [
          ConvertBoolean::SETTING_NO_MATCH => 'other text',
        ] + $default_config,
      ],
    ];
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
