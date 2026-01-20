<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\MissingItemException;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation of the copy plugin.
 *
 * @Tamper(
 *   id = "copy",
 *   label = @Translation("Copy"),
 *   description = @Translation("Copy value from one source to another."),
 *   category = @Translation("Other"),
 *   itemUsage = "required"
 * )
 */
class Copy extends TamperBase {

  const SETTING_TO_FROM = 'to_from';
  const SETTING_SOURCE = 'copy_source';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TO_FROM] = 'to';
    $config[self::SETTING_SOURCE] = NULL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $sources = [];
    foreach ($this->sourceDefinition->getList() as $key => $label) {
      $sources[$key] = $label;
    }

    $form[self::SETTING_TO_FROM] = [
      '#type' => 'radios',
      '#title' => $this->t('To or from'),
      '#options' => ['to' => $this->t('To'), 'from' => $this->t('From')],
      '#default_value' => $this->getSetting(self::SETTING_TO_FROM),
      '#description' => $this->t('Select whether this source value should be copied <em>to</em> another source, or <em>from</em> another source to this one.'),
    ];

    $form[self::SETTING_SOURCE] = [
      '#type' => 'radios',
      '#title' => $this->t('Source'),
      '#options' => $sources,
      '#default_value' => $this->getSetting(self::SETTING_SOURCE),
      '#description' => $this->t('List of sources accessible for copying.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_TO_FROM => $form_state->getValue(self::SETTING_TO_FROM),
      self::SETTING_SOURCE => $form_state->getValue(self::SETTING_SOURCE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSourceProperties(TamperableItemInterface $item): array {
    $source = $this->getSetting(self::SETTING_SOURCE);
    if (isset($source) && $this->getSetting(self::SETTING_TO_FROM) == 'from') {
      // Only if the data is taken from a source property it is considered
      // 'used'. If data gets copied to a source property instead, the data from
      // that property gets overwritten, so the value it had does not get used.
      return [$source];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    if (!$item instanceof TamperableItemInterface) {
      throw new MissingItemException('The copy plugin requires a tamperable item.');
    }

    $source = $this->getSetting(self::SETTING_SOURCE);

    if ($this->getSetting(self::SETTING_TO_FROM) == "to") {
      $item->setSourceProperty($source, $data);
    }
    elseif ($this->getSetting(self::SETTING_TO_FROM) == "from") {
      $data = $item->getSourceProperty($source);
    }

    return $data;
  }

}
