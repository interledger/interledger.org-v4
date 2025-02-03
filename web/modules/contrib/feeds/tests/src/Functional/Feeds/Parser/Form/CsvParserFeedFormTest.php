<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Parser\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Drupal\feeds\Entity\Feed;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\Form\CsvParserFeedForm
 * @group feeds
 */
class CsvParserFeedFormTest extends FeedsBrowserTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'fetcher' => 'upload',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
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
  }

  /**
   * Tests importing a feed using the default settings.
   */
  public function testImportSingleFile() {
    // Create feed and import.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => \Drupal::service('file_system')->realpath($this->resourcesPath() . '/csv/nodes_comma.csv'),
    ];
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->submitForm($edit, 'Save and import');

    // Load feed.
    $feed = Feed::load(1);

    // Assert that 2 nodes have been created.
    static::assertEquals(9, $feed->getItemCount());
    $this->assertNodeCount(9);
  }

  /**
   * Tests importing a feed using various delimiters.
   *
   * @param string $delimiter
   *   The delimiter to test.
   * @param string $csv_file
   *   The file to import.
   *
   * @dataProvider delimiterDataProvider
   */
  public function testDelimiterSetting($delimiter, $csv_file) {
    // Create feed and import.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => \Drupal::service('file_system')->realpath($this->resourcesPath() . '/csv/' . $csv_file),
      'plugin[parser][delimiter]' => $delimiter,
    ];
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->submitForm($edit, 'Save and import');

    // Load feed.
    $feed = Feed::load(1);

    // Assert that 9 nodes have been created.
    static::assertEquals(9, $feed->getItemCount());
    $this->assertNodeCount(9);
  }

  /**
   * Data provider for ::testDelimiterSetting().
   */
  public static function delimiterDataProvider() {
    return [
      'comma' => [',', 'nodes_comma.csv'],
      'semicolon' => [';', 'nodes_semicolon.csv'],
      'tab' => ['TAB', 'nodes_tab.csv'],
      'pipe' => ['|', 'nodes_pipe.csv'],
      'plus' => ['+', 'nodes_plus.csv'],
    ];
  }

  /**
   * Tests displaying a list of CSV sources on the form.
   */
  public function testDisplayCsvSources() {
    $this->createFieldWithStorage('field_alpha');
    $this->createFieldWithStorage('field_beta');

    // Create an additional CSV source that also selects 'title', but has a
    // different machine name.
    // 'title' should only be displayed once, and 'title2' should not get
    // displayed.
    $this->feedType->addCustomSource('title2', [
      'label' => 'Title copy',
      'value' => 'title',
      'machine_name' => 'title2',
      'type' => 'csv',
    ]);

    // And map that source.
    $this->feedType->addMapping([
      'target' => 'field_alpha',
      'map' => ['value' => 'title2'],
    ]);

    // Add another CSV source, but don't map that one. This source should not be
    // displayed because it is not mapped.
    $this->feedType->addCustomSource('bar', [
      'label' => 'Bar',
      'value' => 'bar',
      'machine_name' => 'bar',
      'type' => 'csv',
    ]);

    // Map a blank source. This is not csv, and should not be displayed.
    $this->feedType->addCustomSource('qux', [
      'label' => 'Qux',
      'value' => 'qux',
      'machine_name' => 'qux',
      'type' => 'blank',
    ]);
    $this->feedType->addMapping([
      'target' => 'field_beta',
      'map' => ['value' => 'qux'],
    ]);

    // Go to the feed add form.
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->assertSession()->pageTextContains('Import CSV files with one or more of these columns: GUID, Title.');
  }

  /**
   * Tests displaying which sources are unique.
   */
  public function testDisplayUnique() {
    // At first no mappings are unique.
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->assertSession()->pageTextContains('No columns are unique.');

    // Now set guid as unique.
    $mappings = $this->feedType->getMappings();
    $mappings[0]['unique']['guid'] = TRUE;
    $this->feedType->setMappings($mappings);
    $this->feedType->save();

    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->assertSession()->pageTextContains('Column GUID is mandatory and considered unique: only one item per guid value will be created.');

    // Set both guid and title as unique.
    $mappings = $this->feedType->getMappings();
    $mappings[1]['unique']['value'] = TRUE;
    $this->feedType->setMappings($mappings);
    $this->feedType->save();

    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->assertSession()->pageTextContains('Columns GUID, Title are mandatory and values in these columns are considered unique: only one entry per value in one of these column will be created.');

    // Map a blank source and set as unique. This is not csv, and should not be
    // displayed.
    $this->createFieldWithStorage('field_beta');
    $this->feedType->addCustomSource('qux', [
      'label' => 'Qux',
      'value' => 'qux',
      'machine_name' => 'qux',
      'type' => 'blank',
    ]);
    $this->feedType->addMapping([
      'target' => 'field_beta',
      'map' => ['value' => 'qux'],
      'unique' => ['value' => TRUE],
    ]);

    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->assertSession()->pageTextContains('Columns GUID, Title are mandatory and values in these columns are considered unique: only one entry per value in one of these column will be created.');
  }

}
