<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\AbsoluteUrl;
use Drupal\tamper\TamperItem;

/**
 * Tests the absolute_url plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\AbsoluteUrl
 * @group tamper
 */
class AbsoluteUrlTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      AbsoluteUrl::SETTING_SOURCE => 'base_url',
    ];
    return new AbsoluteUrl($config, 'absolute_url', [], $this->getMockSourceDefinition());
  }

  /**
   * Run through a number of absolute URL test scenarios.
   *
   * @param string $expectedResult
   *   The expected absolute url.
   * @param mixed $input
   *   The data that contains urls that need to be made absolute.
   * @param mixed $baseUrl
   *   The base url, for example 'http://www.example.com'.
   *
   * @dataProvider provideUrlData
   */
  public function testAllTheThingsAbsolute(string $expectedResult, $input, $baseUrl) {
    $item = new TamperItem();
    $item->setSourceProperty('base_url', $baseUrl);
    $html_in = $this->plugin->tamper($input, $item);
    $this->assertEquals($expectedResult, $html_in);
  }

  /**
   * Run through test scenarios where the source domain settings are broken.
   *
   * @param mixed $input
   *   The data that contains urls that need to be made absolute.
   * @param mixed $baseUrl
   *   Something supposed to be a base url, but it is not.
   *
   * @dataProvider provideBrokenDomainData
   */
  public function testBrokenDomainSettings($input, $baseUrl) {
    $item = new TamperItem();
    $item->setSourceProperty('base_url', $baseUrl);
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('You must define a valid domain in your base url data source (ie: http://example.com).');
    $this->plugin->tamper($input, $item);
  }

  /**
   * Tests that a base url source must be configured.
   */
  public function testRequireBaseUrlSource() {
    $item = new TamperItem();
    $item->setSourceProperty('base_url', 'http://example.com');
    $plugin = new AbsoluteUrl([], 'absolute_url', [], $this->getMockSourceDefinition());
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('You must define a valid source from the plugin settings.');
    $plugin->tamper('foo', $item);
  }

  /**
   * Tests that a tamperable item is required.
   */
  public function testRequireItem() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('The plugin "absolute_url" needs a tamperable item in order to operate.');
    $this->plugin->tamper('foo');
  }

  /**
   * Test with a null value.
   */
  public function testWithNullValue() {
    $item = new TamperItem();
    $item->setSourceProperty('base_url', 'http://example.com');
    $this->assertNull($this->plugin->tamper(NULL, $item));
  }

  /**
   * Test with an empty string.
   */
  public function testWithEmptyString() {
    $item = new TamperItem();
    $item->setSourceProperty('base_url', 'http://example.com');
    $this->assertSame('', $this->plugin->tamper('', $item));
  }

  /**
   * Data provider for ::testAllTheThingsAbsolute().
   */
  public static function provideUrlData(): array {
    return [
      'dog link no leading slash' => [
        '<a href="http://example.com/dog"></a>',
        '<a href="dog"></a>',
        'http://example.com',
      ],
      'dog and kitty links with leading slashes' => [
        '<a href="http://example.com/dog"></a><img src="http://example.com/kitty" />',
        '<a href="/dog"></a><img src="/kitty" />',
        'http://example.com',
      ],
      'kitty image with leading slash' => [
        '<img src="http://example.com/kitty" />',
        '<img src="/kitty" />',
        'http://example.com',
      ],
      'kitty image with no leading slash' => [
        '<img src="http://example.com/kitty" />',
        '<img src="kitty" />',
        'http://example.com',
      ],
      'kitty png with leading slash' => [
        '<img src="http://example.com/kitty.png" />',
        '<img src="/kitty.png" />',
        'http://example.com',
      ],
      'kitty png with leading slash and frog sub folder' => [
        '<img src="http://example.com/frog/kitty.png" />',
        '<img src="/frog/kitty.png" />',
        'http://example.com',
      ],
      'base url with extra slash at the end' => [
        '<a href="http://example.com/dog"></a>',
        '<a href="dog"></a>',
        'http://example.com/',
      ],
      'base url with path and extra slash at the end' => [
        '<a href="http://example.com/cat/chicken/dog"></a>',
        '<a href="dog"></a>',
        'http://example.com/cat/chicken/',
      ],
      'empty string' => [
        '',
        '',
        'http://example.com',
      ],
      'data in an array' => [
        '<a href="http://example.com/dog"></a>',
        [['<a href="dog"></a>']],
        'http://example.com',
      ],
      'only a string' => [
        'foo',
        'foo',
        'http://example.com',
      ],
      'empty href in link' => [
        '<a href="">foo</a>',
        '<a href="">foo</a>',
        'http://example.com',
      ],
      'urls that are already absolute' => [
        '<a href="https://www.example.com/foo">bar</a>',
        '<a href="https://www.example.com/foo">bar</a>',
        'http://example.com',
      ],
      'test relative url sticks to base url properly' => [
        '<a href="http://example.com/dog"></a>',
        '<a href="/dog"></a>',
        'http://example.com/cat',
      ],
      'test url without leading slash grabs entire base url ahead of itself' => [
        '<a href="http://example.com/cat/chicken/dog"></a>',
        '<a href="dog"></a>',
        'http://example.com/cat/chicken',
      ],
      'test when base url is an array we attempt to convert it to a string' => [
        '<a href="https://example.com/dog"></a>',
        '<a href="dog"></a>',
        ['https://example.com'],
      ],
      'domain without http' => [
        '<a href="https://example.com/dog"></a>',
        '<a href="dog"></a>',
        'example.com',
      ],
      'domain with subdomain, but without http' => [
        '<a href="https://www.example.com/dog"></a>',
        '<a href="dog"></a>',
        'www.example.com',
      ],
      'domain without http, but with path' => [
        '<a href="https://example.com/cat/dog"></a>',
        '<a href="dog"></a>',
        'example.com/cat',
      ],
      'domain without http, but with a subdirectory with a dot in it' => [
        '<a href="https://example.com/foo.bar/dog"></a>',
        '<a href="dog"></a>',
        'example.com/foo.bar',
      ],
    ];
  }

  /**
   * Data provider for ::testBrokenDomainSettings().
   */
  public static function provideBrokenDomainData(): array {
    return [
      'empty base url' => [
        '<a href="dog"></a>',
        '',
      ],
      'only a string' => [
        '<a href="dog"></a>',
        'example',
      ],
      'is not a domain name?' => [
        '<a href="dog"></a>',
        'example.com:)/cat',
      ],
      'is not a domain name too?' => [
        '<a href="dog"></a>',
        'example.com123/cat',
      ],
      'looks more like a sentence' => [
        '<a href="dog"></a>',
        'the quick brown fox jumps over the lazy dog.',
      ],
    ];
  }

}
