<?php

namespace Drupal\feeds_test_plugin\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets a variable for how many times an entity gets cleaned.
 *
 * @Action(
 *   id = "entity:feeds_test_plugin_clean_action_long_name",
 *   action_label = @Translation("Dummy clean"),
 *   deriver = "Drupal\Core\Action\Plugin\Action\Derivative\EntityPublishedActionDeriver",
 * )
 */
final class FeedsCleanAction extends EntityActionBase {

  /**
   * The state handler service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a FeedsCleanAction object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (is_null($entity)) {
      return;
    }

    // Track how many times this entity has been cleaned.
    $cleaned = $this->state->get('feeds_cleaned', []);
    if (!isset($cleaned[$entity->id()]['feeds_test_plugin_clean_action'])) {
      $cleaned[$entity->id()]['feeds_test_plugin_clean_action'] = 1;
    }
    else {
      $cleaned[$entity->id()]['feeds_test_plugin_clean_action']++;
    }
    $this->state->set('feeds_cleaned', $cleaned);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Entity\EntityInterface $object */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
