<?php

namespace Drupal\feeds;

/**
 * Provides an interface defining a feeds feed type import period per feed.
 */
interface FeedTypeImportPeriodPerFeedInterface {

  /**
   * Returns the import period per feed.
   *
   * @return bool
   *   Whether the import period is allowed per feed.
   */
  public function isImportPeriodPerFeedAllowed(): bool;

  /**
   * Sets the import period per feed.
   *
   * @param bool $import_period_per_feed
   *   The import period per feed.
   */
  public function setImportPeriodPerFeedAllowed(bool $import_period_per_feed): void;

}
