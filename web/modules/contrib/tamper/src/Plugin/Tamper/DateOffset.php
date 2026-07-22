<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for DateOffset.
 */
#[Tamper(
  id: 'dateoffset',
  label: new TranslatableMarkup('Date offset'),
  description: new TranslatableMarkup('Apply an arbitrary offset to a date timestamp.'),
  category: new TranslatableMarkup('Date/time'),
  itemUsage: ItemUsage::OPTIONAL,
)]
class DateOffset extends TamperBase {

  use TimeOffsetTrait;

  const SETTING_DATE_OFFSET = 'date_offset';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_DATE_OFFSET] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_DATE_OFFSET] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#default_value' => $this->getSetting(self::SETTING_DATE_OFFSET),
      '#description' => $this->t('A user-defined php date offset format string like "+1 month". See the <a href="@link">PHP manual</a> for available options.', ['@link' => 'https://www.php.net/manual/en/datetime.modify.php']),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_DATE_OFFSET => $form_state->getValue(self::SETTING_DATE_OFFSET)]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    // Get the offset from the configuration.
    $offset = $this->getSetting(self::SETTING_DATE_OFFSET);

    return $this->applyTimeOffset($data, fn ($datetime) => $offset);
  }

}
