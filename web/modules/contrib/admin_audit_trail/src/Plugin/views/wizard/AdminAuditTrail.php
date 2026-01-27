<?php

declare(strict_types=1);

namespace Drupal\admin_audit_trail\Plugin\views\wizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsWizard;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the admin_audit_trail table.
 */
#[ViewsWizard(
  id: 'admin_audit_trail',
  title: new TranslatableMarkup('Admin Audit Trail'),
  base_table: 'admin_audit_trail'
)]
class AdminAuditTrail extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
   */
  protected $createdColumn = 'created';

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions(): array {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access admin audit trail';

    return $display_options;
  }

}
