<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation of the hash plugin.
 *
 * @Tamper(
 *   id = "hash",
 *   label = @Translation("Hash"),
 *   description = @Translation("Makes the value a hash of the values of item being tampered."),
 *   category = @Translation("Other"),
 *   itemUsage = "optional"
 * )
 */
class Hash extends TamperBase {

  const SETTING_DATA_TO_HASH = 'data_to_hash';
  const SETTING_ONLY_IF_EMPTY = 'only_if_empty';

  /**
   * If a hash should be set even if there already is a value.
   *
   * @deprecated in tamper:8.x-1.0-beta2 and is removed from tamper:2.0.0. Use
   *   the 'only_if_empty' setting instead.
   * @see https://www.drupal.org/node/3551592
   */
  const SETTING_OVERRIDE = 'override';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_DATA_TO_HASH] = 'item';
    $config[self::SETTING_ONLY_IF_EMPTY] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    // @todo Remove this in tamper:2.0.0.
    if ($key === self::SETTING_ONLY_IF_EMPTY) {
      // Check first if there is a value for the old 'override' setting.
      $override = $this->getSetting(self::SETTING_OVERRIDE);
      if (is_bool($override)) {
        return !$override;
      }
    }
    return parent::getSetting($key);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_DATA_TO_HASH] = [
      '#type' => 'select',
      '#title' => $this->t('Data to hash'),
      '#options' => [
        'item' => $this->t('The whole source item'),
        'data' => $this->t('The input value'),
      ],
      '#description' => t('Note: if the source item is not available, a hash from the input value will be generated instead.'),
      '#default_value' => $this->getSetting(self::SETTING_DATA_TO_HASH),
    ];
    $form[self::SETTING_ONLY_IF_EMPTY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only if empty'),
      '#description' => $this->t('A hash will only be generated if the input value is empty. You probably want to disable this option if you want to generate a hash of the input value.'),
      '#default_value' => $this->getSetting(self::SETTING_ONLY_IF_EMPTY),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_DATA_TO_HASH => (string) $form_state->getValue(self::SETTING_DATA_TO_HASH),
      self::SETTING_ONLY_IF_EMPTY => (bool) $form_state->getValue(self::SETTING_ONLY_IF_EMPTY),
    ]);
    unset($this->configuration[static::SETTING_OVERRIDE]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    if (!empty($data) && $this->getSetting(self::SETTING_ONLY_IF_EMPTY)) {
      // Return data as is.
      return $data;
    }

    $data_to_hash = $this->getDataToHash($data, $item);
    return md5($data_to_hash);
  }

  /**
   * Returns the data to hash.
   *
   * @param mixed $data
   *   The input data.
   * @param \Drupal\tamper\TamperableItemInterface|null $item
   *   The tamperable item, if available.
   * @param string|null $data_to_hash_setting
   *   Specification of what value to hash.
   *
   * @return string
   *   The value to be hashed.
   */
  protected function getDataToHash($data, ?TamperableItemInterface $item = NULL, ?string $data_to_hash_setting = NULL): string {
    if (!is_string($data_to_hash_setting)) {
      $data_to_hash_setting = $this->getSetting(self::SETTING_DATA_TO_HASH);
    }

    switch ($data_to_hash_setting) {
      case 'item':
        if ($item instanceof TamperableItemInterface) {
          return serialize($item->getSource());
        }
        // If no item is passed, use the input data instead.
        return $this->getDataToHash($data, $item, 'data');

      case 'data':
        if (!is_string($data)) {
          return serialize($data);
        }
        return $data;
    }

    return '';
  }

}
