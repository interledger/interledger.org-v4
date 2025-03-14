<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

/**
 * Tests the keyword filter plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\KeywordFilter
 * @group tamper
 */
class KeywordFilterTest extends TamperPluginTestBase {

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'keyword_filter';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Words or phrases to filter on field is required.',
        ],
      ],
      'only words' => [
        'expected' => [
          'words' => '',
          'words_list' => [
            '[Foo]',
            'Bar',
          ],
          'word_boundaries' => FALSE,
          'exact' => FALSE,
          'case_sensitive' => FALSE,
          'invert' => FALSE,
        ],
        'edit' => [
          'words' => "[Foo]\nBar",
        ],
      ],
      'with values' => [
        'expected' => [
          'words' => '',
          'words_list' => [
            'F[o]o',
            'Bar',
          ],
          'word_boundaries' => TRUE,
          'exact' => TRUE,
          'case_sensitive' => TRUE,
          'invert' => TRUE,
        ],
        'edit' => [
          'words' => "F[o]o\nBar",
          'word_boundaries' => '1',
          'exact' => '1',
          'case_sensitive' => '1',
          'invert' => '1',
        ],
      ],
      'word boundaries' => [
        'expected' => [
          'words' => '',
          'words_list' => [
            'F[o]o',
            '_Bar_',
            '88x88',
          ],
          'word_boundaries' => TRUE,
          'exact' => FALSE,
          'case_sensitive' => FALSE,
          'invert' => FALSE,
        ],
        'edit' => [
          'words' => "F[o]o\n_Bar_\n88x88",
          'word_boundaries' => '1',
        ],
      ],
      'word boundaries error' => [
        'expected' => [],
        'edit' => [
          'words' => "F[o]o\n*Bar_\n88x88",
          'word_boundaries' => '1',
        ],
        'errors' => [
          'Search text must begin and end with a letter, number, or underscore to use the Respect word boundaries option.',
        ],
      ],
      'case_sensitive' => [
        'expected' => [
          'words' => '',
          'words_list' => [
            'Foo',
          ],
          'word_boundaries' => FALSE,
          'exact' => FALSE,
          'case_sensitive' => TRUE,
          'invert' => FALSE,
        ],
        'edit' => [
          'words' => 'Foo',
          'case_sensitive' => '1',
        ],
      ],
    ];
  }

}
