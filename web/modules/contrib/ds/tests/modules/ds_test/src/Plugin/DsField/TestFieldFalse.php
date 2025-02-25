<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test field plugin that returns FALSE.
 */
#[DsField(
  id: 'test_field_false',
  title: new TranslatableMarkup('Test field plugin that returns FALSE'),
  entity_type: 'node'
)]
class TestFieldFalse extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return FALSE;
  }

}
