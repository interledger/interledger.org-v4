<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has clear access to the items of the tempstore.
 */
class FeedClearMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('clear', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionId(): string {
    return 'feeds_feed_clear_action';
  }

}
