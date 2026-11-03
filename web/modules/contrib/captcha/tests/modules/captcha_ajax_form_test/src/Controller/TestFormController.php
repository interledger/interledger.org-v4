<?php

declare(strict_types=1);

namespace Drupal\captcha_ajax_form_test\Controller;

use Drupal\captcha_ajax_form_test\Form\TestForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the test form.
 */
final class TestFormController extends ControllerBase {

  /**
   * Render the test form in an AJAX command.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function response(): AjaxResponse {
    $form_builder = $this->formBuilder();
    $form = $form_builder->getForm(TestForm::class);

    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $form));

    return $response;
  }

}
