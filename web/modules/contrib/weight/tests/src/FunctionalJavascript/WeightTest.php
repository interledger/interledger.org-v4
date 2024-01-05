<?php

namespace Drupal\Tests\weight\FunctionalJavascript;

use Behat\Mink\Exception\ExpectationException;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Tests\ViewTestData;
use Drupal\node\Entity\Node;

/**
 * Test basic functionality of weight.
 *
 * @group weight
 */
class WeightTest extends WebDriverTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'weight',
    'field_ui',
    'weight_test_views',
  ];
  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected static $fieldName = 'field_weight';

  /**
   * Type of the field.
   *
   * @var string
   */
  protected static $fieldType = 'weight';

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $testViews = [
    'test_weight',
    'test_weight_first_position',
    'test_weight_grouped',
  ];

  /**
   * Array of nodes used to tests.
   *
   * @var array
   */
  public $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(
      [
        'administer content types',
        'administer node fields',
        'administer node display',
      ]
    );

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => static::$fieldName,
      'entity_type' => 'node',
      'type' => static::$fieldType,
    ]);
    $fieldStorage->save();
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'article',
      'required' => FALSE,
    ]);
    $field->save();

    $nodes[] = [
      'type' => 'article',
      'title' => 'Article 1',
      'field_weight' => -20,
    ];
    $nodes[] = [
      'type' => 'article',
      'title' => 'Article 2',
      'field_weight' => -19,
    ];
    $nodes[] = [
      'type' => 'article',
      'title' => 'Article 3',
      'field_weight' => -18,
    ];
    $nodes[] = [
      'type' => 'article',
      'title' => 'Article 4',
      'field_weight' => -17,
    ];

    // Create nodes.
    foreach ($nodes as $n) {
      $node = $this->drupalCreateNode($n);
      $node->save();
      $this->nodes[] = $node;
    }

    ViewTestData::createTestViews(get_class($this), ['weight_test_views']);
  }

  /**
   * Test basic weight ordering.
   */
  public function testWeightSelectorBase() {
    $this->drupalGet('test-weight');
    $page = $this->getSession()->getPage();

    $weight_select1 = $page->findField("field_weight[0][weight]");
    $weight_select2 = $page->findField("field_weight[1][weight]");
    $weight_select3 = $page->findField("field_weight[2][weight]");
    $weight_select4 = $page->findField("field_weight[3][weight]");

    // Check that rows weight selects are hidden.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());
    $this->assertFalse($weight_select3->isVisible());
    $this->assertFalse($weight_select4->isVisible());

    // Check that 'Article 2' row is heavier than 'Article 1' row.
    $this->assertGreaterThan($weight_select1->getValue(), $weight_select2->getValue());

    // Check that 'Article 1' precedes the 'Article 2'.
    $this->assertOrderInPage(['Article 1', 'Article 2']);

    // Check that the 'unsaved changes' text is not present in the message area.
    $this->assertSession()->pageTextNotContains('You have unsaved changes.');

    // Drag and drop the 'Article 1' row over the 'Article 2' row.
    // @todo Test also the reverse, 'Article 2' over 'Article 1', when
    // https://www.drupal.org/node/2769825 is fixed.
    // @see https://www.drupal.org/node/2769825
    $dragged = $this->xpath("//tr[@class='draggable'][1]//a[@class='tabledrag-handle']")[0];
    $target = $this->xpath("//tr[@class='draggable'][2]//a[@class='tabledrag-handle']")[0];
    $dragged->dragTo($target);

    // Give javascript some time to manipulate the DOM.
    $this->assertJsCondition('jQuery(".tabledrag-changed-warning").is(":visible")');

    // Check that the 'unsaved changes' text appeared in the message area.
    $this->assertSession()->pageTextContains('You have unsaved changes.');

    // Check that 'Article 2' page precedes the 'Article 1'.
    $this->assertOrderInPage(['Article 2', 'Article 1']);

    $this->submitForm([], 'Save');
    // @todo Fix the send message after saving the changes.
    // Check that page reordering was done in the backend for drag-n-drop.
    $page1 = Node::load($this->nodes[0]->id());
    $page2 = Node::load($this->nodes[1]->id());
    $this->assertGreaterThan($page2->field_weight->getString(), $page1->field_weight->getString());

    // Check again that 'Article 2' is on top after form submit in the UI.
    $this->assertOrderInPage(['Article 2', 'Article 1']);

    // Toggle row weight selects as visible.
    $page->findButton('Show row weights')->click();

    // Check that rows weight selects are visible.
    $this->assertTrue($weight_select1->isVisible());
    $this->assertTrue($weight_select2->isVisible());
    // Check that 'Article 1' row became heavier than 'Article 2' row.
    $this->assertGreaterThan($weight_select1->getValue(), $weight_select2->getValue());

    // Reverse again using the weight fields. Use the current values so the test
    // doesn't rely on knowing the values in the select boxes.
    $value1 = $weight_select1->getValue();
    $value2 = $weight_select2->getValue();
    $weight_select1->setValue($value2);
    $weight_select2->setValue($value1);

    // Toggle row weight selects back to hidden.
    $page->findButton('Hide row weights')->click();

    // Check that rows weight selects are hidden again.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());

    // @todo Fix the send message after saving the changes.
    $this->submitForm([], 'Save');

    // Check that the 'Article 1' is first again.
    $this->assertOrderInPage(['Article 1', 'Article 2']);

    // Check that page reordering was done in the backend for manual weight
    // field usage.
    $page1 = Node::load($this->nodes[0]->id());
    $page2 = Node::load($this->nodes[1]->id());
    $this->assertGreaterThan($page2->field_weight->getString(), $page1->field_weight->getString());

    // Check if the weight selector appear when is position in the view is the
    // first column.
    $this->drupalGet('test-weight-first-position');
    $page = $this->getSession()->getPage();

    $weight_select1 = $page->findField("field_weight[0][weight]");
    $weight_select2 = $page->findField("field_weight[1][weight]");
    $weight_select3 = $page->findField("field_weight[2][weight]");
    $weight_select4 = $page->findField("field_weight[3][weight]");

    // Check that rows weight selects are hidden.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());
    $this->assertFalse($weight_select3->isVisible());
    $this->assertFalse($weight_select4->isVisible());

    // Drag and drop the 'Article 1' row over the 'Article 2' row.
    // @todo Test also the reverse, 'Article 2' over 'Article 1', when
    // https://www.drupal.org/node/2769825 is fixed.
    // @see https://www.drupal.org/node/2769825
    $dragged = $this->xpath("//tr[@class='draggable'][1]//a[@class='tabledrag-handle']")[0];
    $target = $this->xpath("//tr[@class='draggable'][2]//a[@class='tabledrag-handle']")[0];
    $dragged->dragTo($target);

    // Give javascript some time to manipulate the DOM.
    $this->assertJsCondition('jQuery(".tabledrag-changed-warning").is(":visible")');

    // Check that the 'unsaved changes' text appeared in the message area.
    $this->assertSession()->pageTextContains('You have unsaved changes.');

    // Check that 'Article 2' page precedes the 'Article 1'.
    $this->assertOrderInPage(['Article 2', 'Article 1']);

    $this->submitForm([], 'Save');
    // @todo Fix the send message after saving the changes.
    // Test weight with a grouped views.
    $this->drupalGet('test-weight-grouped');
    $page = $this->getSession()->getPage();

    $weight_select1 = $page->findField("field_weight[0][weight]");
    $weight_select2 = $page->findField("field_weight[1][weight]");
    $weight_select3 = $page->findField("field_weight[2][weight]");
    $weight_select4 = $page->findField("field_weight[3][weight]");

    // Check that rows weight selects are hidden.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());
    $this->assertFalse($weight_select3->isVisible());
    $this->assertFalse($weight_select4->isVisible());
  }

  /**
   * Asserts that several pieces of markup are in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When any of the given string is not found.
   *
   * @todo Remove this once https://www.drupal.org/node/2817657 is committed.
   */
  protected function assertOrderInPage(array $items) {
    $session = $this->getSession();
    $text = $session->getPage()->getHtml();
    $strings = [];
    foreach ($items as $item) {
      if (($pos = strpos($text, $item)) === FALSE) {
        throw new ExpectationException("Cannot find '$item' in the page", $session->getDriver());
      }
      $strings[$pos] = $item;
    }
    ksort($strings);
    $ordered = implode(', ', array_map(function ($item) {
      return "'$item'";
    }, $items));
    $this->assertSame($items, array_values($strings), "Found strings, ordered as: $ordered.");
  }

}
