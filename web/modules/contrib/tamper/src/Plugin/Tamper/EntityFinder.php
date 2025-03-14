<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the entity finder plugin.
 *
 * @Tamper(
 *   id = "entity_finder",
 *   label = @Translation("Entity Finder"),
 *   description = @Translation("Finds an entity based on columns and fields. Returns the ID of the entity."),
 *   category = "Other",
 *   handle_multiples = TRUE
 * )
 */
class EntityFinder extends TamperBase implements ContainerFactoryPluginInterface {

  const SETTING_ENTITY_TYPE = 'entity_type';
  const SETTING_BUNDLE = 'bundle';
  const SETTING_FIELD = 'field';
  const SETTING_COLUMN = 'column';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Constructs an EntityFinder tamper plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tamper\SourceDefinitionInterface $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceDefinitionInterface $source_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $source_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $configuration['source_definition'],
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_ENTITY_TYPE] = '';
    $config[self::SETTING_BUNDLE] = '';
    $config[self::SETTING_FIELD] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="tamper-entity-finder-wrapper">';
    $form['#suffix'] = '</div>';

    $subform_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;

    // Gets the button that triggers the ajax call.
    $values = [];
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      $values = $subform_state->getValues();

      // Retrieve the parents, so we can climb back up the tree and do not have
      // to hard code the unknown position of our subform.
      $parents = array_slice($triggering_element['#array_parents'], 0, -1);
      if ($parents) {
        $values = NestedArray::getValue($values, $parents);
      }
    }

    $entity_types = $this->getEntityTypes();
    $form[self::SETTING_ENTITY_TYPE] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entity_types,
      '#default_value' => $this->getSetting(self::SETTING_ENTITY_TYPE),
      '#ajax' => [
        'callback' => [$this, 'changeSelect'],
        'event' => 'change',
        'wrapper' => 'tamper-entity-finder-wrapper',
      ],
      '#required' => TRUE,
      '#empty_option' => $this->t('-- Select --'),
    ];

    // Find the current selected entity type.
    $selected_entity_type = $this->formGetValue(self::SETTING_ENTITY_TYPE, $values);
    if ($selected_entity_type === '' || $selected_entity_type === NULL) {
      // If no entity type is selected yet, return the form early.
      return $form;
    }

    // Check if the selected entity type exists. Return form if it doesn't.
    if (!$this->entityTypeManager->getDefinition($selected_entity_type) instanceof EntityTypeInterface) {
      $form[self::SETTING_ENTITY_TYPE]['#options'][$selected_entity_type] = $this->t('Error: "@entity_type" not found', [
        '@entity_type' => $selected_entity_type,
      ]);
      return $form;
    }

    $selected_bundle = NULL;
    if ($this->entityTypeSupportBundles($selected_entity_type)) {
      $bundles = $this->getBundles($selected_entity_type);

      $form[self::SETTING_BUNDLE] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundles,
        '#default_value' => $this->getSetting(self::SETTING_BUNDLE),
        '#ajax' => [
          'callback' => [$this, 'changeSelect'],
          'event' => 'change',
          'wrapper' => 'tamper-entity-finder-wrapper',
        ],
        '#empty_option' => $this->t('Any'),
      ];

      // Find the current selected bundle.
      $selected_bundle = $this->formGetValue(self::SETTING_BUNDLE, $values);
    }

    // Gather field definitions.
    $fields = $this->getFields($selected_entity_type, $selected_bundle);

    $form[self::SETTING_FIELD] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $fields,
      '#default_value' => $this->getSetting(self::SETTING_FIELD),
      '#ajax' => [
        'callback' => [$this, 'changeSelect'],
        'event' => 'change',
        'wrapper' => 'tamper-entity-finder-wrapper',
      ],
      '#empty_option' => $this->t('-- Select --'),
    ];

    // Find the current selected field and make sure that it appears in the
    // current list.
    $selected_field = $this->formGetValue(self::SETTING_FIELD, $values);
    $selected_field = isset($fields[$selected_field]) ? $selected_field : NULL;

    if (!is_string($selected_field) || strlen($selected_field) < 1) {
      return $form;
    }

    $columns = $this->getFieldColumns($selected_entity_type, $selected_bundle, $selected_field);
    // Only display selector if there are at least two options.
    if (count($columns) < 2) {
      return $form;
    }

    $form[self::SETTING_COLUMN] = [
      '#type' => 'select',
      '#title' => $this->t('column'),
      '#options' => $columns,
      '#default_value' => $this->getSetting(self::SETTING_COLUMN),
    ];

    return $form;
  }

  /**
   * Ajax callback for form changes.
   */
  public function changeSelect(array &$form, FormStateInterface $form_state) {
    // Gets the button that triggers the ajax call.
    $triggering_element = $form_state->getTriggeringElement();
    // Retrieve the parents, so we can climb back up the tree
    // and do not have to hard code the unknown position of our subform.
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);

    // Use NestedArray to get the element in `$form` at
    // the path that `$parents` describes.
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // When JavaScript is disabled, the form needs to become multistep. Some
    // changes on the form, like selecting an other entity type, have effect on
    // options available in other form elements. We need to ensure that when a
    // certain option is no longer valid because of changing entity types,
    // bundles or fields, that the setting in question is emptied, so that no
    // invalid settings are getting saved.
    $selected_entity_type = $form_state->getValue(self::SETTING_ENTITY_TYPE);
    $selected_bundle = $form_state->getValue(self::SETTING_BUNDLE);
    $selected_field = $form_state->getValue(self::SETTING_FIELD);
    $selected_column = $form_state->getValue(self::SETTING_COLUMN);

    if (is_string($selected_entity_type) && strlen($selected_entity_type) > 0) {
      // Check if the selected bundle is still valid when changing entity types.
      if (is_string($selected_bundle) && strlen($selected_bundle) > 0) {
        if (!isset($this->getBundles($selected_entity_type)[$selected_bundle])) {
          // Empty bundle selection.
          $form_state->setValue(self::SETTING_BUNDLE, NULL);
          $selected_bundle = NULL;
        }
      }

      // Check if the selected field is still valid when changing bundles or
      // entity types.
      if (is_string($selected_field) && strlen($selected_field) > 0) {
        if (!isset($this->getFields($selected_entity_type, $selected_bundle)[$selected_field])) {
          // Empty field selection.
          $form_state->setValue(self::SETTING_FIELD, NULL);
          $selected_field = NULL;
        }
      }

      // Check if the selected column is still valid when changing bundles or
      // entity types.
      if (is_string($selected_column) && strlen($selected_column) > 0) {
        if ($selected_field === NULL || !isset($this->getFieldColumns($selected_entity_type, $selected_bundle, $selected_field)[$selected_column])) {
          // Empty column selection.
          $form_state->setValue(self::SETTING_COLUMN, NULL);
        }
      }
    }

    // If no field has been chosen yet, rebuild the form.
    if (empty($form_state->getValue(self::SETTING_FIELD))) {
      $this->messenger()->addStatus($this->t('Select a field to save the configuration.'));
      $form_state->setRebuild();
      return;
    }

    // If no column has been chosen, but there are choices for it, rebuild the
    // form.
    if (empty($selected_column)
      && isset($selected_entity_type)
      && isset($selected_field)
      && count($this->getFieldColumns($selected_entity_type, $selected_bundle, $selected_field)) > 1
    ) {
      $this->messenger()->addStatus($this->t('Select a column to save the configuration.'));
      $form_state->setRebuild();
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Set bundle to null if no value is selected.
    $bundle = $form_state->getValue(self::SETTING_BUNDLE);
    if (is_string($bundle) && strlen($bundle) < 1) {
      $bundle = NULL;
    }

    $this->setConfiguration([
      self::SETTING_ENTITY_TYPE => $form_state->getValue(self::SETTING_ENTITY_TYPE),
      self::SETTING_BUNDLE => $bundle,
      self::SETTING_FIELD => $form_state->getValue(self::SETTING_FIELD),
      self::SETTING_COLUMN => $form_state->getValue(self::SETTING_COLUMN),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    $entity_type_id = $this->getSetting(self::SETTING_ENTITY_TYPE);
    $bundle = $this->getSetting(self::SETTING_BUNDLE);
    $field = $this->getSetting(self::SETTING_FIELD);
    $column = $this->getSetting(self::SETTING_COLUMN);

    $query = $this->entityTypeManager->getStorage($entity_type_id)
      ->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 1);

    // Limit search by bundle if there is one (e.g. user has none).
    if ($bundle && ($bundleKey = $this->getBundleKey($entity_type_id))) {
      $query->condition($bundleKey, $bundle, '=');
    }

    // Limit search by a field column, if configured.
    if ($column) {
      $query->condition($field . '.' . $column, $data);
    }
    else {
      $query->condition($field, $data);
    }

    $ids = array_filter($query->execute());

    return $ids ? reset($ids) : NULL;
  }

  /**
   * Searches for the correct form value.
   *
   * Returns the requested setting from the values array if it exist, with a
   * fallback to the current saved setting.
   */
  protected function formGetValue(string $key, array $values) {
    return $values[$key] ?? $this->getSetting($key);
  }

  /**
   * Get all content entity types.
   *
   * @return array
   *   A list of all content entity types.
   */
  protected function getEntityTypes() {
    // Get some info on entity types.
    $entity_types = [];
    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $machine_name => $entity_type) {
      // @todo should this work for config entity types as well?
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      $entity_types[$entity_type->getProvider()][$machine_name] = $entity_type->getLabel();
    }

    // Sort entity types.
    ksort($entity_types);
    foreach ($entity_types as $provider => &$types) {
      natcasesort($types);
    }

    return $entity_types;
  }

  /**
   * Returns if the given entity type supports bundles.
   *
   * @param string $entity_type_id
   *   The entity type to check the bundle for.
   *
   * @return bool
   *   True if the entity type has bundle support. False otherwise.
   */
  protected function entityTypeSupportBundles(string $entity_type_id): bool {
    return $this->entityTypeManager->getDefinition($entity_type_id)->hasKey('bundle');
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @param string $entity_type_id
   *   The entity type to check the bundle for.
   *
   * @return string|false
   *   The bundle key of the entity type or false if the entity type
   *   does not support bundles.
   */
  protected function getBundleKey(string $entity_type_id) {
    return $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle');
  }

  /**
   * Get the bundles for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID to get bundles for.
   *
   * @return array
   *   A list of bundles.
   */
  protected function getBundles(string $entity_type_id): array {
    $bundles = [];

    if ($entity_type_id) {
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundle_info as $machine_name => $info) {
        $bundles[$machine_name] = $info['label'];
      }
    }

    // Sort bundles.
    natcasesort($bundles);

    return $bundles;
  }

  /**
   * Returns the fields for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to get fields for.
   * @param string|null $bundle
   *   The bundle to get fields for or null in case of no bundle.
   *
   * @return array
   *   A list of fields.
   */
  protected function getFields(string $entity_type_id, ?string $bundle): array {
    $fields = [];

    foreach ($this->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      $fields[$field_name] = $field_definition->getLabel();
    }

    // Sort fields.
    natcasesort($fields);

    return $fields;
  }

  /**
   * Returns the field definitions for an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to get field definitions for.
   * @param string|null $bundle
   *   The bundle to get field definitions for or null in case of no bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   A list of fields definitions.
   */
  protected function getFieldDefinitions(string $entity_type_id, ?string $bundle): array {
    if (strlen($entity_type_id) < 1) {
      return [];
    }

    $field_definitions = [];
    if (!$this->entityTypeSupportBundles($entity_type_id)) {
      // Return only base field definitions.
      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    }
    else {
      // Check if a bundle is selected. If so, return fields only for that
      // bundle.
      if (is_string($bundle) && strlen($bundle) > 0) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
      }
      else {
        // No bundle is selected, display fields for all bundles.
        $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        foreach ($bundle_info as $bundle => $info) {
          $field_definitions += $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
        }
      }
    }

    return $field_definitions;
  }

  /**
   * Returns the columns for a certain field.
   *
   * @param string $entity_type_id
   *   The entity type ID to get field columns for.
   * @param string|null $bundle
   *   The bundle that the field is in.
   * @param string $field_name
   *   The name of the field.
   *
   * @return array
   *   A list of field columns.
   */
  protected function getFieldColumns(string $entity_type_id, ?string $bundle, string $field_name): array {
    $definitions = $this->getFieldDefinitions($entity_type_id, $bundle);
    if (!isset($definitions[$field_name])) {
      return [];
    }

    $columns = [];
    $column_definitions = $definitions[$field_name]->getFieldStorageDefinition()->getColumns();
    foreach ($column_definitions as $column_name => $column_definition) {
      $columns[$column_name] = $column_name;
    }

    return $columns;
  }

}
