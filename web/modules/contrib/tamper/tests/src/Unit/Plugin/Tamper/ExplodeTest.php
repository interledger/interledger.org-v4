<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Explode;

/**
 * Tests the explode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Explode
 * @group tamper
 */
class ExplodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return $this->getPluginDefaultConfig();
  }

  /**
   * Test explode.
   */
  public function testExplodeWithSingleValue() {
    $original = 'foo,bar,baz,zip';
    $expected = ['foo', 'bar', 'baz', 'zip'];
    $this->assertEquals($expected, $this->getPluginDefaultConfig()->tamper($original));
  }

  /**
   * Test explode.
   */
  public function testExplodeWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $original = ['foo,bar', 'baz,zip'];
    $this->getPluginDefaultConfig()->tamper($original);
  }

  /**
   * Text explode with limit.
   */
  public function testExplodeWithSingleValueAndLimit() {
    $original = 'foo,bar,baz,zip';
    $expected = ['foo', 'bar,baz,zip'];
    $this->assertEquals($expected, $this->getPluginWithLimit()->tamper($original));
  }

  /**
   * Text explode with limit.
   */
  public function testExplodeWithMultipleValuesAndLimit() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $original = ['foo,bar,baz,zip', 'fizz,bang,boop'];
    $this->getPluginWithLimit()->tamper($original);
  }

  /**
   * Tests the different separators behavior.
   *
   * @dataProvider explodeSeparatorsProvider
   */
  public function testExplodeSeparators($original, $separators, $expected) {
    $config = [
      Explode::SETTING_SEPARATOR => $separators,
    ];
    $plugin = new Explode($config, 'explode', [], $this->getMockSourceDefinition());

    $this->assertEquals($expected, $plugin->tamper($original));
  }

  /**
   * Data provider for testExplodeSeparators.
   */
  public static function explodeSeparatorsProvider() {
    return [
      [
        "a,b c\td\ne\rf",
        ',',
        ["a", "b c\td\ne\rf"],
      ],
      [
        "a,b c\td\ne\rf",
        '%s',
        ["a,b", "c\td\ne\rf"],
      ],
      [
        "a,b c\td\ne\rf",
        '%t',
        ["a,b c", "d\ne\rf"],
      ],
      [
        "a,b c\td\ne\rf",
        '%n',
        ["a,b c\td", "e\rf"],
      ],
      [
        "a,b c\td\ne\rf",
        '%r',
        ["a,b c\td\ne", "f"],
      ],
    ];
  }

  /**
   * Returns default configuration for the plugin for this test.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Explode
   *   A explode tamper plugin instance.
   */
  protected function getPluginDefaultConfig() {
    return new Explode([], 'explode', [], $this->getMockSourceDefinition());
  }

  /**
   * Returns default limit setting for the plugin for this test.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Explode
   *   A explode tamper plugin instance.
   */
  protected function getPluginWithLimit() {
    $config = [
      Explode::SETTING_LIMIT => 2,
    ];
    return new Explode($config, 'explode', [], $this->getMockSourceDefinition());
  }

}
