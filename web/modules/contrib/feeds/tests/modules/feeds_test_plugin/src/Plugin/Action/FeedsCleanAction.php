<?php

namespace Drupal\feeds_test_plugin\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Sets a variable for how many times an entity gets cleaned.
 *
 * @Action(
 *   id = "entity:feeds_test_plugin_clean_action_long_name",
 *   action_label = @Translation("Dummy clean"),
 *   deriver = "Drupal\Core\Action\Plugin\Action\Derivative\EntityPublishedActionDeriver",
 * )
 */
class FeedsCleanAction extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (is_null($entity)) {
      return;
    }

    // Track how many times this entity has been cleaned.
    $cleaned = \Drupal::state()->get('feeds_cleaned', []);
    if (!isset($cleaned[$entity->id()]['feeds_test_plugin_clean_action'])) {
      $cleaned[$entity->id()]['feeds_test_plugin_clean_action'] = 1;
    }
    else {
      $cleaned[$entity->id()]['feeds_test_plugin_clean_action']++;
    }
    \Drupal::state()->set('feeds_cleaned', $cleaned);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
