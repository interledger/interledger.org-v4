<?php

namespace Drupal\Tests\tamper\FunctionalJavascript\Plugin\Tamper;

use Drupal\Tests\tamper\Traits\TamperCommonTrait;
use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Tests the Entity Finder plugin with JS.
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
   * Tests that form elements are updated when selecting an entity type.
   */
  public function testFieldAppearance() {
    // Create two bundles.
    EntityTestBundle::create([
      'id' => 'foo',
      'label' => 'Foo',
    ])->save();
    EntityTestBundle::create([
      'id' => 'bar',
      'label' => 'Bar',
    ])->save();

    // Add two fields, one text field and one that has multiple columns.
    $this->createFieldWithStorage('field_foo', [
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'foo',
    ]);
    $this->createFieldWithStorage('field_bar', [
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bar',
      'type' => 'text_with_summary',
    ]);

    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Assert that the fields "bundle", "field" and "column" are not visible
    // yet.
    $assert_session->fieldNotExists('bundle');
    $assert_session->fieldNotExists('field');
    $assert_session->fieldNotExists('column');

    // Select an entity type from the list.
    $assert_session->fieldExists('entity_type');
    $page->selectFieldOption('entity_type', 'entity_test_with_bundle');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that "bundle" and "field" have appeared.
    $assert_session->fieldExists('bundle');
    $assert_session->fieldExists('field');

    // Assert that the two created fields are available as options.
    $assert_session->optionExists('field', 'field_foo');
    $assert_session->optionExists('field', 'field_bar');

    // Select a bundle and assert that the list of fields get updated.
    $page->selectFieldOption('bundle', 'bar');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSession()->optionNotExists('field', 'field_foo');
    $this->assertSession()->optionExists('field', 'field_bar');

    // Select a field and assert that the "column" field now appears.
    $page->selectFieldOption('field', 'field_bar');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('column');

    // Assert that there are three options for the "column" field.
    $this->assertSession()->optionExists('column', 'value');
    $this->assertSession()->optionExists('column', 'summary');
    $this->assertSession()->optionExists('column', 'format');
  }

  /**
   * Tests that for some entity types no bundle field appears.
   */
  public function testNoBundleFieldForEntityTypeWithoutBundle() {
    // Go to the page for configuring the plugin.
    $this->drupalGet('/tamper_test/test/' . static::$pluginId);

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Select an entity type from the list that does not support bundles.
    $assert_session->fieldExists('entity_type');
    $page->selectFieldOption('entity_type', 'entity_test_no_bundle');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that "field" has appeared, but "bundle" has not.
    $assert_session->fieldNotExists('bundle');
    $assert_session->fieldExists('field');
  }

}
