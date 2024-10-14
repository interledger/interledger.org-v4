<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Word Count plugin.
 *
 * @Tamper(
 *   id = "word_count",
 *   label = @Translation("Get number of words"),
 *   description = @Translation("Get the number of words in a string"),
 *   category = "Text"
 * )
 */
class WordCount extends TamperBase {

  const SETTING_LIMIT = 'limit';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_LIMIT] = NULL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_LIMIT] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $this->getSetting(self::SETTING_LIMIT),
      '#description' => $this->t('If limit is set and positive, the returned items will contain a maximum of limit with the last item containing the rest of string. If limit is negative, all components except the last -limit are returned. If the limit parameter is zero, then this is treated as 1. If limit is not set, then there will be no limit on the number of items returned.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_LIMIT => $form_state->getValue(self::SETTING_LIMIT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    $limit = is_numeric($this->getSetting(self::SETTING_LIMIT)) ? $this->getSetting(self::SETTING_LIMIT) : PHP_INT_MAX;
    $words = explode(' ', $data, $limit);
    return count($words);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
