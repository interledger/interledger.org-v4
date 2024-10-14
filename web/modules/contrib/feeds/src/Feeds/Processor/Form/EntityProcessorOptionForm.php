<?php

namespace Drupal\feeds\Feeds\Processor\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form for the CSV parser.
 */
class EntityProcessorOptionForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityProcessorOptionForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entityTypeManager->getDefinition($this->plugin->entityType());

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $form['values'][$bundle_key] = [
        '#type' => 'select',
        '#options' => $this->plugin->bundleOptions(),
        '#title' => $this->plugin->bundleLabel(),
        '#required' => TRUE,
        '#default_value' => $this->plugin->bundle() ?: key($this->plugin->bundleOptions()),
        '#disabled' => $this->plugin->isLocked(),
      ];
    }

    return $form;
  }

}
