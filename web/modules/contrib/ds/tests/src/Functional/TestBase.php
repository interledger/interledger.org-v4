<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use \Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Base test for Display Suite.
 *
 * @group ds
 */
abstract class TestBase extends BrowserTestBase {

  use DsTestTrait;
  use EntityReferenceFieldCreationTrait;
  use FieldUiTestTrait;
  use TaxonomyTestTrait;
  use StringTranslationTrait;

  protected $defaultTheme = 'starterkit_theme';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'field_ui',
    'taxonomy',
    'block',
    'ds',
    'ds_extras',
    'ds_test',
    'ds_switch_view_mode',
    'layout_discovery',
    'field_group',
  ];

  /**
   * The label for a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldLabel;

  /**
   * The input name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldNameInput;

  /**
   * The name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The created taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The created user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access user profiles',
      'admin classes',
      'admin display suite',
      'admin fields',
      'administer nodes',
      'view all revisions',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer taxonomy',
      'administer taxonomy_term fields',
      'administer taxonomy_term display',
      'administer users',
      'administer permissions',
      'administer account settings',
      'administer user display',
      'administer software updates',
      'access site in maintenance mode',
      'administer site configuration',
      'bypass node access',
      'ds switch view mode',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create random field name.
    $this->fieldLabel = $this->randomMachineName(8);
    $this->fieldNameInput = strtolower($this->randomMachineName(8));
    $this->fieldName = 'field_' . $this->fieldNameInput;

    // Create Article node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
      'revision' => TRUE,
    ]);
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Page',
      'revision' => TRUE,
    ]);

    // Create a vocabulary named "Tags".
    $this->vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->vocabulary->save();

    $term1 = Term::create([
      'name' => 'Tag 1',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term1->save();

    $term2 = Term::create([
      'name' => 'Tag 2',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term2->save();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      // Enable auto-create.
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', 'field_' . $this->vocabulary->id(), 'Tags', 'taxonomy_term', 'default', $handler_settings, 10);

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
         ->load('node.article.default');

    $display->setComponent('field_' . $this->vocabulary->id())->save();
  }

  /**
   * Check to see if two trimmed values are equal.
   *
   * @param $first
   *   First element to compare
   * @param $second
   *   Second element to compare
   * @param string $message
   *   The message
   */
  protected function assertTrimEqual($first, $second, $message = '') {
    $first = (string) $first;
    $second = (string) $second;

    $this->assertEquals(trim($first), trim($second), $message);
  }

}
