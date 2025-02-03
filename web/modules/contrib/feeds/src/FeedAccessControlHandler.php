<?php

namespace Drupal\feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_feed entity.
 *
 * @see \Drupal\feeds\Entity\Feed
 */
class FeedAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $feed, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'template':
        $has_perm = $this->hasPermissionForOperation($feed, 'update', $account) || $this->hasPermissionForOperation($feed, 'import', $account) || $this->hasPermissionForOperation($feed, 'schedule_import', $account);
        break;

      default:
        $has_perm = $this->hasPermissionForOperation($feed, $operation, $account);
    }

    switch ($operation) {
      case 'view':
      case 'create':
      case 'update':
      case 'template':
        return AccessResult::allowedIf($has_perm);

      case 'import':
      case 'schedule_import':
      case 'clear':
        return AccessResult::allowedIf($has_perm && !$feed->isLocked());

      case 'unlock':
        return AccessResult::allowedIf($has_perm && $feed->isLocked());

      case 'delete':
        return AccessResult::allowedIf($has_perm && !$feed->isLocked() && !$feed->isNew());

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * Returns if the given user has the permission for the given operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $feed
   *   The feed for which to check access.
   * @param string $operation
   *   The operation to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   */
  protected function hasPermissionForOperation(EntityInterface $feed, string $operation, AccountInterface $account): bool {
    return $account->hasPermission('administer feeds') || $account->hasPermission("$operation {$feed->bundle()} feeds") || ($account->hasPermission("$operation own {$feed->bundle()} feeds") && $this->isFeedOwner($feed, $account));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("create $entity_bundle feeds");
    return AccessResult::allowedIf($has_perm);
  }

  /**
   * Performs check if current user is feed owner.
   *
   * @param \Drupal\Core\Entity\EntityInterface $feed
   *   The feed for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check access.
   *
   * @return bool
   *   True if the current user is the feed owner. False otherwise.
   */
  public function isFeedOwner(EntityInterface $feed, AccountInterface $account) {
    return $feed->getOwner()->id() === $account->id();
  }

}
