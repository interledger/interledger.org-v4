<?php

declare(strict_types=1);

namespace Drupal\admin_audit_trail\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hooks for the Admin Audit Trail module.
 */
class AdminAuditTrailViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];

    $data['admin_audit_trail']['table']['group'] = $this->t('Admin Audit Trail');

    $data['admin_audit_trail']['table']['base'] = [
      'field' => 'lid',
      'title' => $this->t('Admin Audit Trail'),
      'help' => $this->t('Contains a log of administrative actions and events.'),
      'weight' => -10,
    ];

    $data['admin_audit_trail']['table']['wizard_id'] = 'admin_audit_trail';

    // Log ID field.
    $data['admin_audit_trail']['lid'] = [
      'title' => $this->t('Log ID'),
      'help' => $this->t('The unique ID of the log entry.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];

    // Type field.
    $data['admin_audit_trail']['type'] = [
      'title' => $this->t('Type'),
      'help' => $this->t('The type of event (e.g., node, user, taxonomy).'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'audit_trail_types',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ];

    // Operation field.
    $data['admin_audit_trail']['operation'] = [
      'title' => $this->t('Operation'),
      'help' => $this->t('The operation performed (e.g., insert, update, delete).'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'audit_trail_operations',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ];

    // Path field.
    $data['admin_audit_trail']['path'] = [
      'title' => $this->t('Path'),
      'help' => $this->t('The path where the event occurred.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ];

    // Numeric reference field.
    $data['admin_audit_trail']['ref_numeric'] = [
      'title' => $this->t('Reference ID'),
      'help' => $this->t('A numeric reference ID (e.g., node ID).'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ];

    // Character reference field.
    $data['admin_audit_trail']['ref_char'] = [
      'title' => $this->t('Reference Name'),
      'help' => $this->t('A character reference (e.g., machine name).'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ];

    // Description field.
    $data['admin_audit_trail']['description'] = [
      'title' => $this->t('Description'),
      'help' => $this->t('A description of the event.'),
      'field' => [
        'id' => 'audit_trail_description',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    // User ID field with relationship to users.
    $data['admin_audit_trail']['uid'] = [
      'title' => $this->t('User ID'),
      'help' => $this->t('The user who triggered the event.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'relationship' => [
        'title' => $this->t('User'),
        'help' => $this->t('The user who triggered the event.'),
        'base' => 'users_field_data',
        'base field' => 'uid',
        'id' => 'standard',
        'label' => $this->t('User'),
      ],
    ];

    // IP address field.
    $data['admin_audit_trail']['ip'] = [
      'title' => $this->t('IP Address'),
      'help' => $this->t('The IP address of the user.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ];

    // Created timestamp field.
    $data['admin_audit_trail']['created'] = [
      'title' => $this->t('Timestamp'),
      'help' => $this->t('The date and time when the event occurred.'),
      'field' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
    ];

    return $data;
  }

}
