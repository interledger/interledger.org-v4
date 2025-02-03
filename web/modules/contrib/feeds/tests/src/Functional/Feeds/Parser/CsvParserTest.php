<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Parser;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsBrowserTestBase {

  /**
   * Tests if a CSV template is generated properly using various settings.
   *
   * @param string $expected
   *   The expected contents of the CSV template.
   * @param string $delimiter
   *   The delimiter to test with.
   * @param array $columns
   *   The CSV columns, keyed by machine name.
   * @param array $mappings
   *   The mapping settings.
   * @param array $texts
   *   The texts expected to be displayed on the feed form.
   *
   * @dataProvider getTemplateDataProvider
   */
  public function testGetTemplate($expected, $delimiter, array $columns, array $mappings, array $texts) {
    $feed_type = $this->createFeedTypeForCsv($columns, [
      'parser_configuration' => [
        'delimiter' => $delimiter,
      ],
      'mappings' => $mappings,
    ]);

    $this->drupalGet('feed/add/' . $feed_type->id());
    $this->clickLink('Download a template');
    $this->assertSession()->responseContains($expected);

    // Check texts that are displayed on the import page.
    $this->drupalGet('feed/add/' . $feed_type->id());
    $this->assertSession()->pageTextContains('Import CSV files with one or more of these columns: ' . $texts['columns'] . '.');
    if (isset($texts['unique'])) {
      $this->assertSession()->pageTextContains($texts['unique']);
    }
    else {
      $this->assertSession()->pageTextContains('No columns are unique. The import will only create new items, no items will be updated.');
    }
  }

  /**
   * Data provider for ::testGetTemplate().
   */
  public static function getTemplateDataProvider(): array {
    $default_columns = [
      'title_' => 'title+;|',
      'alpha_beta_gamma' => 'alpha, beta + gamma',
      'guid' => 'guid',
    ];
    $default_mappings = [
      [
        'target' => 'title',
        'map' => ['value' => 'title_'],
      ],
      [
        'target' => 'body',
        'map' => ['value' => 'alpha_beta_gamma'],
      ],
      [
        'target' => 'feeds_item',
        'map' => ['guid' => 'guid'],
      ],
    ];

    return [
      // Delimiter ',' test. Source keys containing a ',' should be wrapped in
      // quotes.
      [
        'expected' => 'title+;|,"alpha, beta + gamma",guid',
        'delimiter' => ',',
        'columns' => $default_columns,
        'mappings' => $default_mappings,
        'texts' => [
          'columns' => 'title+;|, "alpha, beta + gamma", guid',
        ],
      ],

      // Delimiter ';' test. Source keys containing a ';' should be wrapped in
      // quotes.
      [
        'expected' => '"title;)";alpha, beta + gamma;guid',
        'delimiter' => ';',
        'columns' => [
          'title_' => 'title;)',
        ] + $default_columns,
        'mappings' => $default_mappings,
        'texts' => [
          'columns' => 'title;), "alpha, beta + gamma", guid',
        ],
      ],

      // Delimiter 'TAB' test.
      [
        'expected' => 'title,;|	alpha, beta + gamma	guid',
        'delimiter' => 'TAB',
        'columns' => [
          'title_' => 'title,;|',
        ] + $default_columns,
        'mappings' => $default_mappings,
        'texts' => [
          'columns' => '"title,;|", "alpha, beta + gamma", guid',
        ],
      ],

      // Delimiter '|' test. Source keys containing a '|' should be wrapped in
      // quotes.
      [
        'expected' => 'title+;,|"alpha|beta|gamma"|guid',
        'delimiter' => '|',
        'columns' => [
          'title_' => 'title+;,',
          'alpha_beta_gamma' => 'alpha|beta|gamma',
        ] + $default_columns,
        'mappings' => $default_mappings,
        'texts' => [
          'columns' => '"title+;,", alpha|beta|gamma, guid',
        ],
      ],

      // Delimiter '+' test. Source keys containing a '+' should be wrapped in
      // quotes.
      [
        'expected' => 'title,;|+"alpha, beta + gamma"+guid',
        'delimiter' => '+',
        'columns' => [
          'title_' => 'title,;|',
        ] + $default_columns,
        'mappings' => $default_mappings,
        'texts' => [
          'columns' => '"title,;|", "alpha, beta + gamma", guid',
        ],
      ],

      // Ensure that when a source key is used multiple times in mapping, the
      // key is only printed once in the CSV template.
      [
        'expected' => 'text,guid,date',
        'delimiter' => ',',
        'columns' => [
          'text' => 'text',
          'guid' => 'guid',
          'date' => 'date',
        ],
        'mappings' => [
          [
            'target' => 'title',
            'map' => ['value' => 'text'],
            'unique' => ['value' => TRUE],
          ],
          [
            'target' => 'feeds_item',
            'map' => ['guid' => 'guid'],
            'unique' => ['guid' => TRUE],
          ],
          [
            'target' => 'created',
            'map' => ['value' => 'date'],
          ],
          [
            'target' => 'body',
            'map' => [
              'summary' => 'date',
              'value' => 'text',
            ],
          ],
        ],
        'texts' => [
          'columns' => 'text, guid, date',
          'unique' => 'Columns text, guid are mandatory and values in these columns are considered unique',
        ],
      ],

      // Special characters. Things like '&' shouldn't be converted to '&amp;'
      // for example.
      [
        'expected' => '&,alpha&beta,<created>,guid',
        'delimiter' => ',',
        'columns' => [
          'title_' => '&',
          'alpha_beta_gamma' => 'alpha&beta',
          'created' => '<created>',
          'guid' => 'guid',
        ],
        'mappings' => [
          [
            'target' => 'title',
            'map' => ['value' => 'title_'],
            'unique' => ['value' => TRUE],
          ],
          [
            'target' => 'body',
            'map' => ['value' => 'alpha_beta_gamma'],
          ],
          [
            'target' => 'created',
            'map' => ['value' => 'created'],
          ],
          [
            'target' => 'feeds_item',
            'map' => ['guid' => 'guid'],
          ],
        ],
        'texts' => [
          'columns' => '&, alpha&beta, <created>, guid',
          'unique' => 'Column & is mandatory and considered unique',
        ],
      ],

      // Blank sources (source which name only contains spaces) should not end
      // up in the template, but a zero should.
      [
        'expected' => '0',
        'delimiter' => ',',
        'columns' => [
          '0' => '0',
          'empty' => ' ',
        ],
        'mappings' => [
          [
            'target' => 'body',
            'map' => ['value' => '0'],
          ],
          [
            'target' => 'feeds_item',
            'map' => ['guid' => 'empty'],
          ],
        ],
        'texts' => [
          'columns' => '0',
        ],
      ],
    ];
  }

  /**
   * Tests getting CSV template for feed with overridden delimiter setting.
   */
  public function testGetTemplateForFeed() {
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'parser_configuration' => [
        'delimiter' => ',',
      ],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);

    // Create a feed, override the delimiter.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/nodes.csv',
      'config' => [
        'parser' => [
          'delimiter' => ';',
        ],
      ],
    ]);

    $this->drupalGet('feed/' . $feed->id() . '/edit');
    $this->clickLink('Download a template');
    $this->assertSession()->responseContains('GUID;Title');
  }

}
