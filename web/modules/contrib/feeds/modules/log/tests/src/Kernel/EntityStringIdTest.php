<?php

namespace Drupal\Tests\feeds_log\Kernel;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests for an Entity with a string ID target.
 *
 * @group feeds
 */
class EntityStringIdTest extends FeedsLogKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds_log',
    'views',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('entity_test_string_id');

    // Create and configure feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'name' => 'name',
    ], [
      'processor' => 'entity:entity_test_string_id',
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'entity_test_string_id',
        ],
      ],
      'mappings' => [
        [
          'target' => 'id',
          'map' => ['value' => 'guid'],
        ],
        [
          'target' => 'name',
          'map' => ['value' => 'name'],
        ],
      ],
    ]);
  }

  /**
   * Tests importing a feed with string IDs.
   */
  public function testImportFeed() {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_string_id.csv',
      'feeds_log' => TRUE,
    ]);

    $feed->import();

    // Test that import log is creating entries.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertCount(2, $import_log->getLogEntries());
  }

}
