<?php

declare(strict_types=1);

namespace Drupal\captcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form that rebuilds itself from a button with #limit_validation_errors.
 *
 * Mirrors the Commerce Shipping "recalculate shipping" button: it is built
 * before the CAPTCHA, skips validation and rebuilds the whole form.
 */
final class LimitedValidationRebuildForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_test_limited_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $form['recalculate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Recalculate'),
      '#limit_validation_errors' => [['name']],
      '#submit' => ['::rebuildSubmit'],
    ];
    $form['captcha'] = [
      '#type' => 'captcha',
      '#captcha_type' => 'captcha/Test',
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * Submit handler for the limited validation button.
   */
  public function rebuildSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Form submitted!'));
  }

}
