<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\conditional_fields\Conditions;
use Drupal\conditional_fields\DependencyHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form with a list of conditional fields for an entity type.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldForm extends FormBase {

  /**
   * The route name for a conditional field edit form.
   *
   * @var string
   */
  protected $editPath = 'conditional_fields.edit_form';

  /**
   * The route name for a conditional field delete form.
   *
   * @var string
   */
  protected $deletePath = 'conditional_fields.delete_form';

  /**
   * Form array.
   *
   * @var array
   */
  protected $form;

  /**
   * FormState object.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Name of the entity type being configured.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Name of the entity bundle being configured.
   *
   * @var string
   */
  protected $bundleName;

  public function __construct(
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Conditions $list,
    protected UuidInterface $uuidGenerator,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('conditional_fields.conditions'),
      $container->get('uuid'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL) {
    $this->form = $form;
    $this->formState = $form_state;
    $this->entityType = $entity_type;
    $this->bundleName = $bundle;

    $this->form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $this->entityType,
    ];

    $this->form['bundle'] = [
      '#type' => 'hidden',
      '#value' => $this->bundleName,
    ];

    $this->buildTable();

    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (empty($table['add_new_dependency']) || !is_array($table['add_new_dependency'])) {
      parent::validateForm($form, $form_state);
    }
    $conditional_values = $table['add_new_dependency'];

    if (array_key_exists('dependee', $conditional_values) &&
      array_key_exists('dependent', $conditional_values)
    ) {
      $dependent = $conditional_values['dependent'];
      $state = $conditional_values['state'] ?? NULL;
      $instances = $this->entityFieldManager
        ->getFieldDefinitions($form_state->getValue('entity_type'), $form_state->getValue('bundle'));

      foreach ($dependent as $field) {
        if ($conditional_values['dependee'] == $field) {
          $form_state->setErrorByName('dependee', $this->t('You should select two different fields.'));
          $form_state->setErrorByName('dependent', $this->t('You should select two different fields.'));
        }

        if (!empty($instances[$field]) && $this->requiredFieldIsNotVisible($instances[$field], $state)) {
          $field_instance = $instances[$field];
          $form_state->setErrorByName('state', $this->t('Field %field is required and can not have state %state.', [
            '%field' => $field_instance->getLabel() . ' (' . $field_instance->getName() . ')',
            '%state' => $this->list->conditionalFieldsStates()[$state],
          ]));
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Determine if a field is configured to be required, but not visible.
   *
   * This is considered an error condition as a user would not be able to fill
   * out the field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field to evaluate.
   * @param null|string $state
   *   The configured state for the field.
   *
   * @return bool
   *   TRUE if the field is required but not visible; FALSE otherwise.
   */
  protected function requiredFieldIsNotVisible(FieldDefinitionInterface $field, $state): bool {
    return method_exists($field, 'isRequired') &&
      $field->isRequired() &&
      in_array($state, ['!visible', 'disabled', '!required']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (empty($table['add_new_dependency']) || !is_array($table['add_new_dependency'])) {
      return;
    }

    $field_names = [];
    $form_state->set('plugin_settings_edit', NULL);

    $conditional_values = $table['add_new_dependency'];
    // Copy values from table for submit.
    $component_value = [];
    $settings = $this->list->conditionalFieldsDependencyDefaultSettings();
    foreach ($conditional_values as $key => $value) {
      if ($key == 'dependent') {
        $field_names = $value;
        continue;
      }
      if (in_array($key, ['entity_type', 'bundle', 'dependee'])) {
        $component_value[$key] = $value;
        continue;
      }
      // @todo It seems reasonable to only set values allowed by field schema.
      // @see conditional_fields.schema.yml
      $settings[$key] = $value;
    }
    unset($settings['actions']);
    $component_value['settings'] = $settings;

    $component_value['entity_type'] = $form_state->getValue('entity_type');
    $component_value['bundle'] = $form_state->getValue('bundle');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($component_value['entity_type'] . '.' . $component_value['bundle'] . '.' . 'default');
    if (!$entity) {
      return;
    }

    // Handle one to one condition creating with redirect to edit form.
    if (count($field_names) == 1) {
      $field_name = reset($field_names);
      $uuid = $form_state->getValue('uuid', $this->uuidGenerator->generate());
      $field = $entity->getComponent($field_name);
      $field['third_party_settings']['conditional_fields'][$uuid] = $component_value;
      $entity->setComponent($field_name, $field);
      $entity->save();
      $parameters = [
        'entity_type' => $component_value['entity_type'],
        'bundle' => $component_value['bundle'],
        'field_name' => $field_name,
        'uuid' => $uuid,
      ];
      $form_state->setRedirect($this->editPath, $parameters);
      return;
    }

    // Handle many to one, in that case we always need new uuid.
    foreach ($field_names as $field_name) {
      $uuid = $this->uuidGenerator->generate();
      $field = $entity->getComponent($field_name);
      $field['third_party_settings']['conditional_fields'][$uuid] = $component_value;
      $entity->setComponent($field_name, $field);
    }
    $entity->save();
  }

  /**
   * Builds table with conditional fields.
   */
  protected function buildTable() {
    $table = [
      '#type' => 'table',
      '#entity_type' => $this->entityType,
      '#bundle_name' => $this->bundleName,
      '#header' => [
        $this->t('Target field'),
        $this->t('Controlled by'),
        ['data' => $this->t('Description'), 'colspan' => 2],
        ['data' => $this->t('Operations'), 'colspan' => 2],
      ],
    ];

    $fields = $this->getFieldsList();

    /* Existing conditions. */

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_entity */
    $form_display_entity = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load("$this->entityType.$this->bundleName.default");

    if (is_null($form_display_entity) && $this->entityType == 'taxonomy_term') {
      $form_display_entity = $this->entityTypeManager->getStorage('entity_form_display')->create([
        'targetEntityType' => 'taxonomy_term',
        'bundle' => $this->bundleName,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    if (!$form_display_entity) {
      $this->form['conditional_fields_wrapper']['table'] = $table;
      return;
    }

    foreach ($fields as $field_name => $label) {
      $field = $form_display_entity->getComponent($field_name);
      if (empty($field['third_party_settings']['conditional_fields'])) {
        continue;
      }
      // Create row for existing field's conditions.
      foreach ($field['third_party_settings']['conditional_fields'] as $uuid => $condition) {
        $parameters = [
          'entity_type' => $condition['entity_type'],
          'bundle' => $condition['bundle'],
          'field_name' => $field_name,
          'uuid' => $uuid,
        ];

        $table[] = [
          'dependent' => ['#markup' => $field_name],
          'dependee' => ['#markup' => $condition['dependee']],
          'state' => ['#markup' => $condition['settings']['state']],
          'condition' => ['#markup' => $condition['settings']['condition']],
          'actions' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute($this->editPath, $parameters),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute($this->deletePath, $parameters),
              ],
            ],
          ],
        ];
      }
    }

    /* Row for creating new condition. */

    // Build list of states.
    $states = $this->list->conditionalFieldsStates();

    // Build list of conditions.
    $conditions = [];
    foreach ($this->list->conditionalFieldsConditions() as $condition => $label) {
      $label = (string) $label;
      $conditions[$condition] = $condition == 'value' ? $this->t('has value...') : $this->t('is @label', ['@label' => (string) $label]);
    }

    // Add new dependency row.
    $table['add_new_dependency'] = [
      'dependent' => [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => $this->t('Target field'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Target field'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">' . $this->t('Add new dependency') . '</div>',
        '#required' => TRUE,
        '#attributes' => [
          'class' => ['conditional-fields-selector'],
          'style' => ['resize: both;'],
        ],
      ],
      'dependee' => [
        '#type' => 'select',
        '#title' => $this->t('Controlled by'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Control field'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        '#required' => TRUE,
        '#attributes' => ['class' => ['conditional-fields-selector']],
      ],
      'state' => [
        '#type' => 'select',
        '#title' => $this->t('State'),
        '#title_display' => 'invisible',
        '#options' => $states,
        '#default_value' => 'visible',
        '#prefix' => $this->t('The target field is'),
      ],
      'condition' => [
        '#type' => 'select',
        '#title' => $this->t('Condition'),
        '#title_display' => 'invisible',
        '#options' => $conditions,
        '#default_value' => 'value',
        '#prefix' => $this->t('when the control field'),
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Add dependency'),
        ],
      ],
    ];

    $table['#attached']['library'][] = 'conditional_fields/admin';

    $this->form['conditional_fields_wrapper']['table'] = $table;
  }

  /**
   * Build options list of available fields.
   */
  protected function getFieldsList() {
    $dependency_helper = new DependencyHelper($this->entityType, $this->bundleName, $this->moduleHandler, $this->entityTypeManager);
    $fields = [];
    foreach ($dependency_helper->getAvailableConditionalFields() as $name => $label) {
      $fields[$name] = $label . ' (' . $name . ')';
    }
    return $fields;
  }

}
