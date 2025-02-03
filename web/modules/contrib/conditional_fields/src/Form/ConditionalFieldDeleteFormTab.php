<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * A conditional field delete form designed to be displayed as a tab.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldDeleteFormTab extends ConditionalFieldDeleteForm {

  /**
   * The entity type this conditional field is attached to.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle type this conditional field is attached to.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->entityType == 'paragraph') {
      return Url::fromRoute('conditional_fields.tab' . "." . $this->entityType, [
        "{$this->entityType}s_type" => $this->bundle,
      ]);
    }

    return Url::fromRoute('conditional_fields.tab' . "." . $this->entityType, [
      "{$this->entityType}_type" => $this->bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_delete_form_tab';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL, $field_name = NULL, $uuid = NULL) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
    return parent::buildForm($form, $form_state, $entity_type, $bundle, $field_name, $uuid);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
