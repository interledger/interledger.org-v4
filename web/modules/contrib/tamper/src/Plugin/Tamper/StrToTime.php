<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for strtotime.
 *
 * @Tamper(
 *   id = "strtotime",
 *   label = @Translation("String to Unix Timestamp"),
 *   description = @Translation("This will take a string containing an English date format and convert it into a Unix Timestamp."),
 *   category = "Date/time"
 * )
 */
class StrToTime extends TamperBase {

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
    return strtotime($data);
  }

}
