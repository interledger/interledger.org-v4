<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for performing basic math.
 *
 * @Tamper(
 *   id = "math",
 *   label = @Translation("Math"),
 *   description = @Translation("Performs basic mathematical calculations on the imported value."),
 *   category = "Number"
 * )
 */
class Math extends TamperBase {

  const SETTING_OPERATION = 'operation';
  const SETTING_FLIP = 'flip';
  const SETTING_VALUE = 'value';
  const SETTING_SKIP_ON_NAN = 'skip_on_nan';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_OPERATION] = '';
    $config[self::SETTING_FLIP] = FALSE;
    $config[self::SETTING_VALUE] = '';
    $config[self::SETTING_SKIP_ON_NAN] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_OPERATION] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#required' => TRUE,
      '#description' => $this->t('The operation to apply to the imported value.'),
      '#default_value' => $this->getSetting(self::SETTING_OPERATION),
      '#options' => $this->getOptions(),
    ];

    $form[self::SETTING_FLIP] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flip'),
      '#description' => $this->t('Normally, the feed item will be processed like input-value / setting-value. This option switches the order so that it is setting-value / input-value.'),
      '#default_value' => $this->getSetting(self::SETTING_FLIP),
      '#states' => [
        'visible' => [
          ':input[name="plugin_configuration[operation]"]' => [
            ['value' => 'subtraction'],
            ['value' => 'division'],
          ],
        ],
      ],
    ];

    $form[self::SETTING_VALUE] = [
      '#type' => 'number',
      '#title' => $this->t('Value'),
      '#required' => TRUE,
      '#description' => $this->t('A numerical value.'),
      '#default_value' => $this->getSetting(self::SETTING_VALUE),
      '#step' => 'any',
    ];

    $form[self::SETTING_SKIP_ON_NAN] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip calculation when the data is not numeric'),
      '#description' => $this->t('If checked, the data is returned as is when it is not numeric. If not checked, empty data will be treated as zero.'),
      '#default_value' => $this->getSetting(self::SETTING_SKIP_ON_NAN),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(self::SETTING_OPERATION) === 'division' && empty($form_state->getValue(self::SETTING_FLIP)) && $form_state->getValue(self::SETTING_VALUE == 0)) {
      $form_state->setErrorByName(self::SETTING_VALUE, $this->t('Cannot divide by zero.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_OPERATION => $form_state->getValue(self::SETTING_OPERATION),
      self::SETTING_FLIP => $form_state->getValue(self::SETTING_FLIP),
      self::SETTING_VALUE => $form_state->getValue(self::SETTING_VALUE),
      self::SETTING_SKIP_ON_NAN => $form_state->getValue(self::SETTING_SKIP_ON_NAN),
    ]);
  }

  /**
   * Get the math operation options.
   *
   * @return array
   *   List of options.
   */
  protected function getOptions() {
    return [
      'addition' => $this->t('addition'),
      'subtraction' => $this->t('subtraction'),
      'multiplication' => $this->t('multiplication'),
      'division' => $this->t('division'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $operation = $this->getSetting(self::SETTING_OPERATION);
    $flip = $this->getSetting(self::SETTING_FLIP);
    $value = $this->getSetting(self::SETTING_VALUE);

    if (!is_numeric($data)) {
      // The data is not a number. Check if the calculation should be aborted.
      if ($this->getSetting(self::SETTING_SKIP_ON_NAN)) {
        return $data;
      }
      if ($data === TRUE || $data === FALSE || $data === NULL || $data === '') {
        $data = (int) $data;
      }
    }

    if (!is_numeric($data)) {
      throw new TamperException('Math plugin failed because data was not numeric.');
    }

    if ($flip) {

      switch ($operation) {
        case 'subtraction':
          $data = $value - $data;
          break;

        case 'division':
          // Avoid divide by zero.
          if (empty($data)) {
            throw new TamperException('Math plugin failed because divide by zero was attempted.');
          }

          $data = $value / $data;
      }
      return $data;
    }

    switch ($operation) {
      case 'addition':
        $data = $data + $value;
        break;

      case 'subtraction':
        $data = $data - $value;
        break;

      case 'multiplication':
        $data = $data * $value;
        break;

      case 'division':
        $data = $data / $value;
    }

    return $data;
  }

}
