<?php

namespace Drupal\Tests\feeds_ex\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests editing the feed type edit form.
 *
 * @group feeds_ex
 */
class FeedTypeEditFormTest extends FeedsExBrowserTestBase {

  /**
   * Tests if configuration is preserved after saving the feed type form.
   */
  public function testFeedTypeEdit() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      'parser' => 'xml',
      'parser_configuration' => [
        'context' => [
          'value' => '//item',
        ],
        'sources' => [
          'guid' => [
            'label' => 'guid',
            'value' => 'guid',
          ],
          'title' => [
            'label' => 'title',
            'value' => 'title',
          ],
        ],
      ],
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
    ]);

    // Save feed type.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id());
    // @todo figure out why Drupal cannot find user 0:
    // > "The referenced entity (user: 0) does not exist."
    $edit = [
      'processor_configuration[owner_id]' => '',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that the config has remained intact by doing an import now.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/content.xml',
    ]);
    $this->batchImport($feed);
    $this->assertSession()->pageTextContains('Created 2 Article items.');

    // Assert node values.
    $node1 = Node::load(1);
    $this->assertEquals('1', $node1->feeds_item->guid);
    $this->assertEquals('Lorem ipsum', $node1->getTitle());
    $node2 = Node::load(2);
    $this->assertEquals('2', $node2->feeds_item->guid);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node2->getTitle());
  }

  /**
   * Tests configuring the 'source_encoding' setting.
   */
  public function testConfigureEncoding() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      'parser' => 'xml',
    ]);

    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id());
    // @todo figure out why Drupal cannot find user 0:
    // > "The referenced entity (user: 0) does not exist."
    $edit = [
      'processor_configuration[owner_id]' => '',
      'parser_configuration[encoding][source_encoding]' => 'ASCII, BASE64',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that the new encoding is saved.
    $feed_type = $this->reloadEntity($feed_type);
    $parser_configuration = $feed_type->getParser()->getConfiguration();
    $this->assertEquals(['ASCII', 'BASE64'], $parser_configuration['source_encoding']);

    // Edit again, but also use a non-existing encoding option in between.
    $edit = [
      'processor_configuration[owner_id]' => '',
      'parser_configuration[encoding][source_encoding]' => 'UTF-8, Banana, ASCII',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that the option 'Banana' is not saved.
    $feed_type = $this->reloadEntity($feed_type);
    $parser_configuration = $feed_type->getParser()->getConfiguration();
    $this->assertEquals(['UTF-8', 'ASCII'], $parser_configuration['source_encoding']);

    // Now only input a non-existing encoding.
    $edit = [
      'processor_configuration[owner_id]' => '',
      'parser_configuration[encoding][source_encoding]' => 'Banana',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that the encoding is set to 'auto'.
    $feed_type = $this->reloadEntity($feed_type);
    $parser_configuration = $feed_type->getParser()->getConfiguration();
    $this->assertEquals(['auto'], $parser_configuration['source_encoding']);
  }

}
