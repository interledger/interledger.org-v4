<?php

namespace Drupal\admin_audit_trail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configure user settings for this site.
 */
class OverviewForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Overview form construct.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    RequestStack $request_stack,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Filters.
   *
   * @var array
   *  The form filters
   */
  private $filters = [];

  /**
   * Get User data.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\Core\Link
   *   The internal link for the user.
   */
  private function getUserData($uid) {
    if (empty($uid)) {
      return Markup::create('<em>' . $this->t('Anonymous') . '</em>');
    }
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    if (empty($account)) {
      return Markup::create('<em>' . $this->t('@uid (deleted)', [
        '@uid' => $uid,
      ]) . '<em>');
    }
    return Link::fromTextAndUrl((string) $account->getDisplayName(), Url::fromUri('internal:/user/' . $account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_audit_trail_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('admin_audit_trail.settings');

    // Add deprecation notice.
    $form['deprecation_notice'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['messages', 'messages--warning'],
      ],
      '#weight' => -100,
      'message' => [
        '#markup' => $this->t('<strong>Deprecated:</strong> This form-based report is deprecated and will be removed in a future version. Please use the <a href="@views_url">Views-based Admin Audit Trail report</a> instead.', [
          '@views_url' => '/admin/reports/audit-trail',
        ]),
      ],
    ];

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Filter the events.'),
      '#description_display' => 'after',
      '#open' => $config->get('filter_expanded') ?? TRUE,
    ];

    $handlers = admin_audit_trail_get_event_handlers();
    $options = [];
    foreach ($handlers as $type => $handler) {
      $options[$type] = $handler['title'];
    }
    $form['filters']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('Event type'),
      '#description_display' => 'after',
      '#options' => ['' => $this->t('Select a type')] + $options,
      '#ajax' => [
        'callback' => '::formGetAjaxOperation',
        'event' => 'change',
      ],
    ];

    $form['filters']['operation'] = AdminAuditTrailStorage::formGetOperations(empty($form_state->getUserInput()['type']) ? '' : $form_state->getUserInput()['type']);

    $form['filters']['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('User'),
      '#description' => $this->t('The user that triggered this event.'),
      '#description_display' => 'after',
      '#size' => 30,
      '#maxlength' => 60,
    ];

    $form['filters']['id'] = [
      '#type' => 'textfield',
      '#size' => 5,
      '#title' => $this->t('ID'),
      '#description' => $this->t('The id of the events (numeric).'),
      '#description_display' => 'after',
    ];

    $form['filters']['ip'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('IP'),
      '#description' => $this->t('The ip address of the visitor.'),
      '#description_display' => 'after',
    ];

    $form['filters']['name'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Name'),
      '#description' => $this->t('The name or machine name.'),
      '#description_display' => 'after',
    ];

    $form['filters']['path'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => $this->t('Path'),
      '#description' => $this->t('keyword in the path.'),
      '#description_display' => 'after',
    ];

    $form['filters']['keyword'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => $this->t('Description'),
      '#description' => $this->t('Keyword in the description.'),
      '#description_display' => 'after',
    ];

    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $header = [
        [
          'data' => $this->t('Updated'),
          'field' => 'created',
          'sort' => 'desc',
        ],
        ['data' => $this->t('Type'), 'field' => 'type'],
        ['data' => $this->t('Operation'), 'field' => 'operation'],
        ['data' => $this->t('Path'), 'field' => 'path'],
        ['data' => $this->t('Description'), 'field' => 'description'],
        ['data' => $this->t('User'), 'field' => 'uid'],
        ['data' => $this->t('IP'), 'field' => 'ip'],
        ['data' => $this->t('ID'), 'field' => 'ref_numeric'],
        ['data' => $this->t('Name'), 'field' => 'ref_char'],
    ];

    $this->getFiltersFromUrl($form);
    $result = AdminAuditTrailStorage::getSearchData($this->filters, $header, 20);

    if (!empty($this->filters)) {
      $form['filters']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }

    $rows = [];
    foreach ($result as $record) {
      $userLink = $this->getUserData($record->uid);
      $rows[] = [
          ['data' => date("Y-m-d H:i:s", $record->created)],
          ['data' => $record->type],
          ['data' => $record->operation],
          ['data' => $record->path],
          ['data' => strip_tags($record->description)],
          ['data' => $userLink],
          ['data' => $record->ip],
          ['data' => $record->ref_numeric],
          ['data' => $record->ref_char],
      ];
    }

    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No events found.'),
    ];

    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
      '#parameters' => $this->filters,
    ];
    $form['results'] = $build;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
    $form_state->setRebuild();
    $this->setFilters($form_state);
  }

  /**
   * Resets all the states of the form.
   *
   * This method is called when the "Reset" button is triggered. Clears
   * user inputs and the form state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<current>');
    $form_state->setValues([]);
    $this->filters = [];
  }

  /**
   * Retrieves form filters from the URL.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function getFiltersFromUrl(array &$form) {
    $url_params = $this->requestStack->getCurrentRequest()->query->all();
    if (!empty($url_params)) {
      unset($url_params['page']);
      $this->filters = $url_params;
      foreach ($this->filters as $field => $value) {
        if ($field === "user") {
          $user = $this->entityTypeManager->getStorage('user')->load($value);
          $form['filters'][$field]['#default_value'] = $user;
        }
        else {
          $form['filters'][$field]['#default_value'] = $value;
        }
      }
    }
  }

  /**
   * Stores form filters in the URL.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function setFilters(FormStateInterface $form_state) {
    $this->filters = [];
    $values = $form_state->getValues();
    foreach ($values as $field => $value) {
      if ($field === 'submit') {
        break;
      }
      elseif (isset($value) && $value !== "") {
        $this->filters[$field] = $value;
      }
    }
    $this->requestStack->getCurrentRequest()->query->replace($this->filters);
  }

  /**
   * Ajax callback for the operations options.
   */
  public function formGetAjaxOperation(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $element = AdminAuditTrailStorage::formGetOperations($form_state->getValue('type'));
    $ajax_response->addCommand(new HtmlCommand('#operation-dropdown-replace', $element));

    return $ajax_response;
  }

}
