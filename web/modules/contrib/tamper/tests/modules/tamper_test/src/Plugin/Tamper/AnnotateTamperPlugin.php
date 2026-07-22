<?php

namespace Drupal\tamper_test\Plugin\Tamper;

use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Provides a Tamper plugin defined with annotation.
 *
 * @Tamper(
 *   id = "annotate_tamper",
 *   label = @Translation("Annotate Tamper plugin"),
 *   description = @Translation("Used for testing if this plugin is found by TamperManager."),
 *   category = @Translation("Other"),
 *   handle_multiples = TRUE,
 *   itemUsage = "ignored"
 * )
 */
class AnnotateTamperPlugin extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    return $data;
  }

}
