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

class RenameAdminPathsSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * @var Config
   */
  private $config;

  /**
   * @var RouteBuilderInterface
   */
  private $routeBuilder;

  /**
   * {@inheritdoc}.
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
   * @param Config $config
   * @param RouteBuilderInterface $routeBuilder
   * @param TranslationInterface $stringTranslation
   */
  public function __construct(
    Config $config,
    RouteBuilderInterface $routeBuilder,
    TranslationInterface $stringTranslation
  ) {
    $this->config = $config;
    $this->routeBuilder = $routeBuilder;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * @param ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('Drupal\rename_admin_paths\Config'),
      $container->get('router.builder'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}.
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
   * Form element validation handler.
   *
   * @param array $element
   * @param FormStateInterface $formState
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

    // at this stage we rebuild all routes to use the new renamed paths
    $this->routeBuilder->rebuild();

    // add confirmation message
    parent::submitForm($form, $formState);

    // make sure we end up at the same form again using the new path
    $formState->setRedirect('rename_admin_paths.admin');
  }

  /**
   * @param FormStateInterface $formState
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
