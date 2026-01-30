<?php

namespace Drupal\Tests\feeds_tamper\Traits;

use Drupal\field\Entity\FieldConfig;

/**
 * Provides methods useful for Kernel and Functional tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsTamperCommonTrait {

  /**
   * Installs body field.
   */
  protected function addBodyField() {
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => $this->nodeType->id(),
      'field_name' => 'body',
      'label' => 'Body',
      'settings' => [
        'display_summary' => TRUE,
        'allowed_formats' => [],
      ],
    ])->save();
  }

}
