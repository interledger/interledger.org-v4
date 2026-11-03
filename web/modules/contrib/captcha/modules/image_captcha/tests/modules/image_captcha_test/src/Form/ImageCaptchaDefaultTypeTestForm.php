<?php

declare(strict_types=1);

namespace Drupal\image_captcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\captcha\Constants\CaptchaConstants;

/**
 * Provides a test form using the "default" CAPTCHA type.
 */
final class ImageCaptchaDefaultTypeTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_captcha_test_default_type';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['image_captcha'] = [
      '#type' => 'captcha',
      '#captcha_type' => CaptchaConstants::CAPTCHA_TYPE_DEFAULT,
      '#captcha_admin_mode' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
