<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;

/**
 * DateTime offset helper functions.
 */
trait TimeOffsetTrait {

  /**
   * Applies a strtotime format time offset to a timestamp.
   *
   * Time offset is provided through a callback.
   *
   * @param mixed $timestamp
   *   The original Unix timestamp.
   * @param callable(\DateTime): string $offset_callback
   *   A callback that returns a time offset.
   *   Returned time offset string should follow relative format.
   *
   * @see https://www.php.net/manual/en/datetime.formats.php#datetime.formats.relative
   *
   * @return int
   *   Timestamp after offset applied.
   */
  protected function applyTimeOffset(mixed $timestamp, callable $offset_callback): int {
    // Check if the input is a valid timestamp.
    if (!is_numeric($timestamp)) {
      throw new TamperException('Input should be numeric (timestamp).');
    }

    // Create a DateTime object for the input timestamp.
    $datetime = new \DateTime('@' . $timestamp);
    // Generate offset string from callback.
    $offset = $offset_callback($datetime);
    // Modify the Date object with the offset.
    try {
      $datetime->modify($offset);
    }
    catch (\Exception $e) {
      throw new TamperException('Offset is invalid.');
    }

    return $datetime->getTimestamp();
  }

}
