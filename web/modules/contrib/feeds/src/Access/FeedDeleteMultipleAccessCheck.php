<?php

namespace Drupal\feeds\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Checks if the current user has delete access to the items of the tempstore.
 */
class FeedDeleteMultipleAccessCheck extends FeedActionMultipleAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected function checkFeedAccess(AccountInterface $account, FeedInterface $feed) {
    return $feed->access('delete', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActionId(): string {
    return 'feeds_feed_delete_action';
  }

}
