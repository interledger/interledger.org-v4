<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for unique tamper.
 */
#[Tamper(
  id: 'unique',
  label: new TranslatableMarkup('Unique'),
  description: new TranslatableMarkup('Makes the elements in a multivalued field unique.'),
  category: new TranslatableMarkup('List'),
  handle_multiples: TRUE,
  itemUsage: ItemUsage::IGNORED,
)]
class Unique extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Don't process empty values.
    if (empty($data)) {
      return $data;
    }

    if (!is_array($data)) {
      throw new TamperException('Input should be an array.');
    }
    return array_values(array_unique($data));
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
