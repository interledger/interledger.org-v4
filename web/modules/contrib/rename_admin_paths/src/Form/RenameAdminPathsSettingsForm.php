<?php

namespace Drupal\rename_admin_paths\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rename_admin_paths\Config;
use Drupal\rename_admin_paths\EventSubscriber\RenameAdminPathsEventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the Rename Admin Paths module.
 */
class RenameAdminPathsSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * The config service.
   *
   * @var \Drupal\rename_admin_paths\Config
   */
  private $config;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  private $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rename_admin_paths_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      Config::CONFIG_KEY,
    ];
  }

  /**
   * Constructs the settings form for Rename Admin Paths.
   *
   * @param \Drupal\rename_admin_paths\Config $config
   *   The config service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   The route builder service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    Config $config,
    RouteBuilderInterface $routeBuilder,
    TranslationInterface $stringTranslation,
  ) {
    $this->config = $config;
    $this->routeBuilder = $routeBuilder;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('Drupal\rename_admin_paths\Config'),
      $container->get('router.builder'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['admin_path'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Rename admin path'),
    ];

    $form['admin_path']['admin_path'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Rename admin path'),
      '#default_value' => $this->config->isPathEnabled('admin'),
      '#description'   => $this->t(
        'If checked, "admin" will be replaced by the following term in admin path.'
      ),
    ];

    $form['admin_path']['admin_path_value'] = [
      '#type'             => 'textfield',
      '#title'            => $this->t('Replace "admin" in admin path by'),
      '#default_value'    => $this->config->getPathValue('admin'),
      '#description'      => $this->t(
        'This value will replace "admin" in admin path.'
      ),
      '#element_validate' => [[$this, 'validate']],
    ];

    $form['user_path'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Rename user path'),
    ];

    $form['user_path']['user_path'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Rename user path'),
      '#default_value' => $this->config->isPathEnabled('user'),
      '#description'   => $this->t(
        'If checked, "user" will be replaced by the following term in user path.'
      ),
    ];

    $form['user_path']['user_path_value'] = [
      '#type'             => 'textfield',
      '#title'            => $this->t('Replace "user" in user path by'),
      '#default_value'    => $this->config->getPathValue('user'),
      '#description'      => $this->t(
        'This value will replace "user" in user path.'
      ),
      '#element_validate' => [[$this, 'validate']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validates a form element.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function validate(&$element, FormStateInterface $formState) {
    if (empty($element['#value'])) {
      $formState->setError(
        $element,
        $this->t('Path replacement value must contain a value.')
      );
    }
    elseif (!RenameAdminPathsValidator::isValidPath($element['#value'])) {
      $formState->setError(
        $element,
        $this->t(
          'Path replacement value must contain only letters, numbers, hyphens and underscores.'
        )
      );
    }
    elseif (RenameAdminPathsValidator::isDefaultPath($element['#value'])) {
      $formState->setError(
        $element,
        sprintf(
          $this->t('Renaming to a default name (%s) is not allowed.'),
          implode(', ', RenameAdminPathsEventSubscriber::ADMIN_PATHS)
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->saveConfiguration($formState);

    // At this stage we rebuild all routes to use the new renamed paths.
    $this->routeBuilder->rebuild();

    // Add confirmation message.
    parent::submitForm($form, $formState);

    // Make sure we end up at the same form again using the new path.
    $formState->setRedirect('rename_admin_paths.admin');
  }

  /**
   * Saves the module configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state interface.
   */
  private function saveConfiguration(FormStateInterface $formState) {
    $this->config->setPathEnabled('admin', $formState->getValue('admin_path'));
    $this->config->setPathValue(
      'admin',
      $formState->getValue('admin_path_value')
    );
    $this->config->setPathEnabled('user', $formState->getValue('user_path'));
    $this->config->setPathValue(
      'user',
      $formState->getValue('user_path_value')
    );
    $this->config->save();
  }

}
