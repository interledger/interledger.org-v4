<?php

namespace Drupal\weight\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'weight selector' widget.
 *
 * @FieldWidget(
 *   id = "weight_selector",
 *   label = @Translation("Weight Selector"),
 *   field_types = {
 *     "weight"
 *   }
 * )
 */
class WeightSelectorWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $range = $this->getFieldSetting('range');

    $element += [
      '#type' => 'select',
      '#options' => WeightSelectorWidget::rangeOptions($range),
      '#default_value' => $value,
    ];

    return ['value' => $element];
  }

  /**
   * Get weight range options.
   */
  public static function rangeOptions($range) {
    $options = [];

    for ($i = -$range; $i <= $range; $i++) {
      $options[$i] = $i;
    }

    return $options;
  }

}
