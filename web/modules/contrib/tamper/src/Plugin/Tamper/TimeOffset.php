<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for TimeOffset.
 */
#[Tamper(
  id: 'timeoffset',
  label: new TranslatableMarkup('Timezone offset'),
  description: new TranslatableMarkup('Apply a timezone offset to a timestamp.'),
  category: new TranslatableMarkup('Date/time'),
  itemUsage: ItemUsage::OPTIONAL,
)]
class TimeOffset extends TamperBase {

  use TimeOffsetTrait;

  const SETTING_TIMEZONE = 'timezone';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TIMEZONE] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getOptions();
    $form[self::SETTING_TIMEZONE] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_TIMEZONE),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_TIMEZONE => $form_state->getValue(self::SETTING_TIMEZONE)]);
  }

  /**
   * Get the timezone options.
   *
   * @return array
   *   List of timezone options.
   */
  protected function getOptions() {
    $zones = timezone_identifiers_list();
    $options = [];
    foreach ($zones as $zone) {
      $options[$zone] = $zone;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    $timezone = $this->getSetting(self::SETTING_TIMEZONE);

    /** @var \DateTime $datetime */
    $callback = function ($datetime) use ($timezone) {
      // Set the timezone for the DateTime object.
      $datetime->setTimezone(new \DateTimeZone($timezone));
      // Get the offset between the regional timezone and UTC in seconds.
      $offset = $datetime->getOffset();
      // Generate the offset string.
      return ($offset >= 0 ? '+' : '-') . abs($offset) . ' seconds';
    };

    return $this->applyTimeOffset($data, $callback);
  }

}
