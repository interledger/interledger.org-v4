<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the hash plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Hash
 * @group tamper
 */
class HashTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'hash';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'data_to_hash' => 'item',
          'only_if_empty' => FALSE,
        ],
      ],
      'with values' => [
        'expected' => [
          'data_to_hash' => 'data',
          'only_if_empty' => TRUE,
        ],
        'edit' => [
          'data_to_hash' => 'data',
          'only_if_empty' => '1',
        ],
      ],
      'with only_if_empty disabled' => [
        'expected' => [
          'data_to_hash' => 'data',
          'only_if_empty' => FALSE,
        ],
        'edit' => [
          'data_to_hash' => 'data',
          'only_if_empty' => '0',
        ],
      ],
    ];
  }

}
