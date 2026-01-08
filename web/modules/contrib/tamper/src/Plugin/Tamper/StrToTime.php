<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for strtotime.
 *
 * Please note that the presence of the timezone in the input string is
 * important for this plugin to function consistently if the system is not
 * configured to use UTC by default.
 *
 * @Tamper(
 *   id = "strtotime",
 *   label = @Translation("String to Unix Timestamp"),
 *   description = @Translation("This will take a string containing an English date format and convert it into a Unix Timestamp."),
 *   category = @Translation("Date/time"),
 *   itemUsage = "ignored"
 * )
 */
class StrToTime extends TamperBase {

  const SETTING_DATE_FORMAT = 'date_format';
  const SETTING_FALLBACK = 'fallback';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[static::SETTING_DATE_FORMAT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#default_value' => $this->getSetting(static::SETTING_DATE_FORMAT),
      '#description' => $this->t('The custom php date format string to parse the date with. This is optional and mainly useful when working with <a href=":so_link">certain date formats</a>. See the <a href=":php_link">PHP manual</a> for available options.', [
        ':so_link' => 'https://stackoverflow.com/a/2892002',
        ':php_link' => 'https://www.php.net/manual/en/datetimeimmutable.createfromformat.php',
      ]),
    ];
    $form[static::SETTING_FALLBACK] = [
      '#type' => 'checkbox',
      '#title' => t('Fallback to strtotime() if the date could not be parsed with the provided date format.'),
      '#default_value' => $this->getSetting(static::SETTING_FALLBACK),
      '#states' => [
        'visible' => [
          'input[name="plugin_configuration[date_format]"]' => ['filled' => TRUE],
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
    $date_format = $form_state->getValue(static::SETTING_DATE_FORMAT);
    if ($date_format) {
      $this->setConfiguration([
        static::SETTING_DATE_FORMAT => $date_format,
        static::SETTING_FALLBACK => $form_state->getValue(static::SETTING_FALLBACK),
      ]);
    }
    else {
      // Remove the setting if it's not configured.
      unset($this->configuration[static::SETTING_DATE_FORMAT]);
      unset($this->configuration[static::SETTING_FALLBACK]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty or null values.
    if (is_null($data) || $data === '') {
      return $data;
    }

    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    if ($date_format = $this->getSetting(static::SETTING_DATE_FORMAT)) {
      try {
        return $this->convertFromFormat($data, $date_format);
      }
      catch (TamperException $e) {
        // Converting the datetime string failed. Check if we should fallback to
        // strtotime().
        if (!$this->getSetting(static::SETTING_FALLBACK)) {
          // Only the configured date format should be used.
          throw $e;
        }
      }
    }
    return strtotime($data);
  }

  /**
   * Converts datetime string to a timestamp using the provided format.
   *
   * @param string $datetime
   *   The date/time string to convert.
   * @param string $date_format
   *   The date format to use.
   *
   * @return int
   *   The timestamp.
   *
   * @throws \Drupal\tamper\Exception\TamperException
   *   In case the date could not be converted.
   */
  protected function convertFromFormat(string $datetime, string $date_format): int {
    // Attempt to support a specific date format such as d/m/y, resetting
    // values such as hour, minutes etc to '0' if not defined.
    $datetime = \DateTime::createFromFormat('!' . $date_format, $datetime);
    if (!$datetime) {
      throw new TamperException("The date format '$date_format' could not parse the date '$datetime'.");
    }
    return $datetime->getTimestamp();
  }

}
