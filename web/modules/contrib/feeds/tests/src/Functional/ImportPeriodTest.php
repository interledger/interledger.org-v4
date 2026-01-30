<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\Entity\FeedType;

/**
 * Tests import period settings work with the Feeds module.
 *
 * @group feeds
 */
class ImportPeriodTest extends FeedsBrowserTestBase {

  /**
   * Tests that import period per feed setting is enabled and displayed.
   */
  public function testImportPeriodPerFeedEnabled() {
    // Create a feed type using the upload fetcher.
    $feed_type = $this->createFeedType([
      'import_period_per_feed' => TRUE,
    ]);

    // Assert that settings are saved.
    $feed_type = FeedType::load($feed_type->id());
    $this->assertTrue($feed_type->isImportPeriodPerFeedAllowed());

    // Check feed creation.
    $this->drupalGet('feed/add/' . $feed_type->id());

    // Ensure that the import period per feed field is present and contains
    // the expected options.
    $this->assertSession()->fieldExists('periodic_import');
    $this->assertSession()->optionExists('periodic_import', -2);
    $this->assertSession()->optionExists('periodic_import', -1);
    $this->assertSession()->optionExists('periodic_import', 0);
    $this->assertSession()->optionExists('periodic_import', 900);
    $this->assertSession()->optionExists('periodic_import', 3600);
    $this->assertSession()->optionExists('periodic_import', 2419200);

    // Create a feed with periodic import.
    $source_file = $this->resourcesPath() . '/csv/content.csv';
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'plugin[fetcher][source]' => $this->container->get('file_system')->realpath($source_file),
      'periodic_import' => 3600,
    ];
    $this->submitForm($edit, 'Save');
  }

  /**
   * Tests that import period per feed setting is disabled and not displayed.
   */
  public function testImportPeriodPerFeedDisabled() {
    // Create a feed type using the upload fetcher.
    $feed_type = $this->createFeedType([
      'import_period_per_feed' => FALSE,
    ]);

    // Check feed creation.
    $this->drupalGet('feed/add/' . $feed_type->id());

    // Ensure that the import period per feed field is not present.
    $this->assertSession()->fieldNotExists('periodic_import');
  }

}
