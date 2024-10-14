<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for aggregating values.
 *
 * @Tamper(
 *   id = "aggregate",
 *   label = @Translation("Aggregate"),
 *   description = @Translation("Aggregates data, such as picking the maximum value."),
 *   category = "Number",
 *   handle_multiples = TRUE
 * )
 */
class Aggregate extends TamperBase {

  const SETTING_FUNCTION = 'function';
  const SETTING_COUNT = 'count';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_FUNCTION] = NULL;
    $config[self::SETTING_COUNT] = NULL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_FUNCTION] = [
      '#type' => 'radios',
      '#title' => $this->t('Function'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_FUNCTION),
      '#description' => $this->t('Method of how to process multiple values into a single value.'),
      '#options' => $this->getOptions(),
    ];

    foreach ($this->getOptionsDescriptions() as $key => $description) {
      $form[self::SETTING_FUNCTION][$key]['#description'] = $description;
    }

    $form[self::SETTING_COUNT] = [
      '#type' => 'select',
      '#title' => $this->t('Count mode'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'recursive' => $this->t('Recursive'),
      ],
      '#default_value' => $this->getSetting(self::SETTING_COUNT),
      '#description' => $this->t('The recursive option will count all elements in a multidimensional array.'),
      '#states' => [
        'visible' => [
          ':input[name="plugin_configuration[function]"]' => [
            ['value' => 'count'],
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $config = [
      self::SETTING_FUNCTION => $form_state->getValue(self::SETTING_FUNCTION),
    ];
    if ($config[self::SETTING_FUNCTION] == 'count') {
      $config[self::SETTING_COUNT] = $form_state->getValue(self::SETTING_COUNT);
    }
    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_array($data)) {
      throw new TamperException('Input should be an array.');
    }

    switch ($this->getSetting(self::SETTING_FUNCTION)) {
      case 'average':
        return array_sum($data) / count($data);

      case 'count':
        if ($this->getSetting(self::SETTING_COUNT) == 'recursive') {
          return count($data, COUNT_RECURSIVE);
        }
        return count($data);

      case 'max':
        return max($data);

      case 'median':
        sort($data);
        $low_middle = $data[floor((count($data) - 1) / 2)];
        $high_middle = $data[ceil((count($data) - 1) / 2)];
        return ($low_middle + $high_middle) / 2;

      case 'min':
        return min($data);

      case 'mode':
        $values = array_count_values($data);
        return array_search(max($values), $values);

      case 'range':
        return max($data) - min($data);

      case 'sum':
        return array_sum($data);
    }
  }

  /**
   * Get the aggregate functions.
   *
   * @return array
   *   List of options.
   */
  protected function getOptions() {
    $options = [
      'average' => $this->t('Average'),
      'count' => $this->t('Count'),
      'max' => $this->t('Maximum'),
      'median' => $this->t('Median'),
      'min' => $this->t('Minimum'),
      'mode' => $this->t('Mode'),
      'range' => $this->t('Range'),
      'sum' => $this->t('Sum'),
    ];
    // Make sure that the options appear in alphabetical order in the
    // language that they appear in.
    asort($options, SORT_STRING);
    return $options;
  }

  /**
   * Get the aggregate functions.
   *
   * @return array
   *   List of options.
   */
  protected function getOptionsDescriptions() {
    return [
      'average' => $this->t('The sum of all values divided by the number of values.'),
      'count' => $this->t('The number of values.'),
      'max' => $this->t('The largest value.'),
      'median' => $this->t('The middle value: half of the values are below it and the other half are above it.'),
      'min' => $this->t('The smallest value.'),
      'mode' => $this->t('The value that appears the most in the list. If there is more than one number that appears the most, the first of these will be picked.'),
      'range' => $this->t('The difference between the largest and smallest value.'),
      'sum' => $this->t('The total of all values.'),
    ];
  }

}
