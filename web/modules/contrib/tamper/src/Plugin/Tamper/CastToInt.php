<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for casting to integer.
 */
#[Tamper(
  id: 'cast_to_int',
  label: new TranslatableMarkup('Cast to integer'),
  description: new TranslatableMarkup('This plugin will convert any value to its integer form.'),
  category: new TranslatableMarkup('Text'),
  itemUsage: ItemUsage::IGNORED,
)]
class CastToInt extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    return (int) $data;
  }

}
