<?php

namespace Drupal\feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_feed_type entity.
 *
 * @see \Drupal\feeds\Entity\FeedType
 *
 * @todo Provide more granular permissions.
 */
class FeedTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("view {$entity->id()} feeds");
        return AccessResult::allowedIf($has_perm);

      case 'delete':
        return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);

      case 'template':
        $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("create {$entity->id()} feeds") || $account->hasPermission("update {$entity->id()} feeds") || $account->hasPermission("update own {$entity->id()} feeds") || $account->hasPermission("import {$entity->id()} feeds") || $account->hasPermission("import own {$entity->id()} feeds") || $account->hasPermission("schedule_import {$entity->id()} feeds") || $account->hasPermission("schedule_import own {$entity->id()} feeds");
        return AccessResult::allowedIf($has_perm);

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
