<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the absolute_url plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\AbsoluteUrl
 * @group tamper
 */
class AbsoluteUrlTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'absolute_url';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Base URL source field is required.',
        ],
      ],
      'with values' => [
        'expected' => [
          'base_url_source' => 'baz',
        ],
        'edit' => [
          'base_url_source' => 'baz',
        ],
      ],
    ];
  }

}
