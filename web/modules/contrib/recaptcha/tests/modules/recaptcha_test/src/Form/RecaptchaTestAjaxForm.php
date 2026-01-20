<?php

namespace Drupal\recaptcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Recaptcha test AJAX form class.
 */
class RecaptchaTestAjaxForm extends FormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recaptcha_test_ajax_form';
  }

  /**
   * Build form method for RecaptchaTestAjaxForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['messages'] = [
      '#type' => 'status_messages',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#validate' => ['::validateForm'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'recaptcha-test-ajax-form-wrapper',
      ],
    ];

    $form['#prefix'] = '<div id="recaptcha-test-ajax-form-wrapper">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Ajax callback method for form.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    if ($email == 'invalid@example.com') {
      $form_state->setError($form['email'], 'Invalid email');
    }

  }

  /**
   * Form submission method.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('Form submit successful.');
  }

}
