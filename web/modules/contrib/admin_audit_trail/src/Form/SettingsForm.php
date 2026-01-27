<?php

namespace Drupal\admin_audit_trail\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a form for the admin audit trail settings.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_audit_trail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['admin_audit_trail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('admin_audit_trail.settings');

    $form['filter_expanded'] = [
      '#default_value' => $config->get('filter_expanded'),
      '#description' => $this->t('Should the filters be expanded when viewing the admin audit trail.'),
      '#title' => $this->t('Filters Expanded'),
      '#type' => 'checkbox',
    ];

    $row_limits = [100, 500, 1_000, 3_000, 10_000, 100_000];
    $form['admin_audit_trail_row_limit'] = [
      '#default_value' => $config->get('admin_audit_trail_row_limit'),
      '#description' => $this->t('The maximum number of messages to keep in the audit trail log. Requires a <a href=":cron">cron maintenance task</a>.', [':cron' => Url::fromRoute('system.status')->toString()]),
      '#options' => [0 => $this->t('All')] + array_combine($row_limits, $row_limits),
      '#title' => $this->t('Audit Trail log messages to keep'),
      '#type' => 'select',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('admin_audit_trail.settings')
      ->set('filter_expanded', $form_state->getValue('filter_expanded'))
      ->set('admin_audit_trail_row_limit', $form_state->getValue('admin_audit_trail_row_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
