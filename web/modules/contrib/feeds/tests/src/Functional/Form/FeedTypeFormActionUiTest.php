<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\feeds\Entity\FeedType;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests the Feed type form with actions.
 *
 * @group feeds
 */
class FeedTypeFormActionUiTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'feeds',
    'feeds_test_broken',
  ];

  /**
   * Tests that an action can be set for the "update_non_existent" setting.
   */
  public function testSetUpdateNonExistent(): void {
    // Create a feed type.
    $this->drupalGet('/admin/structure/feeds/add');

    // Create initial values.
    $edit = [
      'label' => 'Test feed type',
      'id' => 'test_feed',
      'processor' => 'entity:node',
      'processor_configuration[owner_id]' => 'admin (1)',
    ];

    $this->submitForm($edit, 'Save and add mappings');

    // Set action to "unpublish".
    $this->drupalGet('admin/structure/feeds/manage/test_feed');
    $edit = [
      'processor_configuration[update_non_existent]' => 'entity:unpublish_action:node',
    ];
    $this->submitForm($edit, 'Save');

    // Ensure that the action has changed.
    $feed_type = FeedType::load('test_feed');
    $config = $feed_type->getProcessor()->getConfiguration();
    $this->assertSame('entity:unpublish_action:node', $config['update_non_existent']);
  }

}
