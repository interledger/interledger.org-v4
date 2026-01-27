# Creating Custom Event Handlers

Learn how to extend Admin Audit Trail with custom event handlers to track specific events in your Drupal site.

## Overview

Admin Audit Trail provides a flexible API for logging custom events. You can:

- Track custom entity types
- Log specific form submissions
- Monitor custom operations
- Track API events or external integrations
- Log business-specific workflows

## Basic Concepts

### Event Handlers

**Event handlers** are registered callbacks that respond to specific Drupal hooks or form submissions to create audit log entries.

**Components**:
1. **Hook implementation** - Registers your handler
2. **Callback function** - Processes the event and creates log
3. **Log entry** - The data stored in the audit trail

### Log Entry Structure

Every audit trail log entry contains:

```php
[
  'type' => 'string',         // Entity/event type (required)
  'operation' => 'string',    // Operation type (required)
  'description' => 'string',  // Human-readable description (required)
  'ref_numeric' => 123,       // Numeric reference ID (optional)
  'ref_char' => 'string',     // Character reference ID (optional)
  'uid' => 1,                 // User ID (auto-filled if empty)
  'created' => 1234567890,    // Timestamp (auto-filled if empty)
  'ip' => '192.168.1.1',      // IP address (auto-filled if empty)
  'path' => '/node/123',      // Current path (auto-filled if empty)
]
```

## Implementing Custom Handlers

### Method 1: Hook Implementation

Use `hook_admin_audit_trail_handlers()` to register event handlers.

**Step 1: Create a custom module**

```bash
mkdir -p modules/custom/my_audit_module
cd modules/custom/my_audit_module
```

**Step 2: Create module files**

**my_audit_module.info.yml**:
```yaml
name: 'My Audit Module'
type: module
description: 'Custom audit trail events'
core_version_requirement: ^10 || ^11
package: 'Custom'
dependencies:
  - admin_audit_trail:admin_audit_trail
```

**my_audit_module.module**:
```php
<?php

/**
 * @file
 * Custom audit trail event handlers.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_admin_audit_trail_handlers().
 */
function my_audit_module_admin_audit_trail_handlers() {
  $handlers = [];

  // Track specific form submissions.
  $handlers['my_custom_form'] = [
    'title' => t('My Custom Form Submissions'),
    'form_ids' => ['my_custom_form'],
    'form_submit_callback' => 'my_audit_module_log_custom_form',
  ];

  return $handlers;
}

/**
 * Form submission callback for custom form.
 */
function my_audit_module_log_custom_form($form, FormStateInterface $form_state) {
  $log = [
    'type' => 'custom_form',
    'operation' => 'submit',
    'description' => t('Custom form submitted with value: @value', [
      '@value' => $form_state->getValue('my_field'),
    ]),
  ];

  admin_audit_trail_insert($log);
}
```

### Method 2: Direct Logging with Hooks

Use entity hooks to log operations directly.

**Example: Track custom entity operations**

```php
<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_entity_insert().
 */
function my_audit_module_entity_insert(EntityInterface $entity) {
  // Only track our custom entity type.
  if ($entity->getEntityTypeId() !== 'my_custom_entity') {
    return;
  }

  $log = [
    'type' => 'my_custom_entity',
    'operation' => 'insert',
    'description' => t('Created @entity_type: @label', [
      '@entity_type' => $entity->getEntityType()->getLabel(),
      '@label' => $entity->label(),
    ]),
    'ref_numeric' => $entity->id(),
  ];

  admin_audit_trail_insert($log);
}

/**
 * Implements hook_entity_update().
 */
function my_audit_module_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'my_custom_entity') {
    return;
  }

  // Get changed fields.
  $changes = [];
  if (method_exists($entity, 'original') && $entity->original) {
    foreach ($entity->getFields() as $field_name => $field) {
      if ($entity->get($field_name)->getValue() !== $entity->original->get($field_name)->getValue()) {
        $changes[] = $field_name;
      }
    }
  }

  $log = [
    'type' => 'my_custom_entity',
    'operation' => 'update',
    'description' => t('Updated @entity_type: @label - Changed fields: @fields', [
      '@entity_type' => $entity->getEntityType()->getLabel(),
      '@label' => $entity->label(),
      '@fields' => implode(', ', $changes),
    ]),
    'ref_numeric' => $entity->id(),
  ];

  admin_audit_trail_insert($log);
}

/**
 * Implements hook_entity_delete().
 */
function my_audit_module_entity_delete(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'my_custom_entity') {
    return;
  }

  $log = [
    'type' => 'my_custom_entity',
    'operation' => 'delete',
    'description' => t('Deleted @entity_type: @label (ID: @id)', [
      '@entity_type' => $entity->getEntityType()->getLabel(),
      '@label' => $entity->label(),
      '@id' => $entity->id(),
    ]),
    'ref_numeric' => $entity->id(),
  ];

  admin_audit_trail_insert($log);
}
```

### Method 3: Tracking Custom Events

Log custom business logic events.

```php
<?php

/**
 * Example: Track product price changes.
 */
function my_audit_module_track_price_change($product_id, $old_price, $new_price) {
  $product = \Drupal::entityTypeManager()
    ->getStorage('commerce_product')
    ->load($product_id);

  $log = [
    'type' => 'commerce_product',
    'operation' => 'price_change',
    'description' => t('Product "@title" price changed from $@old to $@new', [
      '@title' => $product->getTitle(),
      '@old' => number_format($old_price, 2),
      '@new' => number_format($new_price, 2),
    ]),
    'ref_numeric' => $product_id,
  ];

  admin_audit_trail_insert($log);
}

/**
 * Example: Track API calls.
 */
function my_audit_module_track_api_call($endpoint, $method, $status_code) {
  $log = [
    'type' => 'api_call',
    'operation' => strtolower($method),
    'description' => t('API @method request to @endpoint - Status: @status', [
      '@method' => $method,
      '@endpoint' => $endpoint,
      '@status' => $status_code,
    ]),
    'ref_char' => $endpoint,
  ];

  admin_audit_trail_insert($log);
}

/**
 * Example: Track file downloads.
 */
function my_audit_module_track_file_download($file_id) {
  $file = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->load($file_id);

  $log = [
    'type' => 'file',
    'operation' => 'download',
    'description' => t('File downloaded: @filename', [
      '@filename' => $file->getFilename(),
    ]),
    'ref_numeric' => $file_id,
  ];

  admin_audit_trail_insert($log);
}
```

## Advanced Patterns

### Using Regular Expressions for Form IDs

Track multiple forms with a pattern:

```php
function my_audit_module_admin_audit_trail_handlers() {
  $handlers = [];

  // Track all webform submissions.
  $handlers['webform_submissions'] = [
    'title' => t('Webform Submissions'),
    'form_ids_regexp' => ['/^webform_submission_.*_form$/'],
    'form_submit_callback' => 'my_audit_module_log_webform_submission',
  ];

  return $handlers;
}

function my_audit_module_log_webform_submission($form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformSubmissionInterface $submission */
  $submission = $form_state->getFormObject()->getEntity();
  $webform = $submission->getWebform();

  $log = [
    'type' => 'webform_submission',
    'operation' => 'submit',
    'description' => t('Webform "@title" submitted', [
      '@title' => $webform->label(),
    ]),
    'ref_numeric' => $submission->id(),
    'ref_char' => $webform->id(),
  ];

  admin_audit_trail_insert($log);
}
```

### Conditional Logging

Only log under certain conditions:

```php
function my_audit_module_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'node') {
    return;
  }

  // Only log published content changes.
  if (!$entity->isPublished()) {
    return;
  }

  // Only log specific content types.
  $tracked_types = ['article', 'page', 'landing_page'];
  if (!in_array($entity->bundle(), $tracked_types)) {
    return;
  }

  // Only log if specific fields changed.
  $important_fields = ['title', 'body', 'field_price'];
  $changed = FALSE;

  foreach ($important_fields as $field_name) {
    if ($entity->hasField($field_name) && $entity->get($field_name)->getValue() !== $entity->original->get($field_name)->getValue()) {
      $changed = TRUE;
      break;
    }
  }

  if (!$changed) {
    return;
  }

  $log = [
    'type' => 'node',
    'operation' => 'important_update',
    'description' => t('Critical field updated in @type: @title', [
      '@type' => $entity->bundle(),
      '@title' => $entity->getTitle(),
    ]),
    'ref_numeric' => $entity->id(),
  ];

  admin_audit_trail_insert($log);
}
```

### Tracking Field Changes

Log detailed field-level changes:

```php
function my_audit_module_log_field_changes(EntityInterface $entity) {
  if (!isset($entity->original)) {
    return;
  }

  $changes = [];
  foreach ($entity->getFields() as $field_name => $field) {
    // Skip computed and internal fields.
    if ($field->getFieldDefinition()->isComputed() || strpos($field_name, 'field_') !== 0) {
      continue;
    }

    $old_value = $entity->original->get($field_name)->getValue();
    $new_value = $entity->get($field_name)->getValue();

    if ($old_value !== $new_value) {
      $changes[$field_name] = [
        'old' => $old_value,
        'new' => $new_value,
      ];
    }
  }

  if (!empty($changes)) {
    $log = [
      'type' => $entity->getEntityTypeId(),
      'operation' => 'field_changes',
      'description' => t('Fields changed in @entity: @fields', [
        '@entity' => $entity->label(),
        '@fields' => implode(', ', array_keys($changes)),
      ]),
      'ref_numeric' => $entity->id(),
    ];

    admin_audit_trail_insert($log);
  }
}
```

### Using the Alter Hook

Modify or enhance log entries before they're saved:

```php
/**
 * Implements hook_admin_audit_trail_log_alter().
 */
function my_audit_module_admin_audit_trail_log_alter(array &$log) {
  // Add custom reference field for specific event types.
  if ($log['type'] === 'node' && !empty($log['ref_numeric'])) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($log['ref_numeric']);

    if ($node && $node->hasField('field_department')) {
      $department = $node->get('field_department')->target_id;
      $log['ref_char'] = 'dept_' . $department;
    }
  }

  // Sanitize sensitive data from descriptions.
  if (stripos($log['description'], 'password') !== FALSE) {
    $log['description'] = preg_replace(
      '/password[:\s]+\S+/i',
      'password: [REDACTED]',
      $log['description']
    );
  }

  // Add contextual information.
  if ($log['type'] === 'commerce_order') {
    $log['description'] .= ' [Total: $' . $log['order_total'] . ']';
  }
}
```

## Complete Example: Custom Module

Here's a complete example tracking custom product reviews:

**product_review_audit.info.yml**:
```yaml
name: 'Product Review Audit'
type: module
description: 'Tracks product review submissions and moderation'
core_version_requirement: ^10 || ^11
package: 'Custom'
dependencies:
  - admin_audit_trail:admin_audit_trail
  - commerce:commerce_product
```

**product_review_audit.module**:
```php
<?php

/**
 * @file
 * Audit trail tracking for product reviews.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_entity_insert().
 */
function product_review_audit_entity_insert(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'comment' || $entity->bundle() !== 'product_review') {
    return;
  }

  $product = $entity->getCommentedEntity();
  $rating = $entity->get('field_rating')->value;

  $log = [
    'type' => 'product_review',
    'operation' => 'submit',
    'description' => t('New review submitted for "@product" - Rating: @rating stars', [
      '@product' => $product->getTitle(),
      '@rating' => $rating,
    ]),
    'ref_numeric' => $entity->id(),
    'ref_char' => 'product_' . $product->id(),
  ];

  admin_audit_trail_insert($log);
}

/**
 * Implements hook_entity_update().
 */
function product_review_audit_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'comment' || $entity->bundle() !== 'product_review') {
    return;
  }

  // Track moderation status changes.
  if (isset($entity->original)) {
    $old_status = $entity->original->get('status')->value;
    $new_status = $entity->get('status')->value;

    if ($old_status !== $new_status) {
      $product = $entity->getCommentedEntity();
      $action = $new_status ? 'approved' : 'rejected';

      $log = [
        'type' => 'product_review',
        'operation' => $action,
        'description' => t('Review @action for product "@product"', [
          '@action' => $action,
          '@product' => $product->getTitle(),
        ]),
        'ref_numeric' => $entity->id(),
      ];

      admin_audit_trail_insert($log);
    }
  }
}

/**
 * Implements hook_entity_delete().
 */
function product_review_audit_entity_delete(EntityInterface $entity) {
  if ($entity->getEntityTypeId() !== 'comment' || $entity->bundle() !== 'product_review') {
    return;
  }

  $product = $entity->getCommentedEntity();

  $log = [
    'type' => 'product_review',
    'operation' => 'delete',
    'description' => t('Review deleted for product "@product" - Reason: @reason', [
      '@product' => $product ? $product->getTitle() : 'Unknown',
      '@reason' => $entity->get('field_delete_reason')->value ?? 'Not specified',
    ]),
    'ref_numeric' => $entity->id(),
  ];

  admin_audit_trail_insert($log);
}
```

## Testing Your Custom Handler

### Manual Testing

1. **Enable your module**:
   ```bash
   drush en my_audit_module -y
   drush cr
   ```

2. **Trigger the event**:
   - Submit the form
   - Create/update/delete the entity
   - Perform the action you're tracking

3. **Verify the log**:
   - Visit `/admin/reports/audit-trail`
   - Filter by your custom type
   - Check the log entry appears correctly

### Automated Testing

**Kernel test example**:

```php
<?php

namespace Drupal\Tests\my_audit_module\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests custom audit trail logging.
 *
 * @group my_audit_module
 */
class CustomAuditTrailTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'admin_audit_trail',
    'my_audit_module',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('admin_audit_trail', ['admin_audit_trail']);
  }

  /**
   * Tests that custom events are logged.
   */
  public function testCustomEventLogging() {
    // Trigger the event.
    my_audit_module_track_api_call('/api/v1/users', 'POST', 201);

    // Verify the log was created.
    $logs = \Drupal::database()
      ->select('admin_audit_trail', 'a')
      ->fields('a')
      ->condition('type', 'api_call')
      ->execute()
      ->fetchAll();

    $this->assertCount(1, $logs);
    $this->assertEquals('post', $logs[0]->operation);
    $this->assertStringContainsString('POST', $logs[0]->description);
  }
}
```

## Best Practices

### 1. Clear Naming Conventions

- **Type**: Use entity type or event category
- **Operation**: Use standard terms (insert, update, delete, submit, etc.)
- **Description**: Human-readable, include key details

### 2. Provide Context

Include relevant information in descriptions:
```php
// Good
'description' => t('Updated article "@title" - Changed: @fields', [
  '@title' => $node->getTitle(),
  '@fields' => implode(', ', $changed_fields),
])

// Bad
'description' => t('Node updated')
```

### 3. Use References

Always include reference IDs when applicable:
```php
'ref_numeric' => $entity->id(),  // Entity ID
'ref_char' => $entity->bundle(),  // Bundle or other identifier
```

### 4. Avoid Over-Logging

Don't log every single event - focus on important ones:
- Security-relevant actions
- Business-critical operations
- Compliance-required events

### 5. Sanitize Sensitive Data

Never log passwords, API keys, or PII in descriptions:
```php
// Use hook_admin_audit_trail_log_alter() to sanitize
if (stripos($log['description'], 'password') !== FALSE) {
  $log['description'] = preg_replace('/password.*/', 'password: [REDACTED]', $log['description']);
}
```

## Next Steps

- [Review API reference](1-api-reference.md) for complete function documentation
- [Understand database schema](2-database-schema.md) for advanced queries
- [Learn about testing](3-testing.md) your implementations
