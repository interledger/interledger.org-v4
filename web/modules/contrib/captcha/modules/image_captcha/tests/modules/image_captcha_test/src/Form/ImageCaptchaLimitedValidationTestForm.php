<?php

declare(strict_types=1);

namespace Drupal\image_captcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image_captcha\Constants\ImageCaptchaConstants;

/**
 * An image CAPTCHA form with a limited-validation button, like a file upload.
 */
final class ImageCaptchaLimitedValidationTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_captcha_test_limited_validation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];

    $form['image_captcha'] = [
      '#type' => 'captcha',
      '#captcha_type' => ImageCaptchaConstants::IMAGE_CAPTCHA_CAPTCHA_TYPE,
    ];

    // Mimics the "Upload" button of a managed_file element.
    $form['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#limit_validation_errors' => [['name']],
      '#submit' => ['::rebuildForm'],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Submit handler for the upload button.
   */
  public function rebuildForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Form submitted!'));
  }

}
