<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\StateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests importing a feed with an invalid item.
 *
 * @group feeds
 */
class InvalidItemTest extends FeedsKernelTestBase {

  /**
   * The process state after an import.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $processState;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $columns = [
      'guid' => 'guid',
      'title' => 'title',
    ];

    $this->feedType = $this->createFeedTypeForCsv($columns);

    // Setup an event dispatcher. We use this to check the number of created and
    // updated items after an import.
    $this->container->get('event_dispatcher')->addListener(FeedsEvents::IMPORT_FINISHED, [
      $this,
      'importFinished',
    ]);
  }

  /**
   * Event callback for the 'feeds.import_finished' event.
   *
   * Sets the processState property, so that the tests can read this.
   *
   * @param \Drupal\feeds\Event\ImportFinishedEvent $event
   *   The Feeds event that was dispatched.
   */
  public function importFinished(ImportFinishedEvent $event) {
    $this->processState = $event->getFeed()->getState(StateInterface::PROCESS);
  }

  /**
   * Tests a feed import where one item is invalid.
   */
  public function testFeedWithInvalidItem(): void {
    // Register a temporary event listener to inject an invalid item.
    $this->injectInvalidItem($this->container->get('event_dispatcher'));

    // Create a feed and import the CSV as well as the dynamically added
    // items.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Only the valid items should be imported.
    $this->assertNodeCount(3);

    $node = Node::load(3);
    $this->assertEquals('Valid title', $node->label());

    $invalid_node = Node::load(4);
    $this->assertNull($invalid_node, 'Invalid item should not be imported.');

    // Assert that the invalid item was reported.
    $messages = \Drupal::messenger()->all();
    $this->assertStringContainsString('This item is invalid.', (string) $messages['warning'][0]);

    // Clear the logged messages so no failure is reported on tear down.
    $this->logger->clearMessages();
  }

  /**
   * Injects a listener for FeedsEvents::PARSE that marks one item as invalid.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  protected function injectInvalidItem(EventDispatcherInterface $dispatcher): void {
    $dispatcher->addListener(FeedsEvents::PARSE, function (ParseEvent $event) {
      $parser_result = $event->getParserResult();

      // Create a valid item.
      $valid_item = new DynamicItem();
      $valid_item->fromArray([
        'guid' => '3',
        'title' => 'Valid title',
      ]);

      // Create an invalid item.
      $invalid_item = new DynamicItem();
      $invalid_item->fromArray([
        'guid' => '4',
        'title' => 'Invalid title',
      ]);
      $invalid_item->markInvalid('This item is invalid.');

      $parser_result->addItem($valid_item);
      $parser_result->addItem($invalid_item);
    }, FeedsEvents::AFTER);
  }

}
