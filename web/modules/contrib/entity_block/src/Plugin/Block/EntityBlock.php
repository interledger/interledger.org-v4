<?php

namespace Drupal\entity_block\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_block\Plugin\Derivative\EntityBlockDeriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the block for similar articles.
 */
#[Block(
  id: "entity_block",
  admin_label: new TranslatableMarkup("Entity block"),
  deriver: EntityBlockDeriver::class
)]
class EntityBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The number of times this block allows rendering the same entity.
   *
   * @var int
   */
  const RECURSIVE_RENDER_LIMIT = 3;

  /**
   * The name of our entity type.
   *
   * @var string
   */
  protected string $entityTypeName;

  /**
   * The entity storage for our entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * The view builder for our entity type.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilder;

  /**
   * An array of view mode labels, keyed by the display mode ID.
   *
   * @var array
   */
  protected array $viewModeOptions;

  /**
   * An array of counters for the recursive rendering protection.
   *
   * @var array
   */
  protected static array $recursiveRenderDepth = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    try {
      // Determine what entity type we are referring to.
      $this->entityTypeName = $this->getDerivativeId();

      // Load various utilities related to our entity type.
      $entityTypeManager = $this->entityTypeManager;
      $this->entityStorage = $entityTypeManager->getStorage($this->entityTypeName);
      $this->entityViewBuilder = $entityTypeManager->getHandler($this->entityTypeName, 'view_builder');
      $this->viewModeOptions = $entityDisplayRepository->getViewModeOptions($this->entityTypeName);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->loggerFactory->get('entity')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['entity'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Entity'),
      '#target_type' => $this->entityTypeName,
      '#required' => TRUE,
      '#maxlength' => 1024,
    ];

    if (isset($config['entity'])) {
      if ($entity = $this->entityStorage->load($config['entity'])) {
        $form['entity']['#default_value'] = $entity;
      }
    }

    $view_mode = $config['view_mode'] ?? NULL;

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $this->viewModeOptions,
      '#default_value' => $view_mode,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hide default block form fields that are undesired in this case.
    $form['admin_label']['#access'] = FALSE;

    $form['label']['#states'] = [
      'visible' => [
        ':input[name="settings[label_display]"]' => ['checked' => TRUE],
      ],
    ];

    $form['label_display']['#description'] = $this->t('If left unchecked the block will automatically generate a title.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);

    $this->configuration['entity'] = $form_state->getValue('entity');
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');

    if ($entity = $this->entityStorage->load($this->configuration['entity'])) {
      $plugin_definition = $this->getPluginDefinition();
      $admin_label = $plugin_definition['admin_label'];
      if ($this->configuration['label_display'] !== 'visible') {
        $this->configuration['label'] = new FormattableMarkup('@entity_label (@admin_label)', [
          '@entity_label' => $entity->label(),
          '@admin_label' => $admin_label,
        ]);
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if ($entity = $this->getEntity()) {
      $recursive_render_id = $entity->getEntityTypeId() . ':' . $entity->id();
      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }

      // Protect recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering embedded entity %entity_type: %entity_id. Aborting rendering.', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%entity_id' => $entity->id(),
        ]);
      }

      $render_controller = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $view_mode = $this->configuration['view_mode'] ?? 'default';

      return $render_controller->view($entity, $view_mode);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    $entity = $this->getEntity();
    if ($entity && $entity->access('view', $account)) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()
      ->setReason($this->t('User does not have permission to view this entity.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $entity = $this->getEntity();
    $contexts = $entity ? $entity->getCacheContexts() : [];
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $entity = $this->getEntity();
    $cache_tags = $entity ? $entity->getCacheTags() : [];
    return Cache::mergeTags(parent::getCacheTags(), $cache_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    $entity = $this->getEntity();
    $max_age = $entity ? $entity->getCacheMaxAge() : Cache::PERMANENT;
    return Cache::mergeMaxAges(parent::getCacheMaxAge(), $max_age);
  }

  /**
   * Gets our entity.
   */
  public function getEntity(): ?EntityInterface {
    if ($entity_id = $this->configuration['entity']) {
      return $this->entityStorage->load($entity_id);
    }

    return NULL;
  }

}
