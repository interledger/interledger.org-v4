<?php

declare(strict_types=1);

namespace Drupal\captcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with buttons that use #limit_validation_errors around a CAPTCHA.
 *
 * Each set of buttons limits validation to the CAPTCHA response, to the
 * CAPTCHA element, or to an unrelated field. The "before" set is built ahead
 * of the CAPTCHA, so the triggering element is already known when the CAPTCHA
 * element is processed; the "after" set is built behind it.
 */
final class LimitValidationErrorsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_test_limit_validation_errors';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $form['before'] = $this->buildButtons('Before');
    $form['captcha'] = [
      '#type' => 'captcha',
      '#captcha_type' => 'captcha/Test',
    ];
    $form['after'] = $this->buildButtons('After');
    return $form;
  }

  /**
   * Builds a set of buttons with different validation limits.
   *
   * @param string $prefix
   *   Prefix for the button labels.
   *
   * @return array
   *   An actions element.
   */
  private function buildButtons(string $prefix): array {
    $limits = [
      'response' => [['captcha_response']],
      'element' => [['captcha']],
      'skip' => [['name']],
    ];
    $buttons = ['#type' => 'actions'];
    foreach ($limits as $key => $limit) {
      $buttons[$key] = [
        '#type' => 'submit',
        '#value' => "$prefix: $key",
        '#name' => strtolower($prefix) . "_$key",
        '#limit_validation_errors' => $limit,
        '#submit' => ['::submitForm'],
      ];
    }
    return $buttons;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Form submitted!'));
  }

}
