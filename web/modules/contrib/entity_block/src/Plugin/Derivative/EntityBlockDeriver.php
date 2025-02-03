<?php

namespace Drupal\entity_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Entity Block displays.
 *
 * @see \Drupal\entity_block\Plugin\block\EntityBlock
 */
class EntityBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * Constructs a EntityBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected string $base_plugin_id, protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    foreach ($this->entityTypeManager->getDefinitions() as $entityDefinition) {
      if ($entityDefinition->hasViewBuilderClass()) {
        $delta = $entityDefinition->id();
        $this->derivatives[$delta] = [
          'category' => 'Entity Block',
          'admin_label' => $entityDefinition->getLabel(),
          'config_dependencies' => [
            'config' => [
              // @todo Add proper config dependencies.
            ],
          ],
        ];
        $this->derivatives[$delta] += $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
