<?php

declare(strict_types=1);

namespace Drupal\captcha_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\captcha\Constants\CaptchaConstants;

/**
 * Replaces the Math CAPTCHA title the way Webform's "Question title" does.
 */
final class TitleOverrideForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_test_title_override';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['captcha'] = [
      '#type' => 'captcha',
      '#captcha_type' => CaptchaConstants::CAPTCHA_MATH_CAPTCHA_TYPE,
      '#after_build' => [[static::class, 'overrideTitle']],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * After build callback mirroring Webform's afterBuildCaptcha().
   */
  public static function overrideTitle(array $element, FormStateInterface $form_state): array {
    if (isset($element['captcha_widgets']['captcha_response']['#title'])) {
      $element['captcha_widgets']['captcha_response']['#title'] = 'Prove you are human';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Form submitted!'));
  }

}
