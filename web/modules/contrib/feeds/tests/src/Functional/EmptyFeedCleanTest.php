<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\Entity\FeedType;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Tests that invalid or empty sources do not trigger the Feeds clean stage.
 *
 * If a fetch yields an empty source file *or* the source fetch fails,
 * previously imported items MUST NOT be cleaned. A clean action can for example
 * be deleting or unpublishing items. This test verifies that those actions are
 * skipped when the source file is empty or non-existent.
 *
 * @group feeds
 */
class EmptyFeedCleanTest extends FeedsBrowserTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected FeedType $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);
  }

  /**
   * Tests that empty files do not trigger delete all imported items via clean.
   */
  public function testEmptyFileDoesNotDeleteAllItems(): void {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import the first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Created 6 Article items.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an invalid empty feed.
    // This throws an EmptyFeedException internally, and should not do any
    // content cleanups.
    $feed->setSource($this->resourcesPath() . '/rss/empty.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that all the original nodes are still present.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests that empty files do not trigger a clean() during cron runs.
   */
  public function testEmptyFileDoesNotDeleteAllItemsWithCron(): void {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an invalid empty feed.
    $feed->setSource($this->resourcesPath() . '/rss/empty.rss2');
    $feed->save();
    $this->cronRun();

    // Assert that no nodes were removed.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests cleanup behavior with HTTP responses.
   */
  public function testNonExistentFileDoesNotDeleteAllItems() {
    // Install module that will throw a 304 when the same data is fetched again.
    $this->container->get('module_installer')->install(['feeds_test_files']);
    $this->rebuildContainer();

    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'fetcher' => 'http',
      'fetcher_configuration' => [],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'update_non_existent' => ProcessorInterface::DELETE_NON_EXISTENT,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);

    // Create a feed and import the nodes.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->getBaseUrl() . '/testing/feeds/nodes.csv',
    ]);
    $this->batchImport($feed);

    // Assert that 8 items were imported.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(8, $feed->getItemCount());
    $this->assertNodeCount(8);

    // Attempt an import with no known changes.
    $feed->setSource($this->getBaseUrl() . '/testing/feeds/304.csv');
    $feed->save();

    $this->batchImport($feed);

    // Assert that all 8 items still exist even if there are no changes.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(8, $feed->getItemCount());
    $this->assertNodeCount(8);

    $feed->setSource($this->getBaseUrl() . '/testing/feeds/nonexistent.csv');
    $feed->save();

    $this->batchImport($feed);

    // Assert that all 8 items still exist even when there's a 404 HTTP error.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(8, $feed->getItemCount());
    $this->assertNodeCount(8);
  }

}
