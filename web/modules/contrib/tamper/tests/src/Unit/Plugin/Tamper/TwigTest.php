<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\tamper\Plugin\Tamper\Twig;
use Drupal\tamper\TamperItem;
use Twig\Loader\ChainLoader;

/**
 * Tests the twig plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Twig
 * @group tamper
 */
class TwigTest extends TamperPluginTestBase {

  /**
   * The twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigEnvironment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $cache = new NullBackend('twig');
    $twig_extension_hash = 'twig-extension-hash';
    $state = $this->getMockBuilder(StateInterface::class)->getMock();
    $loader = new ChainLoader();
    $options = [
      // Disable cache.
      'cache' => FALSE,
    ];
    // Create a simple twig environment.
    $twig = new TwigEnvironment(DRUPAL_ROOT, $cache, $twig_extension_hash, $state, $loader, $options);
    $this->plugin->setTwigEnvironment($twig);
  }

  /**
   * Get a tamper item to use in the test.
   *
   * @return \Drupal\tamper\TamperItem
   *   The tamperable item to use in the test.
   */
  protected function getTamperItem() {
    $item = new TamperItem();
    $item->setSourceProperty('title', 'Yay title!');
    $item->setSourceProperty('body', 'Yay body!');
    $item->setSourceProperty('foo', 'bar');
    $item->setSourceProperty('0x123"\'', 'unconventional source');
    // Source names that conflict with the internal variable names.
    $item->setSourceProperty('_tamper_data', 'bar');
    $item->setSourceProperty('_tamper_item', 'baz');
    $item->setSourceProperty('_context', 'custom context');

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Twig::SETTING_TEMPLATE => <<<EOF
      Title: {{ title }}
      Title (upper): {{ title|upper }}
      Title (length): {{ title|length }}
      Body: {{ body }}
      Foo: {% if foo == "bar" %}
      foo contains bar
      {% endif %}
      _tamper_data (current value): {{ _tamper_data }}
      _tamper_data (source): {{ _tamper_item.getSourceProperty('_tamper_data') }}
      _tamper_item (source): {{ _tamper_item.getSourceProperty('_tamper_item') }}
      unconventional: {{ _tamper_item.getSourceProperty('0x123"\'') }}
      twig _context (foo): {{ _context['foo'] }}
      _context (source): {{ _tamper_item.getSourceProperty('_context') }}
      EOF,
    ];
    return new Twig($config, 'twig', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests the twig functionality.
   */
  public function testTwig() {
    $expected = <<<MARKUP
    Title: Yay title!
    Title (upper): YAY TITLE!
    Title (length): 10
    Body: Yay body!
    Foo: foo contains bar
    _tamper_data (current value): foo
    _tamper_data (source): bar
    _tamper_item (source): baz
    unconventional: unconventional source
    twig _context (foo): bar
    _context (source): custom context
    MARKUP;
    $this->assertEquals($expected, $this->plugin->tamper('foo', $this->getTamperItem()));
  }

  /**
   * Tests if no twig rendering takes place when there's no tamperable item.
   */
  public function testWithoutTamperableItem() {
    $this->assertEquals('foo', $this->instantiatePlugin()->tamper('foo'));
  }

  /**
   * Tests that the plugin converts variables into a usable twig token.
   *
   * @dataProvider tokenVariableProvider
   */
  public function testTwigTokenConversion(string $variable, string $expected) {
    $this->assertEquals($expected, $this->instantiatePlugin()->convertToTwigToken($variable));
  }

  /**
   * Data provider for ::testTwigTokenConversion().
   */
  public static function tokenVariableProvider(): array {
    return [
      'foo' => [
        'variable' => 'foo',
        'expected' => 'foo',
      ],
      '_foo' => [
        'variable' => '_foo',
        'expected' => '_foo',
      ],
      'integer' => [
        'variable' => '0',
        'expected' => "0",
      ],
      // Non-standard source names that can not be accessed as direct twig
      // variables.
      'non-standard' => [
        'variable' => '0x123"\'',
        'expected' => "_tamper_item.getSourceProperty('0x123\"\'')",
      ],
      '0foo' => [
        'variable' => '0foo',
        'expected' => "_tamper_item.getSourceProperty('0foo')",
      ],
      // These variables are explicitly declared by the twig plugin, so can
      // only be accessed through the tamper item.
      '_tamper_data' => [
        'variable' => '_tamper_data',
        'expected' => "_tamper_item.getSourceProperty('_tamper_data')",
      ],
      '_tamper_item' => [
        'variable' => '_tamper_item',
        'expected' => "_tamper_item.getSourceProperty('_tamper_item')",
      ],
      '_context' => [
        'variable' => '_context',
        'expected' => "_tamper_item.getSourceProperty('_context')",
      ],
    ];
  }

  /**
   * Tests if the Twig plugin returns the right used properties.
   *
   * @covers ::getUsedSourceProperties
   *
   * @dataProvider getUsedSourcePropertiesProvider
   */
  public function testGetUsedSourceProperties(array $expected, string $template) {
    $this->plugin->setConfiguration([
      Twig::SETTING_TEMPLATE => $template,
    ]);
    $item = new TamperItem();
    $this->assertSame($expected, $this->plugin->getUsedSourceProperties($item));
  }

  /**
   * Data provider for testGetUsedSourceProperties().
   */
  public static function getUsedSourcePropertiesProvider(): array {
    return [
      'no variables' => [
        'expected' => [],
        'template' => 'Hello world',
      ],
      'simple variable' => [
        'expected' => ['title'],
        'template' => '{{ title }}',
      ],
      'variable with filter' => [
        'expected' => ['name'],
        'template' => '{{ name|upper }}',
      ],
      'variable with property' => [
        'expected' => ['article'],
        'template' => '{{ article.body }}',
      ],
      'reserved variables excluded' => [
        'expected' => [
          '_data',
        ],
        'template' => '{{ _data }} {{ _item }} {{ _context }}',
      ],
      'getSourceProperty call' => [
        'expected' => ['price'],
        'template' => '{{ _item.getSourceProperty("price") }}',
      ],
      'mixed variables and calls' => [
        'expected' => ['title', 'price'],
        'template' => '{{ title }} costs {{ _item.getSourceProperty("price") }}',
      ],
      'if statement' => [
        'expected' => ['foo', 'qux'],
        'template' => '{% if foo.bar == qux|upper %}',
      ],
    ];
  }

}
