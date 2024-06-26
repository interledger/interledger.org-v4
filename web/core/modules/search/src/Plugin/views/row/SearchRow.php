<?php

namespace Drupal\search\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Row handler plugin for displaying search results.
 */
#[ViewsRow(
  id: "search_view",
  title: new TranslatableMarkup("Search results"),
  help: new TranslatableMarkup("Provides a row plugin to display search results.")
)]
class SearchRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['score'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['score'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display score'),
      '#default_value' => $this->options['score'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    return [
      '#theme' => $this->definition['theme'],
      '#view' => $this->view,
      '#views_plugin' => $this,
      '#options' => $this->options,
      '#row' => $row,
    ];
  }

}
