<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has import access to the feeds of the tempstore.
 */
class FeedImportMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('import', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionId(): string {
    return 'feeds_feed_import_action';
  }

}
