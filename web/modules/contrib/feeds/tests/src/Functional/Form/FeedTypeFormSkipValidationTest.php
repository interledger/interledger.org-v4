<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests skip validation workflow on the edit feed type form.
 *
 * @group feeds
 */
class FeedTypeFormSkipValidationTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'block',
  ];

  /**
   * Tests enabling the option "skip_validation".
   *
   * When only enabling "skip_validation" and not selecting any validation
   * types, the "skip_validation_types" setting should remain empty.
   */
  public function testEnableSkipValidationWithoutTypes() {
    // Create the feed type that we're going to edit in the UI.
    $feed_type = $this->createFeedType([
      'label' => 'Test Feed Type Skip Validation',
      'id' => 'test_feed_type_skip_val_one',
      'description' => 'A test for feed type creation.',
      'help' => 'A help text for this feed type.',
    ]);

    // Go to the feed types list.
    $this->drupalGet('admin/structure/feeds');

    // Assert that the created feed type exists in the feed types list.
    $this->assertSession()->pageTextContains('Test Feed Type Skip Validation');

    // Go to the created feed type edit page.
    $this->drupalGet('admin/structure/feeds/manage/test_feed_type_skip_val_one');

    // Assert that we're on the feed type edit page.
    $this->assertSession()->pageTextContains('Edit Test Feed Type Skip Validation');

    // Enable the option "skip_validation".
    $edit = [
      'processor_configuration[owner_id]' => 'admin (1)',
      'processor_configuration[skip_validation]' => TRUE,
    ];
    $this->submitForm($edit, 'Save feed type');

    // Check if changes were saved.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Reload the updated feed type.
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that processor skip validation settings are updated.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that skip_validation checkbox is selected.
    $this->assertTrue($processor['skip_validation']);
    // Assert that skip_validation_types are empty.
    $this->assertEmpty($processor['skip_validation_types']);
  }

  /**
   * Tests enabling the option "skip_validation" plus selecting a few types.
   */
  public function testEnableSkipValidationWithTypes() {
    // Create the feed type that we're going to edit in the UI.
    $feed_type = $this->createFeedType([
      'label' => 'Test Feed Type Skip Validation',
      'id' => 'test_feed_type_skip_val_two',
      'description' => 'A test for feed type creation.',
      'help' => 'A help text for this feed type.',
    ]);

    // Go to the feed types list.
    $this->drupalGet('admin/structure/feeds');

    // Assert that the created feed type exists in the feed types list.
    $this->assertSession()->pageTextContains('Test Feed Type Skip Validation');

    // Go to the created feed type edit page.
    $this->drupalGet('admin/structure/feeds/manage/test_feed_type_skip_val_two');

    // Assert that we're on the feed type edit page.
    $this->assertSession()->pageTextContains('Edit Test Feed Type Skip Validation');

    // Enable the option "skip_validation" and select a few validation types.
    $edit = [
      'processor_configuration[owner_id]' => 'admin (1)',
      'processor_configuration[skip_validation]' => TRUE,
      'processor_configuration[skip_validation_types][Length]' => 1,
      'processor_configuration[skip_validation_types][Blank]' => 1,
      'processor_configuration[skip_validation_types][Count]' => 1,
    ];
    $this->submitForm($edit, 'Save feed type');

    // Check if changes were saved.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Reload the updated feed type.
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that processor skip validation settings are updated.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that skip_validation checkbox is selected.
    $this->assertTrue($processor['skip_validation']);
    // Assert that specific skip_validation_types were enabled.
    $this->assertContains('Length', $processor['skip_validation_types']);
    $this->assertContains('Blank', $processor['skip_validation_types']);
    $this->assertContains('Count', $processor['skip_validation_types']);
  }

  /**
   * Tests disabling the option "skip_validation".
   *
   * Disabling the option "skip_validation" should automatically disable all
   * selected validation types as well.
   */
  public function testDisableSkipValidation() {
    // Create the feed type that we're going to edit in the UI.
    // For this feed type, the option "skip_validation" is already enabled and
    // also a few validation types are selected.
    $feed_type = $this->createFeedType([
      'label' => 'Test Feed Type Skip Validation',
      'id' => 'test_feed_type_skip_val_three',
      'description' => 'A test for feed type creation.',
      'help' => 'A help text for this feed type.',
      'processor_configuration' => [
        'values' => [
          'type' => 'article',
        ],
        'owner_id' => 1,
        'skip_validation' => TRUE,
        'skip_validation_types' => [
          'Length',
          'Blank',
          'Count',
        ],
      ],
    ]);

    // Go to the feed types list.
    $this->drupalGet('admin/structure/feeds');

    // Assert that the created feed type exists in the feed types list.
    $this->assertSession()->pageTextContains('Test Feed Type');

    // Go to the created feed type edit page.
    $this->drupalGet('admin/structure/feeds/manage/test_feed_type_skip_val_three');

    // Assert that we're on the feed type edit page.
    $this->assertSession()->pageTextContains('Edit Test Feed Type Skip Validation');

    $edit = [
      'label' => 'Edited Feed Type Skip Validation',
    ];
    $this->submitForm($edit, 'Save feed type');

    // Check if changes were saved.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Reload the updated feed type.
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that processor skip validation settings are updated.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that skip_validation checkbox is selected.
    $this->assertTrue($processor['skip_validation']);
    // Assert that specific skip_validation_types were still checked.
    $this->assertContains('Length', $processor['skip_validation_types']);
    $this->assertContains('Blank', $processor['skip_validation_types']);
    $this->assertContains('Count', $processor['skip_validation_types']);

    // Go to the created feed type edit page.
    $this->drupalGet('admin/structure/feeds/manage/test_feed_type_skip_val_three');

    // Assert that we're on the feed type edition page.
    $this->assertSession()->pageTextContains('Edited Feed Type Skip Validation');

    // Disable the option "skip_validation". This should disable all selected
    // validation types as well.
    $edit = [
      'processor_configuration[skip_validation]' => FALSE,
    ];
    $this->submitForm($edit, 'Save feed type');

    // Check if changes were saved.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Reload the updated feed type.
    $feed_type = $this->reloadEntity($feed_type);
    // Assert that processor skip validation settings are updated.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that skip_validation checkbox is not selected.
    $this->assertFalse($processor['skip_validation']);
    // Assert that skip_validation_types are empty.
    $this->assertEmpty($processor['skip_validation_types']);
  }

}
