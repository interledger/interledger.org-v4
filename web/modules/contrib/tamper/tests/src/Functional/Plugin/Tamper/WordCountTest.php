<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the word_count plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\WordCount
 * @group tamper
 */
class WordCountTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'word_count';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [
          'limit' => NULL,
        ],
      ],
      'with values' => [
        'expected' => [
          'limit' => 6,
        ],
        'edit' => [
          'limit' => '6',
        ],
      ],
    ];
  }

}
