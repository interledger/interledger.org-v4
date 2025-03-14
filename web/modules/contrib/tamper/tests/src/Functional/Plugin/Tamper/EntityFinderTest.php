<?php

namespace Drupal\Tests\tamper\Functional\Plugin\Tamper;

use Drupal\Tests\tamper\Traits\TamperCommonTrait;
use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Tests the Entity Finder plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\EntityFinder
 * @group tamper
 */
class EntityFinderTest extends TamperPluginTestBase {

  use TamperCommonTrait;

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId = 'entity_finder';

  /**
   * {@inheritdoc}
   */
  public static function formDataProvider(): array {
    return [
      'no values' => [
        'expected' => [],
        'edit' => [],
        'errors' => [
          'Entity type field is required.',
        ],
      ],
    ];
  }

  /**
   * Asserts that configuration is saved and stays the same on edit.
   *
   * @param array $expected
   *   The expected Tamper values.
   */
  protected function assertConfigSaved(array $expected) {
    // Assert that the page says that the config is saved.
    $this->assertSession()->pageTextContains('Configuration saved.');

    $this->assertTamperValues($expected);

    // Flush cache in order for the entity to not get served from cache.
    drupal_flush_all_caches();
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Submit the form again with no values and assert that the plugin is still
    // configured the same.
    $this->submitForm([], 'Submit');
    $this->assertTamperValues($expected);
  }

  /**
   * Tests that the plugin can be configured in multiple steps.
   */
  public function testFormInSteps() {
    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Assert that fields 'bundle' and 'field' are not displayed yet.
    $this->assertSession()->fieldNotExists('bundle');
    $this->assertSession()->fieldNotExists('field');

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a field to save the configuration.');

    // Assert that fields are now displayed for bundle and field.
    $this->assertSession()->fieldExists('bundle');
    $this->assertSession()->fieldExists('field');

    // Select a field.
    $edit = ['field' => 'name'];
    $this->submitForm($edit, 'Submit');

    // Assert that the page says that the config is saved.
    $this->assertSession()->pageTextContains('Configuration saved.');

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test',
      'bundle' => NULL,
      'field' => 'name',
      'column' => NULL,
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

  /**
   * Tests that a specific field can be selected after selecting bundle.
   */
  public function testFormWithBundleSelection() {
    $this->createFieldWithStorage('field_text');

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a field to save the configuration.');

    // Now select a bundle.
    $edit = ['bundle' => 'entity_test'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a field to save the configuration.');

    // Select a field.
    $edit = ['field' => 'field_text'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet because there is a column to
    // choose.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a column to save the configuration.');

    // Submit again to save.
    $this->submitForm($edit, 'Submit');

    // Assert that the page says that the config is saved.
    $this->assertSession()->pageTextContains('Configuration saved.');

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field' => 'field_text',
      'column' => 'value',
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

  /**
   * Tests that for some entity types no bundle field appears.
   */
  public function testNoBundleFieldForEntityTypeWithoutBundle() {
    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test_no_bundle'];
    $this->submitForm($edit, 'Submit');

    // Assert that "field" has appeared, but "bundle" has not.
    $this->assertSession()->fieldNotExists('bundle');
    $this->assertSession()->fieldExists('field');

    // Select a field.
    $edit = ['field' => 'name'];
    $this->submitForm($edit, 'Submit');

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test_no_bundle',
      'bundle' => NULL,
      'field' => 'name',
      'column' => NULL,
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

  /**
   * Tests that bundle selection limits the available fields.
   */
  public function testLimitAvailableFieldsOnBundleSelection() {
    // Create two bundles.
    EntityTestBundle::create([
      'id' => 'foo',
      'label' => 'Foo',
    ])->save();
    EntityTestBundle::create([
      'id' => 'bar',
      'label' => 'Bar',
    ])->save();

    // For both bundles, add a field.
    $this->createFieldWithStorage('field_foo', [
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'foo',
    ]);
    $this->createFieldWithStorage('field_bar', [
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bar',
    ]);

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test_with_bundle'];
    $this->submitForm($edit, 'Submit');

    // Assert that both fields are available initially.
    $this->assertSession()->optionExists('field', 'field_foo');
    $this->assertSession()->optionExists('field', 'field_bar');

    // Now select a bundle.
    $edit = ['bundle' => 'bar'];
    $this->submitForm($edit, 'Submit');

    // Assert that field_foo is no longer available, but field_bar is.
    $this->assertSession()->optionNotExists('field', 'field_foo');
    $this->assertSession()->optionExists('field', 'field_bar');
  }

  /**
   * Tests that a column can be selected for certain fields.
   */
  public function testFormWithColumnSelection() {
    $this->createFieldWithStorage('field_text', [
      'type' => 'text_with_summary',
    ]);

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test'];
    $this->submitForm($edit, 'Submit');

    // Select a field.
    $edit = ['field' => 'field_text'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a column to save the configuration.');

    // Select a column.
    $edit = ['column' => 'summary'];
    $this->submitForm($edit, 'Submit');

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test',
      'bundle' => NULL,
      'field' => 'field_text',
      'column' => 'summary',
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

  /**
   * Tests that there is a default column selected.
   */
  public function testFormWithDefaultColumnSelection() {
    $this->createFieldWithStorage('field_text', [
      'type' => 'text_with_summary',
    ]);

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Select the entity type.
    $edit = ['entity_type' => 'entity_test'];
    $this->submitForm($edit, 'Submit');

    // Select a field.
    $edit = ['field' => 'field_text'];
    $this->submitForm($edit, 'Submit');

    // Assert that the config is not saved yet.
    $this->assertSession()->pageTextNotContains('Configuration saved.');
    $this->assertSession()->pageTextContains('Select a column to save the configuration.');

    // Submit again so that a column is saved.
    $this->submitForm([], 'Submit');

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test',
      'bundle' => NULL,
      'field' => 'field_text',
      'column' => 'value',
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

  /**
   * Tests that the selected entity type can be changed.
   */
  public function testChangeEntityTypeSelection() {
    // Set existing configuration.
    $this->entity->setThirdPartySetting('tamper_test', 'tampers', [
      static::$pluginId => [
        'entity_type' => 'entity_test',
        'bundle' => 'entity_test',
        'field' => 'type',
        'column' => NULL,
        'id' => static::$pluginId,
      ],
    ]);
    $this->entity->save();

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    // Assert that a form appears with the selected options.
    $this->assertSession()->fieldValueEquals('entity_type', 'entity_test');
    $this->assertSession()->fieldValueEquals('bundle', 'entity_test');
    $this->assertSession()->fieldValueEquals('field', 'type');

    // Select a different entity type.
    $edit = ['entity_type' => 'entity_test_no_bundle'];
    $this->submitForm($edit, 'Submit');

    // Select a field again.
    $edit = ['field' => 'name'];
    $this->submitForm($edit, 'Submit');

    // Flush cache in order for the entity to not get served from cache.
    drupal_flush_all_caches();

    // Assert that the settings have been saved.
    $expected = [
      'entity_type' => 'entity_test_no_bundle',
      'bundle' => NULL,
      'field' => 'name',
      'column' => NULL,
      'id' => static::$pluginId,
    ];
    $this->assertConfigSaved($expected);
  }

}
