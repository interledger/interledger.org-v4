<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Encode;

/**
 * Tests the encode / decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Encode
 *
 * @group tamper
 */
class EncodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Encode([], 'encode', [], $this->getMockSourceDefinition());
  }

  /**
   * Test serialize.
   */
  public function testSerializeArray() {
    $config = [
      Encode::SETTING_MODE => 'serialize',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('a:0:{}', $this->plugin->tamper([]));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Test unserialize.
   */
  public function testUnserializeArray() {
    $config = [
      Encode::SETTING_MODE => 'unserialize',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals([], $this->plugin->tamper('a:0:{}'));
    $this->assertTrue($this->plugin->multiple());
  }

  /**
   * Test serialize on complex string.
   */
  public function testSerializeCrazyString() {
    $config = [
      Encode::SETTING_MODE => 'serialize',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('s:28:"abcdef 123 @#`|\"$%&/()=?\'^*";', $this->plugin->tamper('abcdef 123 @#`|\\"$%&/()=?\'^*'));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Test unserialize on complex string.
   */
  public function testUnserializeCrazyString() {
    $config = [
      Encode::SETTING_MODE => 'unserialize',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('abcdef 123 @#`|\"$%&/()=?\'^*', $this->plugin->tamper('s:28:"abcdef 123 @#`|\"$%&/()=?\'^*";'));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Tests encoding a PHP array to JSON.
   */
  public function testJsonEncode() {
    $config = [
      Encode::SETTING_MODE => 'json_encode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('{"key":"value"}', $this->plugin->tamper(['key' => 'value']));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Tests decoding JSON string to a PHP array.
   */
  public function testJsonDecode() {
    $config = [
      Encode::SETTING_MODE => 'json_decode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals(['key' => 'value'], $this->plugin->tamper('{"key":"value"}'));
    $this->assertTrue($this->plugin->multiple());
  }

  /**
   * Test base64_encode.
   */
  public function testBase64Encode() {
    $config = [
      Encode::SETTING_MODE => 'base64_encode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('YWJjZGVmIDEyMyBAI2B8XCIkJSYvKCk9PydeKg==', $this->plugin->tamper('abcdef 123 @#`|\\"$%&/()=?\'^*'));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Test base64_decode.
   */
  public function testBase64Decode() {
    $config = [
      Encode::SETTING_MODE => 'base64_decode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('abcdef 123 @#`|\\"$%&/()=?\'^*', $this->plugin->tamper('YWJjZGVmIDEyMyBAI2B8XCIkJSYvKCk9PydeKg=='));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Test yaml_encode.
   */
  public function testYamlEncode() {
    $config = [
      Encode::SETTING_MODE => 'yaml_encode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals("x: 'y'\n'y': x\n", $this->plugin->tamper(['x' => 'y', 'y' => 'x']));
    $this->assertFalse($this->plugin->multiple());
  }

  /**
   * Test yaml_decode.
   */
  public function testYamlDecode() {
    $config = [
      Encode::SETTING_MODE => 'yaml_decode',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals(['x' => 'y', 'y' => 'x'], $this->plugin->tamper("x: y\ny: x"));
    $this->assertTrue($this->plugin->multiple());
  }

  /**
   * Tests handling multiple values.
   *
   * @param mixed $expected
   *   The expected return value after tampering.
   * @param string $mode
   *   The plugin encode/decode mode.
   * @param array $input
   *   The data to pass to the tamper plugin.
   * @param bool $return_multiple
   *   Whether the data return by the tamper plugin is single or multiple.
   *   True in case it is multiple, false in case it is single.
   *
   * @dataProvider multivalueHandlingProvider
   */
  public function testMultivalueHandling($expected, string $mode, array $input, bool $return_multiple = FALSE) {
    $config = [
      Encode::SETTING_MODE => $mode,
    ];
    $this->plugin->setConfiguration($config);
    $this->assertSame($expected, $this->plugin->tamper($input));
    $this->assertSame($return_multiple, $this->plugin->multiple());
  }

  /**
   * Data provider for testMultivalueHandling().
   */
  public static function multivalueHandlingProvider(): array {
    $encode_input = [
      'foo' => 'bar',
      'qux' => 'baz',
    ];

    return [
      'serialize' => [
        'expected' => 'a:2:{s:3:"foo";s:3:"bar";s:3:"qux";s:3:"baz";}',
        'mode' => 'serialize',
        'input' => $encode_input,
      ],
      'unserialize' => [
        'expected' => [
          'foo',
          'bar',
        ],
        'mode' => 'unserialize',
        'input' => [
          's:3:"foo";',
          's:3:"bar";',
        ],
        'return_multiple' => TRUE,
      ],
      'json_encode' => [
        'expected' => '{"foo":"bar","qux":"baz"}',
        'mode' => 'json_encode',
        'input' => $encode_input,
      ],
      'json_decode' => [
        'expected' => [
          $encode_input,
          'foo',
        ],
        'mode' => 'json_decode',
        'input' => [
          '{"foo":"bar","qux":"baz"}',
          '"foo"',
        ],
        'return_multiple' => TRUE,
      ],
      'base64_encode' => [
        'expected' => [
          'foo' => 'YmFy',
          'qux' => 'YmF6',
        ],
        'mode' => 'base64_encode',
        'input' => $encode_input,
        'return_multiple' => TRUE,
      ],
      'base64_decode' => [
        'expected' => [
          'foo' => 'bar',
          'qux' => 'baz',
        ],
        'mode' => 'base64_decode',
        'input' => [
          'foo' => 'YmFy',
          'qux' => 'YmF6',
        ],
        'return_multiple' => TRUE,
      ],
      'yaml_encode' => [
        'expected' => "foo: bar\nqux: baz\n",
        'mode' => 'yaml_encode',
        'input' => $encode_input,
      ],
      'yaml_decode' => [
        'expected' => [
          $encode_input,
          'foo',
        ],
        'mode' => 'yaml_decode',
        'input' => [
          "foo: 'bar'\n'qux': 'baz'\n",
          'foo',
        ],
        'return_multiple' => TRUE,
      ],
    ];
  }

  /**
   * Tests that a function that is not defined as option is not called.
   */
  public function testInvalidEncodeOption() {
    $config = [
      Encode::SETTING_MODE => '\Drupal\Tests\tamper\Unit\Plugin\Tamper\tamper_test_invalid_encode',
    ];
    $this->plugin->setConfiguration($config);
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('The selected encode mode "\Drupal\Tests\tamper\Unit\Plugin\Tamper\tamper_test_invalid_encode" is invalid.');
    $this->assertEquals(['foo', 'bar'], $this->plugin->tamper(['foo', 'bar']));
  }

  /**
   * Tests that a mode with an undefined callback results into an exception.
   */
  public function testErrorOnCallbackNotDefined() {
    $class = new \ReflectionClass(Encode::class);
    $method = $class->getMethod('applyEncode');
    $method->setAccessible(TRUE);
    $closure = $method->getClosure($this->plugin);

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The encoding could not be applied because the selected mode "invalid_encode" has no valid callback.');
    call_user_func_array($closure, ['foo', [], 'invalid_encode']);
  }

  /**
   * {@inheritdoc}
   */
  public function testWithNullValue() {
    // Serializing the data is the default.
    $this->assertEquals('N;', $this->plugin->tamper(NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function testWithEmptyString() {
    // Serializing the data is the default.
    $this->assertEquals('s:0:"";', $this->plugin->tamper(''));
  }

}

/**
 * Function that should not get called.
 */
function tamper_test_invalid_encode() {
  throw new \LogicException('This function should not be called.');
}
