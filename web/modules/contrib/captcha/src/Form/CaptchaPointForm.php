<?php

namespace Drupal\captcha\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\captcha\Service\CaptchaService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Entity Form to edit CAPTCHA points.
 */
class CaptchaPointForm extends EntityForm {

  /**
   * The CAPTCHA helper service.
   *
   * @var \Drupal\captcha\Service\CaptchaService
   */
  protected $captchaService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * CaptchaPointForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Constructor.
   * @param \Drupal\captcha\Service\CaptchaService $captcha_service
   *   The captcha service.
   */
  public function __construct(RequestStack $request_stack, CaptchaService $captcha_service) {
    $this->requestStack = $request_stack;
    $this->captchaService = $captcha_service;
  }

  /**
   * Create Captcha Points.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Event to create Captcha points.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('captcha.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\captcha\CaptchaPointInterface $captchaPoint */
    $captcha_point = $this->entity;

    // Support to set a default form_id through a query argument.
    $request = $this->requestStack->getCurrentRequest();
    if ($captcha_point->isNew() && !$captcha_point->id() && $request->query->has('form_id')) {
      $captcha_point->set('formId', $request->query->get('form_id'));
      $captcha_point->set('label', $request->query->get('form_id'));
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID'),
      '#description' => $this->t('The internal ID of the form, for example <code>user_login_form</code> or <code>contact_message_feedback_form</code>. The base form ID, for example <code>contact_message_form</code>, also works. To find the ID of a form, check <em>Add CAPTCHA administration information to forms</em> on the CAPTCHA settings page and visit the form.'),
      '#default_value' => $captcha_point->label(),
      '#required' => TRUE,
    ];

    $form['formId'] = [
      '#type' => 'machine_name',
      '#default_value' => $captcha_point->id(),
      '#machine_name' => [
        'exists' => [CaptchaPoint::class, 'load'],
      ],
      '#disable' => !$captcha_point->isNew(),
      '#required' => TRUE,
    ];

    // Select widget for CAPTCHA type.
    $form['captchaType'] = [
      '#type' => 'select',
      '#title' => $this->t('Challenge type'),
      '#description' => $this->t('The CAPTCHA type to use for this form.'),
      '#default_value' => $captcha_point->getCaptchaType() ?: $this->config('captcha.settings')->get('default_challenge'),
      '#options' => $this->captchaService->getAvailableChallengeTypes(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\captcha\Entity\CaptchaPoint $captcha_point */
    $captcha_point = $this->entity;
    $status = $captcha_point->save();

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('CAPTCHA Point for %form_id form was created.', [
        '%form_id' => $captcha_point->getFormId(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('CAPTCHA Point for %form_id form was updated.', [
        '%form_id' => $captcha_point->getFormId(),
      ]));
    }
    $form_state->setRedirect('captcha_point.list');
    return $status;
  }

}
