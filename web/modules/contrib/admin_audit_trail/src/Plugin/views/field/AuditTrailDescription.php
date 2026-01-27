<?php

declare(strict_types=1);

namespace Drupal\admin_audit_trail\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a field handler that renders audit trail descriptions safely.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("audit_trail_description")]
class AuditTrailDescription extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function clickSortable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): mixed {
    $value = $this->getValue($values);
    // Use xss_admin to allow safe HTML markup in admin descriptions.
    return $this->sanitizeValue($value, 'xss_admin');
  }

}
