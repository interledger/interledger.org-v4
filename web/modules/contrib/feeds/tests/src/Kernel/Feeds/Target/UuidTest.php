<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Uuid
 * @group feeds
 */
class UuidTest extends FeedsKernelTestBase {

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * Node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->nodeStorage = $this->entityTypeManager->getStorage('node');

    // Create and configure the feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'title' => 'title',
      'uuid' => 'uuid',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'uuid',
          'map' => ['value' => 'uuid'],
          'unique' => ['value' => TRUE],
        ],
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
      ],
    ]);
  }

  /**
   * Basic test loading a double-entry CSV file.
   */
  public function test() {
    // Import the CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/uuid.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(4);

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Lorem ipsum']);
    $this->assertCount(1, $nodes);
    $node = array_shift($nodes);
    $this->assertEquals('bfe2cffc-f86a-493f-8ccc-5017fac1f382', $node->uuid());

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Ut wisi enim ad minim veniam']);
    $this->assertCount(1, $nodes);
    $node = array_shift($nodes);
    $this->assertEquals('eb8dc174-ceb7-47e9-8ec6-daa03b165c83', $node->uuid());

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Node with no UUID 1']);
    $this->assertCount(1, $nodes);
    $node = array_shift($nodes);
    // Granted by Drupal core, just double-checking.
    $this->assertNotEmpty($node->uuid());

    // Import the CSV file with updated UUIDs.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/uuid_updated.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(6);

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Lorem ipsum updated']);
    $this->assertCount(1, $nodes);
    $node = array_shift($nodes);
    $this->assertEquals('bfe2cffc-f86a-493f-8ccc-5017fac1f382', $node->uuid());

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Node with no UUID 1']);
    $this->assertCount(2, $nodes);

    $nodes = $this->nodeStorage->loadByProperties(['title' => 'Node with no UUID 2']);
    $this->assertCount(2, $nodes);
  }

}
