<?php

declare(strict_types=1);

namespace Drupal\captcha_ajax_form_test\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * A form used to load the test form via AJAX.
 */
final class TestFormLoaderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = Html::getUniqueId($this->getFormId());

    $form['actions']['#type'] = 'actions';

    $form['actions']['load']['#type'] = 'submit';
    $form['actions']['load']['#value'] = $this->t('Load');
    $form['actions']['load']['#ajax']['url'] = Url::fromRoute('captcha_ajax_form_test.form');
    $form['actions']['load']['#ajax']['wrapper'] = $form['#id'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_ajax_form_test_loader';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
