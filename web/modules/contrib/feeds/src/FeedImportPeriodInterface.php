<?php

namespace Drupal\feeds;

/**
 * Provides an interface defining a feeds_feed entity import period.
 */
interface FeedImportPeriodInterface {

  /**
   * Indicates that a feed should use the feed type import period.
   */
  const USE_FEED_TYPE_IMPORT_PERIOD = -2;

  /**
   * Get the periodic import interval for this feed.
   *
   * @return int
   *   The periodic import interval in seconds or the constant for never or
   *   use feed type setting.
   */
  public function getImportPeriod(): int;

}
