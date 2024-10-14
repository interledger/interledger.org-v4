<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;
use Drupal\feeds\Feeds\Target\StringTarget;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\StateInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\feeds\Traits\FeedsMockingTrait;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Processor\EntityProcessorBase
 * @group feeds
 */
class EntityProcessorBaseTest extends FeedsKernelTestBase {

  use FeedsMockingTrait;

  /**
   * The processor under test.
   *
   * @var \Drupal\feeds\Feeds\Processor\EntityProcessorBase
   */
  protected $processor;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The state.
   *
   * @var \Drupal\feeds\State
   *
   * @todo replace with StateInterface.
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createMock(FeedTypeInterface::class);
    $this->feedType->expects($this->any())
      ->method('getMappings')
      ->willReturn([]);

    $this->processor = new EntityProcessorMock(
      [
        'values' => [
          'type' => 'article',
        ],
        'feed_type' => $this->feedType,
      ],
      'entity:node',
      [
        'id' => 'entity:node',
        'title' => 'Node',
        'description' => 'Creates nodes from feed items.',
        'entity_type' => 'node',
        'form' => [
          'configuration' => 'Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm',
          'option' => 'Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm',
        ],
        'class' => EntityProcessorBase::class,
        'provider' => 'feeds',
        'plugin_type' => 'processor',
      ],
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity_type.bundle.info'),
      \Drupal::service('language_manager'),
      \Drupal::service('datetime.time'),
      \Drupal::service('plugin.manager.action'),
      \Drupal::service('renderer'),
      \Drupal::service('logger.factory')->get('feeds'),
      \Drupal::service('database'),
      \Drupal::service('validation.constraint'),
      \Drupal::service('config.installer'),
    );

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $this->feed->expects($this->any())
      ->method('getState')
      ->with(StateInterface::CLEAN)
      ->willReturn($this->createFeedsCleanState($this->feed->id()));

    $this->state = $this->createFeedsState();
  }

  /**
   * @covers ::process
   */
  public function testProcess() {
    $item = $this->createMock(ItemInterface::class);
    $item->expects($this->any())
      ->method('toArray')
      ->willReturn([]);

    $this->feedType->expects($this->any())
      ->method('getMappedSources')
      ->willReturn([]);

    $this->processor->process($this->feed, $item, $this->state);

    // @todo This method should be tested multiple times with different
    // settings.
    $this->markTestIncomplete('Test is a stub.');
  }

  /**
   * @covers ::clean
   */
  public function testCleanWithKeepNonExistent() {
    // Add feeds_item field to article content type.
    $this->callProtectedMethod($this->processor, 'prepareFeedsItemField');

    // Create an entity with a feeds item field.
    $node = $this->createNodeWithFeedsItem($this->feed);

    // Get hash of node.
    $hash = $node->get('feeds_item')->getItemByFeed($this->feed)->hash;

    // Clean.
    $this->processor->clean($this->feed, $node, $this->createFeedsCleanState($this->feed->id()));

    // Assert that the hash did not change.
    $this->assertEquals($hash, $node->get('feeds_item')->getItemByFeed($this->feed)->hash);
  }

  /**
   * @covers ::clean
   */
  public function testCleanWithUnpublishAction() {
    // Change configuration of processor.
    $config = $this->processor->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->processor->setConfiguration($config);

    // Add feeds_item field to article content type.
    $this->callProtectedMethod($this->processor, 'prepareFeedsItemField');

    // Create an entity with a feeds item field.
    $node = $this->createNodeWithFeedsItem($this->feed);
    // Assert that the node is published.
    $this->assertTrue($node->isPublished());

    // Clean.
    $this->processor->clean($this->feed, $node, $this->createFeedsCleanState($this->feed->id()));

    // Reload node.
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($node->id());

    // Assert that the node is unpublished now.
    $this->assertFalse($node->isPublished());
    // Assert that the hash is now 'entity:unpublish_action:node'.
    $this->assertEquals('entity:unpublish_action:node', $node->get('feeds_item')->getItemByFeed($this->feed)->hash);
  }

  /**
   * @covers ::clean
   */
  public function testCleanWithDeleteAction() {
    // Change configuration of processor.
    $config = $this->processor->getConfiguration();
    $config['update_non_existent'] = EntityProcessorBase::DELETE_NON_EXISTENT;
    $this->processor->setConfiguration($config);

    // Add feeds_item field to article content type.
    $this->callProtectedMethod($this->processor, 'prepareFeedsItemField');

    // Create an entity with a feeds item field.
    $node = $this->createNodeWithFeedsItem($this->feed);
    $this->assertNodeCount(1);

    // Clean.
    $this->processor->clean($this->feed, $node, $this->createFeedsCleanState($this->feed->id()));

    // Assert that the node is deleted.
    $this->assertNodeCount(0);
  }

  /**
   * @covers ::clear
   */
  public function testClear() {
    $this->markTestIncomplete('Test not yet implemented.');
    $this->processor->clear($this->feed, $this->state);
  }

  /**
   * @covers ::entityType
   */
  public function testEntityType() {
    $this->assertEquals('node', $this->processor->entityType());
  }

  /**
   * @covers ::bundleKey
   */
  public function testBundleKey() {
    $this->assertEquals('type', $this->processor->bundleKey());
  }

  /**
   * @covers ::bundle
   */
  public function testBundle() {
    $this->assertEquals('article', $this->processor->bundle());
  }

  /**
   * @covers ::bundleLabel
   */
  public function testBundleLabel() {
    $this->assertEquals('Content type', $this->processor->bundleLabel());
  }

  /**
   * @covers ::bundleOptions
   */
  public function testBundleOptions() {
    $expected = [
      'article' => 'Article',
    ];
    $this->assertEquals($expected, $this->processor->bundleOptions());
  }

  /**
   * @covers ::entityTypeLabel
   */
  public function testEntityTypeLabel() {
    $this->assertEquals('Content', $this->processor->entityTypeLabel());
  }

  /**
   * @covers ::entityTypeLabelPlural
   */
  public function testEntityTypeLabelPlural() {
    $this->assertEquals('content items', $this->processor->entityTypeLabelPlural());
  }

  /**
   * @covers ::getItemLabel
   */
  public function testGetItemLabel() {
    $this->assertEquals('Article', $this->processor->getItemLabel());
  }

  /**
   * @covers ::getItemLabelPlural
   */
  public function testGetItemLabelPlural() {
    $this->assertEquals('Article items', $this->processor->getItemLabelPlural());
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertIsArray($this->processor->defaultConfiguration());
  }

  /**
   * @covers ::onFeedTypeSave
   */
  public function testOnFeedTypeSave() {
    $this->processor->onFeedTypeSave();
  }

  /**
   * @covers ::onFeedTypeDelete
   */
  public function testOnFeedTypeDelete() {
    $this->processor->onFeedTypeDelete();
  }

  /**
   * @covers ::expiryTime
   */
  public function testExpiryTime() {
    $this->assertEquals(EntityProcessorBase::EXPIRE_NEVER, $this->processor->expiryTime());

    // Change the expire setting.
    $config = $this->processor->getConfiguration();
    $config['expire'] = 100;
    $this->processor->setConfiguration($config);
    $this->assertEquals(100, $this->processor->expiryTime());
  }

  /**
   * @covers ::getExpiredIds
   */
  public function testGetExpiredIds() {
    $this->processor->getExpiredIds($this->feed);
  }

  /**
   * @covers ::expireItem
   */
  public function testExpireItem() {
    $item_id = 1;
    $this->processor->expireItem($this->feed, $item_id, $this->state);
  }

  /**
   * @covers ::getItemCount
   */
  public function testGetItemCount() {
    $this->markTestIncomplete('Test not yet implemented.');
    $this->processor->getItemCount($this->feed);
  }

  /**
   * @covers ::getImportedItemIds
   */
  public function testGetImportedItemIds() {
    $feed_type = $this->createFeedType();
    $feed = $this->createFeed($feed_type->id());

    // Create an entity with a feeds item field.
    $node = $this->createNodeWithFeedsItem($feed);

    $expected = [
      $node->id() => $node->id(),
    ];
    $this->assertEquals($expected, $feed_type->getProcessor()->getImportedItemIds($this->feed));

    // Create two other nodes.
    $node2 = $this->createNodeWithFeedsItem($feed);
    $node3 = $this->createNodeWithFeedsItem($feed);

    $expected = [
      $node->id() => $node->id(),
      $node2->id() => $node2->id(),
      $node3->id() => $node3->id(),
    ];
    $this->assertEquals($expected, $feed_type->getProcessor()->getImportedItemIds($this->feed));
  }

  /**
   * @covers ::buildAdvancedForm
   */
  public function testBuildAdvancedForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $this->assertIsArray($this->processor->buildAdvancedForm($form, $form_state));
  }

  /**
   * @covers ::isLocked
   */
  public function testIsLocked() {
    $this->processor->isLocked();
    $this->markTestIncomplete('Test is a stub.');
  }

  /**
   * @covers ::map
   */
  public function testMapWithEmptySource() {
    // Create a new feed type mock.
    $feed_type = $this->createMock(FeedTypeInterface::class);
    $feed_type->expects($this->once())
      ->method('getMappings')
      ->willReturn([
        [
          'target' => 'title',
          'map' => [
            'value' => '',
          ],
        ],
      ]);

    // And set this on the processor.
    $this->setProtectedProperty($this->processor, 'feedType', $feed_type);

    // Instantiate target plugin.
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    $target = new StringTarget(
      [
        'feed_type' => $feed_type,
        'target_definition' => $definition,
      ],
      'string',
      [
        'id' => 'string',
        'field_types' => [
          'string',
          'string_long',
          'list_string',
        ],
      ]
    );

    // And let the feed type always return this plugin.
    $feed_type->expects($this->exactly(2))
      ->method('getTargetPlugin')
      ->willReturn($target);

    // Map.
    $this->callProtectedMethod($this->processor, 'map', [
      $this->feed,
      $this->createMock(EntityInterface::class),
      $this->createMock(ItemInterface::class),
    ]);
  }

  /**
   * @covers ::onFeedDeleteMultiple
   */
  public function testOnFeedDeleteMultiple() {
    // Add feeds_item field to article content type.
    $this->callProtectedMethod($this->processor, 'prepareFeedsItemField');

    $this->processor->onFeedDeleteMultiple([$this->feed]);
    $this->markTestIncomplete('Test is a stub.');
  }

}

/**
 * For testing methods from EntityProcessorBase.
 *
 * Abstract classes cannot be mocked.
 */
class EntityProcessorMock extends EntityProcessorBase {}
