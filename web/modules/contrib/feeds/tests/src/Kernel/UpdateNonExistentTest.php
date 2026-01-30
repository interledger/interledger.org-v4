<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\StateInterface;

/**
 * Tests the feature of updating items that are no longer available in the feed.
 *
 * @group feeds
 */
class UpdateNonExistentTest extends FeedsKernelTestBase {

  use NodeCreationTrait;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'options',
    'entity_test',
  ];

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
   * Asserts number of items on the clean list for the given feed.
   *
   * @param int $expected_count
   *   The amount of expected items on the clean list.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to check the clean list for.
   */
  protected function assertCleanListCount($expected_count, FeedInterface $feed) {
    $count = $this->container->get('database')
      ->select('feeds_clean_list')
      ->fields('feeds_clean_list', [])
      ->condition('feed_id', $feed->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals($expected_count, $count);
  }

  /**
   * Asserts that no items exist on the clean list for the given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to check the clean list for.
   */
  protected function assertCleanListEmpty(FeedInterface $feed) {
    $this->assertCleanListCount(0, $feed);
  }

  /**
   * Tests 'Unpublish non-existent' option.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'entity:unpublish_action:node'.
   */
  public function testUnpublishNonExistentItems() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that one was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $feed->import();
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $feed->import();
    $node = $this->reloadEntity($node);
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());

    // Import an "updated" version of the file with no results.
    $node = $this->getNodeByTitle('Obama wants to fast track a final health care bill - USA Today');
    $this->assertTrue($node->isPublished(), 'Node is published');

    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_empty.rss2');
    $feed->save();
    $feed->import();

    // All the nodes should have been unpublished.
    $node = $this->getNodeByTitle('Obama wants to fast track a final health care bill - USA Today');
    $this->assertFalse($node->isPublished(), 'Node is unpublished');

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);
  }

  /**
   * Tests 'Delete non-existent' option.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete'.
   */
  public function testDeleteNonExistentItems() {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that one node is removed.
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);

    // Import an "updated" version of the file with no results.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_empty.rss2');
    $feed->save();
    $feed->import();

    // Assert that all the nodes have been removed.
    static::assertEquals(0, $feed->getItemCount());
    $this->assertNodeCount(0);

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $feed->import();
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests cleaning feeds with different clean settings in the same request.
   *
   * This test creates two feed types:
   * - One where the 'update_non_existent' setting is set to '_delete';
   * - One where the 'update_non_existent' setting is set to
   * 'entity:unpublish_action:node'.
   *
   * First, content is imported for both of them. After that, for both feeds
   * a source is imported where one item is removed. This should initiate a
   * clean stage for both of them. Then it is checked if the
   * 'update_non_existent' setting is applied correctly for both feeds.
   */
  public function testCleanMultipleFeeds() {
    // Set 'update_non_existent' setting for the first feed type to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a second feed type. This feed type imports CSV files instead of
    // RSS and the 'update_non_existent' setting is set to 'unpublish'.
    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'name' => 'name',
    ], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'update_non_existent' => 'entity:unpublish_action:node',
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
          'settings' => [],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'name'],
        ],
      ],
    ]);

    // For both feed types, create a feed and import the first file.
    $feed1 = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed1->import();
    $feed2 = $this->createFeed($feed_type2->id(), [
      'source' => $this->resourcesPath() . '/csv/content_string_id.csv',
    ]);
    $feed2->import();

    // Assert amount of imported items.
    static::assertEquals(6, $feed1->getItemCount());
    static::assertEquals(2, $feed2->getItemCount());
    $this->assertNodeCount(8);

    // For both feeds, set a source file from which one item is removed.
    $feed1->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed1->save();
    $feed2->setSource($this->resourcesPath() . '/csv/content_string_id_missing.csv');
    $feed2->save();

    // And now import both feeds, which should initiate a clean stage for
    // both of them. One node should get removed (from feed 1) and one node
    // should get unpublished (from feed 2).
    $feed1->import();
    $feed2->import();

    // Assert that one node was removed.
    static::assertEquals(5, $feed1->getItemCount());
    static::assertEquals(2, $feed2->getItemCount());
    $this->assertNodeCount(7);

    // Assert that one was unpublished.
    $node = $this->getNodeByTitle('Minnesota');
    $this->assertFalse($node->isPublished());
  }

  /**
   * Tests if the feeds clean list gets empty after clearing states.
   */
  public function testEmptyCleanListAfterClearingStates() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Add two records to the feeds_clean_list table for this feed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    $clean_state->setList([123, 456]);
    $this->assertCleanListCount(2, $feed);

    // Clear states.
    $feed->clearStates();

    // Assert that the clean list is now empty for this feed.
    $this->assertCleanListEmpty($feed);
  }

  /**
   * Tests if entities in the clean list can have a ID that is a string.
   */
  public function testStringIdInCleanList() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Test that clean list is creating entries.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    $clean_state->setList(['MN', 'WI']);
    $this->assertCleanListCount(2, $feed);
  }

  /**
   * Tests if the feeds clean list gets empty after deleting feed.
   *
   * There could exist records on the clean list if an import ends abruptly, for
   * example.
   */
  public function testEmptyCleanListAfterDeletingFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Add two records to the feeds_clean_list table for this feed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    $clean_state->setList([123, 456]);
    $this->assertCleanListCount(2, $feed);

    // Delete the feed.
    $feed->delete();

    // Assert that the clean list is now empty for this feed.
    $this->assertCleanListEmpty($feed);
  }

  /**
   * Tests cleaning an item when using a long action name.
   */
  public function testWithCustomActionPlugin() {
    $this->installModule('feeds_test_plugin');
    $this->feedType = $this->reloadEntity($this->feedType);

    // Set 'update_non_existent' setting to an action plugin with a long name.
    // The long name affects the hash that gets set when cleaning. The hash
    // value can only be maximal 32 characters long. By using a long action name
    // we can ensure that cleaning will continue to work as expected.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:feeds_test_plugin_clean_action_long_name:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import the first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that node 6 was one time flagged as "cleaned".
    $cleaned = \Drupal::state()->get('feeds_cleaned', []);
    $this->assertEquals(1, $cleaned[6]['feeds_test_plugin_clean_action'], 'Item should only be cleaned once.');

    // Import the same file again to ensure that the node does not get cleaned
    // again (since the node was already cleaned during the previous import).
    $feed->import();
    $cleaned = \Drupal::state()->get('feeds_cleaned', []);
    $this->assertEquals(1, $cleaned[6]['feeds_test_plugin_clean_action'], 'Item should only be cleaned once.');
  }

  /**
   * Tests cleaning entities when the entity ID is a string.
   */
  public function testCleanWithEntityStringId() {
    $this->installEntitySchema('entity_test_string_id');

    // Create and configure feed type.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'name' => 'name',
    ], [
      'processor' => 'entity:entity_test_string_id',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'update_non_existent' => ProcessorInterface::DELETE_NON_EXISTENT,
        'values' => [
          'type' => 'entity_test_string_id',
        ],
      ],
      'mappings' => [
        [
          'target' => 'id',
          'map' => ['value' => 'guid'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'name',
          'map' => ['value' => 'name'],
        ],
      ],
    ]);

    // Create a feed and import the first file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content_string_id.csv',
    ]);

    $feed->import();

    // Assert that two entity_test_string_id entities have been imported.
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_string_id');
    $storage->resetCache();
    $entities = $storage->loadMultiple();
    $this->assertCount(2, $entities);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/csv/content_string_id_missing.csv');
    $feed->save();
    $feed->import();

    // Assert that one entity is removed.
    $storage->resetCache();
    $entities = $storage->loadMultiple();
    $this->assertCount(1, $entities);

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);
  }

}
