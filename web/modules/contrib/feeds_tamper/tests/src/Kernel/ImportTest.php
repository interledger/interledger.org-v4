<?php

namespace Drupal\Tests\feeds_tamper\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests Feeds Tamper behavior during imports.
 *
 * @group feeds_tamper
 */
class ImportTest extends FeedsTamperKernelTestBase {

  /**
   * A feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The Tamper manager for a feed type.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMeta
   */
  protected $feedTypeTamperMeta;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Get the tamper manager for the just created feed type.
    $this->feedTypeTamperMeta = $this->container->get('feeds_tamper.feed_type_tamper_manager')
      ->getTamperMeta($this->feedType, TRUE);
  }

  /**
   * Tests that a Tamper plugin gets applied upon import.
   */
  public function testApplySingleTamperPlugin() {
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'find_replace',
      'find' => 'ipsum',
      'replace' => 'ipsam',
      'source' => 'title',
    ]);
    $this->feedType->save();

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Assert that the title of the first node got transformed.
    $node = Node::load(1);
    $this->assertEquals('Lorem ipsam', $node->title->value);
  }

  /**
   * Tests that a certain item gets skipped.
   */
  public function testSkipItem() {
    // Add a tamper that should skip items with 'ipsum' in the title.
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'keyword_filter',
      'word_list' => ['ipsum'],
      'invert' => TRUE,
      'source' => 'title',
      'function' => 'mb_stripos',
      'weight' => 0,
    ]);
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'required',
      'source' => 'title',
      'weight' => 1,
    ]);
    $this->feedType->save();

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(1);

    // Assert that only the second item got imported.
    $node = Node::load(1);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node->title->value);
  }

  /**
   * Tests that tampers are applied in the expected order.
   */
  public function testApplyTamperOrder() {
    // Add a few Tamper plugins, but do not create them in the order that they
    // should be applied. The weight setting should dictate in which order they
    // get applied.
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'implode',
      'glue' => '-',
      'weight' => 3,
      'source' => 'title',
    ]);
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'sprintf',
      'format' => '%s foo',
      'weight' => 4,
      'source' => 'title',
    ]);
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'explode',
      'separator' => ' ',
      'weight' => 2,
      'source' => 'title',
    ]);
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'rewrite',
      'text' => '[guid] [title]',
      'weight' => 1,
      'source' => 'title',
    ]);
    $this->feedType->save();

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    $node = Node::load(1);
    $this->assertEquals('1-Lorem-ipsum foo', $node->title->value);

    $node = Node::load(2);
    $this->assertEquals('2-Ut-wisi-enim-ad-minim-veniam foo', $node->title->value);
  }

}
