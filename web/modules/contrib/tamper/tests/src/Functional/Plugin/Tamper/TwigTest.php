<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the twig plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Twig
 * @group tamper
 */
class TwigTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'twig';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'template' => '',
        ],
      ],
      'with values' => [
        'expected' => [
          'template' => 'current data: {{ _tamper_data }}',
        ],
        'edit' => [
          'template' => 'current data: {{ _tamper_data }}',
        ],
      ],
    ];
  }

  /**
   * Tests if source keys instead of labels are displayed.
   */
  public function testDisplaySourceListKeys() {
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);
    $this->assertSession()->pageTextContains('{{ _tamper_data }} - Current data');
    $this->assertSession()->pageTextContains('{{ _tamper_item }} - Current item object');
    $this->assertSession()->pageTextContains('{{ foo }} - Foo');
    $this->assertSession()->pageTextContains('{{ bar }} - Bar');
    $this->assertSession()->pageTextContains('{{ baz }} - Baz');
    $this->assertSession()->pageTextContains('{{ quxxie }} - Qux');
    // Special source properties with non-standard names can be retrieved.
    $this->assertSession()->pageTextContains("{{ _tamper_item.getSourceProperty('a\"b_c') }} - Unconventional");
  }

}
