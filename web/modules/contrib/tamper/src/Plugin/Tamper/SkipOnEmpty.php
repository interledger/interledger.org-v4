<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\ItemUsage;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;

/**
 * Plugin implementation for skipping applying further Tamper plugins.
 */
#[Tamper(
  id: 'skip_on_empty',
  label: new TranslatableMarkup('Skip tampers on empty'),
  description: new TranslatableMarkup("If it is empty, further Tamper plugins won't be applied."),
  category: new TranslatableMarkup('Filter'),
  itemUsage: ItemUsage::IGNORED,
)]
class SkipOnEmpty extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    if (empty($data)) {
      throw new SkipTamperDataException('Item is empty.');
    }

    return $data;
  }

}
