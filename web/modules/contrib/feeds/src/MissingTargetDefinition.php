<?php

namespace Drupal\feeds;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Definition for a missing target.
 */
class MissingTargetDefinition implements TargetDefinitionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Error: target is missing');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyLabel($property) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDescription($property) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnique($property) {
    return FALSE;
  }

}
