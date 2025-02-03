<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a text field mapper.
 *
 * @FeedsTarget(
 *   id = "text",
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class Text extends StringTarget implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The storage for filter_format config entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $filterFormatStorage;

  /**
   * Constructs a Text object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $filter_format_storage
   *   The storage for filter_format config entities.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $user, ?ConfigEntityStorageInterface $filter_format_storage = NULL) {
    $this->user = $user;
    if (!isset($filter_format_storage)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $filter_format_storage argument is deprecated in feeds:3.0.0-rc2 and will be required in feeds:4.0.0. See https://www.drupal.org/node/3473603', E_USER_DEPRECATED);
      $filter_format_storage = \Drupal::service('entity_type.manager')->getStorage('filter_format');
    }
    $this->filterFormatStorage = $filter_format_storage;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('filter_format'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    if ($field_definition->getType() === 'text_with_summary') {
      $definition->addProperty('summary');
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // At todo. Maybe break these up into separate classes.
    parent::prepareValue($delta, $values);
    $values['format'] = $this->configuration['format'];
  }

  /**
   * Retrieves a list of text formats that the given user may use.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to select formats for.
   *
   * @return \Drupal\filter\FilterFormatInterface[]
   *   An array of text format objects, keyed by the format ID and ordered by
   *   weight.
   */
  protected function getFilterFormats(AccountInterface $account) {
    return filter_formats($account);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['format' => 'plain_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = [];
    foreach ($this->getFilterFormats($this->user) as $id => $format) {
      $options[$id] = $format->label();
    }
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter format'),
      '#options' => $options,
      '#default_value' => $this->configuration['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    $formats = $this->filterFormatStorage->loadByProperties([
      'status' => '1',
      'format' => $this->configuration['format'],
    ]);

    if ($formats) {
      $format = reset($formats);
      $summary[] = $this->t('Format: %format', ['%format' => $format->label()]);
    }
    return $summary;
  }

}
