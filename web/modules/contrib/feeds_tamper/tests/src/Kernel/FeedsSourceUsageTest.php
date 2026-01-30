<?php

namespace Drupal\Tests\feeds_tamper\Kernel;

use Drupal\feeds_tamper_test\Feeds\Source\DummySource;
use Drupal\node\Entity\Node;

/**
 * Tests when a FeedsSource plugin is invoked.
 *
 * @group feeds_tamper
 */
class FeedsSourceUsageTest extends FeedsTamperKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'text',
    'filter',
    'feeds',
    'feeds_tamper',
    'feeds_tamper_test',
    'tamper',
  ];

  /**
   * A feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The Tamper manager for a feed type.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface
   */
  protected $feedTypeTamperMeta;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig(['field', 'filter', 'node']);
    $this->createFieldWithStorage('field_alpha');

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
   * Performs an import.
   */
  public function performImport() {
    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);
  }

  /**
   * Tests that the dummy source is not called when not used.
   */
  public function testDummySourceNotUsed() {
    $this->performImport();
    $this->assertFalse(DummySource::$called, 'getSourceElement() should not be called if the source is unused.');
  }

  /**
   * Tests that the dummy source is called when used in mapping.
   */
  public function testDummySourceUsedInMapping() {
    $this->feedType->addMapping([
      'target' => 'field_alpha',
      'map' => ['value' => 'dummy_source:source_context'],
      'settings' => [
        'format' => 'plain_text',
      ],
    ]);
    $this->feedType->save();
    $this->performImport();

    // Assert the the dummy source value is imported.
    $node = Node::load(1);
    $this->assertEquals('context_value', $node->field_alpha->value);

    $this->assertTrue(DummySource::$called, 'getSourceElement() should be called if the source is used in mapping.');
  }

  /**
   * Tests that the dummy source is called when used in a tamper plugin.
   */
  public function testDummySourceUsedInTamperOnly() {
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'feeds_tamper_test_source_user',
      'used_sources' => [
        'dummy_source:source_context',
      ],
      'source' => 'title',
    ]);
    $this->feedType->save();
    $this->performImport();

    // Assert that the dummy source is appended to the title.
    $node = Node::load(1);
    $this->assertEquals('Lorem ipsum;context_value', $node->title->value);

    $this->assertTrue(DummySource::$called, 'getSourceElement() should be called if the source is used in a tamper plugin.');
  }

}
