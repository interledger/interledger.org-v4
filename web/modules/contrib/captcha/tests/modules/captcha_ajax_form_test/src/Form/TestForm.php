<?php

declare(strict_types=1);

namespace Drupal\captcha_ajax_form_test\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * An AJAX form used for testing the CAPTCHA module.
 */
final class TestForm extends FormBase {

  /**
   * Tracks the number of times the form has been submitted.
   *
   * @var int
   */
  protected int $count = 0;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(TRUE);

    $form['#id'] = Html::getUniqueId($this->getFormId());

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit']['#type'] = 'submit';
    $form['actions']['submit']['#value'] = $this->t('Submit');
    $form['actions']['submit']['#ajax']['callback'] = '::submit';
    $form['actions']['submit']['#ajax']['wrapper'] = $form['#id'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_ajax_form_test';
  }

  /**
   * Render the form via AJAX.
   *
   * @param array $form
   *   The form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function submit(array &$form): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $form));

    /** @var array<string,array<int,\Stringable|string>> */
    $messages_by_type = $this->messenger()->deleteAll();
    $clear_previous = TRUE;

    foreach ($messages_by_type as $type => $messages) {
      foreach ($messages as $message) {
        $response->addCommand(new MessageCommand($message, NULL, ['type' => $type], $clear_previous));
        $clear_previous = FALSE;
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    $this->messenger()->addStatus($this->t('Successfully submitted!'));
    $this->messenger()->addStatus($this->t('Count: @count', ['@count' => ++$this->count]));
  }

}
