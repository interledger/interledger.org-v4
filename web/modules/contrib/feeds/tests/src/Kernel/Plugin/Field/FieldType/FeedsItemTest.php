<?php

namespace Drupal\Tests\feeds\Kernel\Plugin\Field\FieldType;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\Field\FieldType\FeedsItem
 * @group feeds
 */
class FeedsItemTest extends FeedsKernelTestBase {

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed to test with.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create feeds_item field.
    $this->createFieldWithStorage('feeds_item', [
      'type' => 'feeds_item',
      'label' => 'Feeds item',
    ]);

    // Create a feed type and feed.
    $this->feedType = $this->createFeedType();
    $this->feed = $this->createFeed($this->feedType->id());
  }

  /**
   * Creates a node object to test with.
   *
   * @return \Drupal\node\NodeInterface
   *   A node object.
   */
  protected function feedsCreateNode(): NodeInterface {
    $node = Node::create([
      'title'  => 'Lorem ipsum',
      'type'  => 'article',
      'uid'  => 0,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Tests setting feeds_item with an integer.
   */
  public function testSetValueWithInt() {
    $node = $this->feedsCreateNode();
    $node->set('feeds_item', $this->feed->id());

    $expected = [
      [
        'target_id' => $this->feed->id(),
      ],
    ];
    $this->assertEquals($expected, $node->feeds_item->getValue());
  }

  /**
   * Tests setting feeds_item with an array.
   */
  public function testSetValueWithArray() {
    $node = $this->feedsCreateNode();
    $node->set('feeds_item', [
      'target_id' => $this->feed->id(),
    ]);

    $expected = [
      [
        'target_id' => $this->feed->id(),
      ],
    ];
    $this->assertEquals($expected, $node->feeds_item->getValue());
  }

  /**
   * Tests setting feeds_item with an object.
   */
  public function testSetValueWithObject() {
    $node = $this->feedsCreateNode();
    $node->set('feeds_item', $this->feed);

    $expected = [
      [
        'target_id' => $this->feed->id(),
      ],
    ];
    $this->assertEquals($expected, $node->feeds_item->getValue());
  }

  /**
   * Tests setting an empty url on the feeds_item field.
   */
  public function testSetValueWithEmptyUrl() {
    $node = $this->feedsCreateNode();
    $node->set('feeds_item', [
      'url' => '',
      'target_id' => $this->feed->id(),
    ]);

    $expected = [
      [
        'url' => NULL,
        'target_id' => $this->feed->id(),
      ],
    ];
    $this->assertEquals($expected, $node->feeds_item->getValue());
  }

}
