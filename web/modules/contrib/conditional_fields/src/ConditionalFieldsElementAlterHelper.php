<?php

namespace Drupal\conditional_fields;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Helper to alter the element info.
 */
class ConditionalFieldsElementAlterHelper {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Provides an interface for entity type managers.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConditionalFieldsElementAlterHelper constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Processes form elements with dependencies.
   *
   * Just adds a #conditional_fields property to the form with the needed
   * data, which is used later in
   * \Drupal\conditional_fields\ConditionalFieldsFormHelper::afterBuild():
   * - The fields #parents property.
   * - Field dependencies data.
   */
  public function afterBuild(array $element, FormStateInterface &$form_state) {
    // A container with a field widget.
    // Element::children() is probably a better fit.
    if (isset($element['widget'])) {
      $field = $element['widget'];
    }
    else {
      $field = $element;
    }

    $first_parent = reset($field['#parents']);

    // No parents, so bail out.
    if (!isset($first_parent) || (isset($field['#type']) && $field['#type'] == 'value')) {
      return $element;
    }

    $is_related_to_paragraph = FALSE;

    $full_form = &$form_state->getCompleteForm();
    // Some fields do not have entity type and bundle properties.
    // In this case we try to use the properties from the form.
    // This is not an optimal solution, since in case of fields
    // in entities within entities they might not correspond,
    // and their dependencies will not be loaded.
    $build_info = $form_state->getBuildInfo();
    if (method_exists($build_info['callback_object'], 'getEntity')) {
      $entity = $build_info['callback_object']->getEntity();
      if ($entity instanceof EntityInterface) {
        // Detect if this element is located inside inline_entity_form.
        if ($inline_entity_form_parents = ConditionalFieldsFormHelper::findInlineEntityFormParentsForElement($full_form, $field)) {
          $form = &NestedArray::getValue($full_form, $inline_entity_form_parents['element_parents']);

          // Remove outer forms from field parents arrays.
          $field = ConditionalFieldsFormHelper::fieldRemoveInlineEntityFormParents($field, $inline_entity_form_parents);

          $ief_entity = $form["#entity"];
          $bundle = $ief_entity->bundle();
          $entity_type = $ief_entity->getEntityTypeId();
        }
        else {
          $form = &$full_form;
          $bundle = $entity->bundle();
          $entity_type = $entity->getEntityTypeId();

          // Deprecated, not actual from Drupal 8.7.0.
          // Media entity returns the actual bundle object, rather than id.
          if (is_object($bundle) && method_exists($bundle, 'getPluginId')) {
            $bundle = $bundle->getPluginId();
          }

          $paragraph_bundle = $this->getParagraphBundle($field, $form);
          $bundle = $paragraph_bundle ?: $bundle;
          $is_related_to_paragraph = (bool) $paragraph_bundle;
          $entity_type = $is_related_to_paragraph ? 'paragraph' : $entity_type;
        }

        $dependencies = $this->loadDependencies($entity_type, $bundle);

        if (!$dependencies) {
          return $element;
        }
        // We only add requirement on the widget parent and not on child.
        if (
          count($field['#array_parents']) > 1 &&
          $field['#array_parents'][count($field['#array_parents']) - 2] === 'widget' &&
          is_int($field['#array_parents'][count($field['#array_parents']) - 1])
        ) {
          return $element;
        }
        $field_name = reset($field['#array_parents']);

        // We get the name of the field inside the the paragraph where the
        // conditions are being applied, instead of the field name where the
        // paragraph is.
        if ($is_related_to_paragraph) {
          foreach ($field['#array_parents'] as $parent) {
            if (isset($dependencies['dependents'][$parent])) {
              $field_name = $parent;
              break;
            }

            if (isset($dependencies['dependees'][$parent])) {
              $field_name = $parent;
              break;
            }
          }

          if ($parent != $field_name || $first_parent == $field_name || !isset($field['#type'])) {
            return $element;
          }
        }

        $paragraph_info = [];

        if ($is_related_to_paragraph) {
          $paragraph_info['entity_type'] = $entity_type;
          $paragraph_info['bundle'] = $bundle;
          $paragraph_info['paragraph_field'] = $first_parent;
          $paragraph_info['array_parents'] = $element['#array_parents'];
        }

        // Attach dependent.
        if (isset($dependencies['dependents'][$field_name])) {
          foreach ($dependencies['dependents'][$field_name] as $id => $dependency) {
            if (!isset($form['#conditional_fields'][$field_name]['dependees'][$id]) || $this->isPriorityField($field)) {
              if ($is_related_to_paragraph) {
                $paragraph_info['field'] = $field_name;
              }
              $this->attachDependency($form, $form_state, ['#field_name' => $dependency['dependee']], $field, $dependency['options'], $id, $paragraph_info);
            }
          }
        }

        // Attach dependee.
        if (isset($dependencies['dependees'][$field_name])) {
          foreach ($dependencies['dependees'][$field_name] as $id => $dependency) {
            if (!isset($form['#conditional_fields'][$field_name]['dependents'][$id]) || $this->isPriorityField($field)) {
              if ($is_related_to_paragraph) {
                $paragraph_info['field'] = $field_name;
              }
              $this->attachDependency($form, $form_state, $field, ['#field_name' => $dependency['dependent']], $dependency['options'], $id, $paragraph_info);
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * Gets the paragraph's bundle from a form field.
   */
  public function getParagraphBundle($field, $form) {
    $closest_parent = [];
    $last_subform = FALSE;

    // Finds closest subform position.
    foreach ($field['#array_parents'] as $index => $parent) {
      if ($parent === 'subform') {
        $last_subform = $index;
      }
    }

    if (!$last_subform) {
      return FALSE;
    }

    // Gets the route to the closest subform.
    $closest_parent = array_slice($field['#array_parents'], 0, $last_subform);
    // Gets the paragraph's bundle if any...
    $bundle = NestedArray::getValue($form, array_merge($closest_parent, ['#paragraph_type']));

    return $bundle && !is_array($bundle) ? $bundle : FALSE;
  }

  /**
   * Loads all dependencies from the database for a given bundle.
   */
  public function loadDependencies($entity_type, $bundle) {
    static $dependency_helpers;
    $dependency_key = $entity_type . '.' . $bundle;
    if (!isset($dependency_helpers[$dependency_key])) {
      $dependency_helpers[$dependency_key] =
        new DependencyHelper($entity_type, $bundle, $this->moduleHandler, $this->entityTypeManager);
    }
    return $dependency_helpers[$dependency_key]->getBundleDependencies();
  }

  /**
   * Checking if field is priority for rewrite the conditions.
   *
   * If the field widget is datelist this function help to return correct object
   * for this field.
   *
   * @param array $field
   *   The field form element.
   *
   * @return bool
   *   Check the fields is priority and return the boolean result
   */
  public function isPriorityField(array $field) {
    $priority_fields = [
      'datelist',
    ];
    // For modules supports.
    $this->moduleHandler->alter(['conditional_fields_priority_field'], $priority_fields);

    if (isset($field['#type']) && in_array($field['#type'], $priority_fields)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Attaches a single dependency to a form.
   *
   * Call this function when defining or altering a form to create dependencies
   * dynamically.
   *
   * @param array $form
   *   The form where the dependency is attached.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $dependee
   *   The dependee field form element. Either a string identifying the element
   *   key in the form, or a fully built field array. Actually used properties
   *   of the array are #field_name and #parents.
   * @param string $dependent
   *   The dependent field form element. Either a string identifying the element
   *   key in the form, or a fully built field array. Actually used properties
   *   of the array are #field_name and #field_parents.
   * @param array $options
   *   An array of dependency options with the following key/value pairs:
   *   - state: The state applied to the dependent when the dependency is
   *     triggered. See conditionalFieldsStates() for available states.
   *   - condition: The condition for the dependency to be triggered. See
   *     conditionalFieldsConditions() for available conditions.
   *   - values_set: One of the following constants:
   *     - ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
   *       Dependency is triggered if the dependee has a certain value defined
   *       in 'value'.
   *     - ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
   *       Dependency is triggered if the dependee has all the values defined in
   *       'values'.
   *     - ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
   *       Dependency is triggered if the dependee has any of the values defined
   *       in 'values'.
   *     - ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
   *       Dependency is triggered if the dependee has only one of the values
   *       defined in 'values'.
   *     - ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
   *       Dependency is triggered if the dependee does not have any of the
   *       values defined in 'values'.
   *   - value: The value to be tested when 'values_set' is
   *     ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET.
   *     An associative array with the same structure of the dependee field
   *     values as found in $form_states['values] when the form is submitted.
   *     You can use field_default_extract_form_values() to extract this array.
   *   - values: The array of values to be tested when 'values_set' is not
   *     ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET.
   *   - value_form: An associative array with the same structure of the
   *     dependee field values as found in
   *     $form_state['input']['value']['field'] when the form is submitted.
   *   - effect: The jQuery effect associated to the state change. See
   *     conditionalFieldsEffects() for available effects and options.
   *   - effect_options: The options for the active effect.
   *   - selector: (optional) Custom jQuery selector for the dependee.
   * @param int $id
   *   (internal use) The identifier for the dependency. Omit this parameter
   *   when attaching a custom dependency.
   * @param array $paragraph_info
   *   @todo Document parameter.
   *
   *   Note that you don't need to manually set all these options, since default
   *   settings are always provided.
   */
  public function attachDependency(array &$form, &$form_state, $dependee, $dependent, array $options, $id = 0, $paragraph_info = []) {
    // The absence of the $id parameter identifies a custom dependency.
    if (!$id) {
      // String values are accepted to simplify usage of this function with
      // custom forms.
      if (is_string($dependee) && is_string($dependent)) {
        $dependee = [
          '#field_name' => $dependee,
          '#parents' => [$dependee],
        ];
        $dependent = [
          '#field_name' => $dependent,
          '#field_parents' => [$dependent],
        ];

        // Custom dependencies have automatically assigned a progressive id.
        static $current_id;
        if (!$current_id) {
          $current_id = 1;
        }
        $id = $current_id;
        $current_id++;
      }
    }

    // Attach dependee.
    // Use the #array_parents property of the dependee instead of #field_parents
    // since we will need access to the full structure of the widget.
    if (isset($dependee['#array_parents'])) {

      $dependee_index = $dependee['#parents'][0];
      if ($paragraph_info) {
        $dependee_index = conditional_fields_get_simpler_id($dependee['#id']);
      }

      $form['#conditional_fields'][$dependee_index]['index'] = $dependee_index;
      $form['#conditional_fields'][$dependee_index]['base'] = $dependee_index;

      $form['#conditional_fields'][$dependee_index]['is_from_paragraph'] = (bool) $paragraph_info;
      $form['#conditional_fields'][$dependee_index]['parents'] = $dependee['#array_parents'];
      $form['#conditional_fields'][$dependee_index]['dependents'][$id] = [
        'dependent' => $dependent['#field_name'],
        'options' => $options,
      ];
    }

    // Attach dependent.
    if (!empty($dependent['#parents'])) {
      $dependent_parents = $dependent['#parents'];
      // If the field type is Date, we need to remove the last "date" parent
      // key, since it is not part of the $form_state value when we validate it.
      if (isset($dependent['#type']) && $dependent['#type'] === 'date') {
        array_pop($dependent_parents);
      }
    }
    elseif (isset($dependent['#field_parents'])) {
      $dependent_parents = $dependent['#field_parents'];
    }

    if (isset($dependent_parents)) {
      $dependent_index = $dependent['#parents'][0];

      if ($paragraph_info) {
        $dependent_index = conditional_fields_get_simpler_id($dependent['#id']);
      }

      $form['#conditional_fields'][$dependent_index]['is_from_paragraph'] = (bool) $paragraph_info;

      $form['#conditional_fields'][$dependent_index]['index'] = $dependent_index;

      $form['#conditional_fields'][$dependent_index]['field_parents'] = $dependent_parents;
      $form['#conditional_fields'][$dependent_index]['array_parents'] = $paragraph_info['array_parents'] ?? [];
      $form['#conditional_fields'][$dependent_index]['dependees'][$id] = [
        'dependee' => $dependee['#field_name'],
        'options' => $options,
      ];
    }
  }

}
