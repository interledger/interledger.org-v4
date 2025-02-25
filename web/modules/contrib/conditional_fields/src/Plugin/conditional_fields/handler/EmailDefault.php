<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for emails.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_email_default",
 * )
 */
class EmailDefault extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $values_array = $this->getConditionValues($options);

    // Email fields values are keyed by cardinality, so we have to flatten them.
    // @todo support multiple values.
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        foreach ($options['value_form'] as $value) {
          // Fix 0 selector for multiple fields.
          if (!empty($value['value'])) {
            $state[$options['state']][$options['selector']] = ['value' => $value['value']];
          }
        }
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // @todo support AND condition.
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $values[$options['condition']] = ['regex' => $options['regex']];
        $state[$options['state']][$options['selector']] = $values;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $values[$options['condition']] = ['xor' => $values_array];
        $state[$options['state']][$options['selector']] = $values;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $options['state'] = '!' . $options['state'];
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        if (!empty($values_array)) {
          foreach ($values_array as $value) {
            $input_states[$options['selector']][] = ['value' => $value];
          }
        }
        else {
          $input_states[$options['selector']] = [
            $options['condition'] => $values_array,
          ];
        }

        if (isset($input_states)) {
          $state[$options['state']][] = $input_states;
        }
        break;

      default:
        break;
    }
    return $state;
  }

}
