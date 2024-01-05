<?php

namespace Drupal\layout_builder_admin_theme\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LBATConfigForm.
 */
class LBATConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_builder_admin_theme.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lbat_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('layout_builder_admin_theme.config');

    // Checkbox to enable or disable the admin theme for layout builder.
    $form['lbat_enable_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable admin theme for layout builder'),
      '#default_value' => $config->get('lbat_enable_admin_theme'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('layout_builder_admin_theme.config')
      ->set('lbat_enable_admin_theme', $form_state->getValue('lbat_enable_admin_theme'))
      ->save();
  }

}
