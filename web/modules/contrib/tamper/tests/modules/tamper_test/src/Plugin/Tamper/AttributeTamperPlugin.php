<?php

namespace Drupal\tamper_test\Plugin\Tamper;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Provides a Tamper plugin defined with attributes.
 */
#[Tamper(
  id: 'attribute_tamper',
  label: new TranslatableMarkup('Attribute Tamper plugin'),
  description: new TranslatableMarkup("Used for testing if this plugin is found by \\Drupal\\tamper\\TamperManager."),
  category: new TranslatableMarkup('Other'),
  handle_multiples: TRUE,
  itemUsage: 'ignored',
)]
class AttributeTamperPlugin extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    return $data;
  }

}
