<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test second field plugin.
 */
#[DsField(
  id: 'test_field_2',
  title: new TranslatableMarkup('Test field plugin 2'),
  entity_type: 'node'
)]
class TestField2 extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'Test field plugin on node ' . $this->entity()->id()];
  }

}
