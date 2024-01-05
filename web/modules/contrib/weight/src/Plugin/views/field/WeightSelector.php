<?php

namespace Drupal\weight\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\weight\Plugin\Field\FieldWidget\WeightSelectorWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Field handler to present a weight selector element.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("weight_selector")
 */
class WeightSelector extends FieldPluginBase {
  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['range'] = ['default' => 20];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Range'),
      '#description' => $this->t('The range of weights available to select. For
        example, a range of 20 will allow you to select a weight between -20
        and 20.'),
      '#default_value' => $this->options['range'],
      '#size' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $options = WeightSelectorWidget::rangeOptions($this->options['range']);

    // At this point the query already run, so we can access the results.
    foreach ($this->view->result as $row_index => $row) {
      $entity = $this->getEntity($row);
      $field_langcode = $entity->getEntityTypeId() . '__' . $this->field . '_langcode';

      $form[$this->options['id']][$row_index]['weight'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $this->getValue($row),
        '#attributes' => ['class' => ['weight-selector']],
      ];

      $form[$this->options['id']][$row_index]['entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];

      $form[$this->options['id']][$row_index]['langcode'] = [
        '#type' => 'value',
        '#value' => isset($row->{$field_langcode}) ? $row->{$field_langcode} : NULL,
      ];
    }

    $form['views_field'] = [
      '#type' => 'value',
      '#value' => $this->field,
    ];

    $form['#action'] = $this->requestStack->getCurrentRequest()->getRequestUri();
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getValue('views_field');
    if (!$field_name) {
      return;
    }
    $rows = $form_state->getValue($field_name);

    foreach ($rows as $row) {
      if ($row['langcode']) {
        $entity = $row['entity']->getTranslation($row['langcode']);
      }
      else {
        $entity = $row['entity'];
      }
      if ($entity && $entity->hasField($field_name)) {
        $entity->set($field_name, $row['weight']);
        $entity->save();
        \Drupal::messenger()->addMessage($this->t('Your changes have been saved.'));
      }
    }
  }

}
