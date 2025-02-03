<?php

namespace Drupal\Tests\conditional_fields\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldFilledEmptyInterface;
use Drupal\Tests\conditional_fields\FunctionalJavascript\TestCases\ConditionalFieldValueInterface;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Test Conditional Fields Paragraphs Handler.
 *
 * @group conditional_fields
 */
class ConditionalFieldParagraphsTest extends ConditionalFieldTestBase implements
  ConditionalFieldValueInterface,
  ConditionalFieldFilledEmptyInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  protected $screenshotPath = 'sites/simpletest/conditional_fields/paragraphs/';

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'single_textfield';

  /**
   * The target field name.
   *
   * @var string
   */
  protected $targetFieldName = 'field_body';

  /**
   * The target field wrapper selector.
   *
   * @var string
   */
  protected $targetFieldWrap = '';

  /**
   * Jquery selector of field in a document.
   *
   * @var string
   */
  protected $fieldSelector;

  /**
   * Base steps for all javascript tests.
   */
  protected function baseTestSteps() {
    $admin_account = $this->createUser([
      'view conditional fields',
      'edit conditional fields',
      'delete conditional fields',
      'administer nodes',
      'create article content',
      'administer content types',
    ]);

    $this->drupalLogin($admin_account);

    // Visit a ConditionalFields configuration page for test_conditional CT.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()->pageTextContains('Target field');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldSelector = '[name="field_paragraph[0][subform][field_' . $this->fieldName . '][0][value]"]';
    $this->targetFieldWrap = '.field--name-' . str_replace('_', '-', $this->targetFieldName);

    $paragraphsType = ParagraphsType::create([
      'label' => 'Test Conditional',
      'id' => 'test_conditional',
    ]);
    $paragraphsType->save();
    $this->addParagraphsField('test_conditional', 'field_single_textfield', 'string');
    $this->addParagraphsField('test_conditional', 'field_body', 'string');

    $paragraphDisplay = EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'test_conditional',
      'mode' => 'default',
      'status' => TRUE,
    ]);

    $paragraphDisplay->setComponent('field_single_textfield', [
      'type' => 'text_textfield',
      'settings' => [],
    ]);

    $paragraphDisplay->setComponent('field_body', [
      'type' => 'text_textfield',
      'settings' => [],
    ]);
    $paragraphDisplay->save();

    $handler_settings = [
      'target_bundles' => [
        'test_conditional' => 'test_conditional',
      ],
    ];
    $this->createEntityReferenceRevisionsField(
      'node',
      'article',
      'field_paragraph',
      'Paragraphs',
      'default:paragraph',
      $handler_settings,
      1
    );

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_paragraph', [
        'type' => 'paragraphs',
        'settings' => [
          'title' => 'Paragraph',
          'title_plural' => 'Paragraphs',
          'closed_mode' => 'summary',
          'autocollapse' => 'none',
          'closed_mode_threshold' => 0,
          'add_mode' => 'button',
          'form_display_mode' => 'default',
          'edit_mode' => 'default',
          'default_paragraph_type' => 'test_conditional',
          'features' => [
            'add_above' => '0',
            'collapse_edit_all' => 'collapse_edit_all',
            'convert' => '0',
            'duplicate' => 'duplicate',
          ],
        ],
      ])
      ->save();
  }

  /**
   * Creates a field of an entity reference field storage.
   *
   * Creates the field on the specified bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field; if it already exists, a new instance of the
   *   existing field will be created.
   * @param string $field_label
   *   The label of the field.
   * @param string $selection_handler
   *   The selection handler used by this field.
   * @param array $selection_handler_settings
   *   An array of settings supported by the selection handler specified above.
   *   (e.g. 'target_bundles', 'sort', 'auto_create', etc).
   * @param int $cardinality
   *   The cardinality of the field.
   */
  protected function createEntityReferenceRevisionsField($entity_type, $bundle, $field_name, $field_label, $selection_handler = 'default:paragraph', $selection_handler_settings = [], $cardinality = 1) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => 'entity_reference_revisions',
        'entity_type' => $entity_type,
        'cardinality' => $cardinality,
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ])->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => [
          'handler' => $selection_handler,
          'handler_settings' => $selection_handler_settings,
        ],
      ])->save();
    }
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addParagraphsField($paragraph_type_name, $field_name, $field_type, $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueWidget() {

    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    // Set up conditions.
    $text = $this->getRandomGenerator()->word(8);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      'field_' . $this->fieldName . '[0][value]' => $text,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrap);

    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is hidden');

    $this->changeField($this->fieldSelector, $text);
    $this->waitUntilVisible($this->targetFieldWrap, 50, '02. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueRegExp() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
      'regex' => '.*data\=[\d]+.*',
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $text_without_expression = 'The field in not empty';
    $text_with_expression = 'The field has data=2 text';

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrap);

    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');
    $this->changeField($this->fieldSelector, $text_without_expression);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '02. Article Body field is visible');
    $this->changeField($this->fieldSelector, $text_with_expression);
    $this->waitUntilVisible($this->targetFieldWrap, 50, '03. Article Body field is not visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueAnd() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND,
      'values' => implode("\r\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrap);

    $text_false = implode(' ', [$text_1, $text_2]);

    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_false);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '03. Article Body field is visible');

    // Change a value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrap, 50, '04. Article Body field is visible');

  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueOr() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for Content bundles.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    // Random term id to check necessary value.
    $text1 = $this->getRandomGenerator()->word(8);
    $text2 = $this->getRandomGenerator()->word(7);

    // Set up conditions.
    $values = implode("\r\n", [$text1, $text2]);
    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR,
      'values' => $values,
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];
    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');

    // Change value that should not show the body.
    $this->changeField($this->fieldSelector, 'wrong');
    $this->waitUntilHidden($this->targetFieldWrap, 50, '02. Article Body field is visible');

    // Change a value value to show the body.
    $this->changeField($this->fieldSelector, $text1);
    $this->waitUntilVisible($this->targetFieldWrap, 50, '03. Article Body field is not visible');

    // Change a value value to show the body.
    $this->changeField($this->fieldSelector, $text2);
    $this->waitUntilVisible($this->targetFieldWrap, 50, '04. Article Body field is not visible');

    // Change a value value to hide the body again.
    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrap, 50, '05. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueNot() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT,
      'values' => implode("\r\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrap, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'some-unique-text');
    $this->waitUntilVisible($this->targetFieldWrap, 50, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '03. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_2);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '04. Article Body field is visible');

    $this->changeField($this->fieldSelector, "");
    $this->waitUntilVisible($this->targetFieldWrap, 50, '05. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleValueXor() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'value');

    $text_1 = $this->getRandomGenerator()->word(7);
    $text_2 = $this->getRandomGenerator()->word(7);

    $data = [
      'condition' => 'value',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR,
      'values' => implode("\n", [
        $text_1,
        $text_2,
      ]),
      'grouping' => 'AND',
      'state' => 'visible',
      'effect' => 'show',
    ];

    $this->submitForm($data, 'Save settings');

    // Check if that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible value');

    $this->drupalGet('node/add/article');
    $this->assertSession()->elementExists('css', $this->targetFieldWrap);

    $text_false = 'same unique value';

    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_false);
    $this->waitUntilHidden($this->targetFieldWrap, 50, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, $text_1);
    $this->waitUntilVisible($this->targetFieldWrap, 50, '03. Article Body field is not visible');

    $this->changeField($this->fieldSelector, "");
    $this->waitUntilHidden($this->targetFieldWrap, 50, '04. Article Body field is visible');
  }

  /**
   * Tests creating Conditional Field: Visible if isFilled.
   */
  public function testVisibleFilled() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', '!empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible !empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    // Check that the field Body is not visible.
    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrap, 10, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrap, 10, '03. Article Body field is visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testVisibleEmpty() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, 'visible', 'empty');
    // Check that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' visible empty');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrap, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilHidden($this->targetFieldWrap, 10, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilVisible($this->targetFieldWrap, 10, '03. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleFilled() {
    $this->baseTestSteps();
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, '!visible', '!empty');
    // Check that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' !visible !empty');

    $this->drupalGet('node/add/article');

    $this->waitUntilVisible($this->targetFieldWrap, 0, '01. Article Body field is not visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilHidden($this->targetFieldWrap, 10, '02. Article Body field is visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilVisible($this->targetFieldWrap, 10, '03. Article Body field is not visible');
  }

  /**
   * {@inheritdoc}
   */
  public function testInvisibleEmpty() {
    $this->baseTestSteps();

    // Visit a ConditionalFields configuration page for `Article` Content type.
    $this->createCondition($this->targetFieldName, 'field_' . $this->fieldName, '!visible', 'empty');

    // Check that configuration is saved.
    $this->drupalGet('admin/structure/paragraphs_type/test_conditional/conditionals');
    $this->assertSession()
      ->pageTextContains($this->targetFieldName . ' ' . 'field_' . $this->fieldName . ' !visible empty');

    // Visit Article Add form to check that conditions are applied.
    $this->drupalGet('node/add/article');

    $this->waitUntilHidden($this->targetFieldWrap, 0, '01. Article Body field is visible');

    $this->changeField($this->fieldSelector, 'This field is not empty.');
    $this->waitUntilVisible($this->targetFieldWrap, 10, '02. Article Body field is not visible');

    $this->changeField($this->fieldSelector, '');
    $this->waitUntilHidden($this->targetFieldWrap, 10, '03. Article Body field is not visible');
  }

}
