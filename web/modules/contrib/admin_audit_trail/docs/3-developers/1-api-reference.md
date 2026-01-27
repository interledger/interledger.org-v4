# API Reference

Complete API documentation for Admin Audit Trail functions and hooks.

## Core Functions

### admin_audit_trail_insert()

Inserts a log record into the admin audit trail database.

**Function signature**:
```php
admin_audit_trail_insert(array &$log)
```

**Parameters**:
- `$log` (array, required) - The log record to be saved. Passed by reference.

**Log array structure**:
```php
[
  // Required fields
  'type' => 'string',         // Event type (e.g., 'node', 'user', 'taxonomy_term')
  'operation' => 'string',    // Operation type (e.g., 'insert', 'update', 'delete')
  'description' => 'string',  // Human-readable description of the event

  // Optional fields (auto-filled if empty)
  'uid' => 1,                 // User ID (defaults to current user)
  'created' => 1234567890,    // Unix timestamp (defaults to current time)
  'ip' => '192.168.1.1',      // IP address (defaults to client IP)
  'path' => '/node/123/edit', // Current path (defaults to current route)

  // Optional reference fields
  'ref_numeric' => 123,       // Numeric reference (e.g., entity ID)
  'ref_char' => 'string',     // Character reference (e.g., bundle name)
]
```

**Return value**: None (void)

**Behavior**:
1. Auto-fills missing optional fields (`uid`, `created`, `ip`, `path`)
2. Invokes `hook_admin_audit_trail_log_alter()` to allow modifications
3. Inserts the log into the database
4. Ignores CLI requests (PHP_SAPI == 'cli')

**Example usage**:
```php
$log = [
  'type' => 'node',
  'operation' => 'publish',
  'description' => t('Published article "@title"', ['@title' => $node->getTitle()]),
  'ref_numeric' => $node->id(),
];

admin_audit_trail_insert($log);
```

**Notes**:
- Always provide `type`, `operation`, and `description`
- Description supports HTML (stored in TEXT field)
- Function is ignored when run via CLI/Drush
- Log can be altered by other modules via alter hook

---

### admin_audit_trail_get_event_handlers()

Returns all registered event handlers from enabled modules.

**Function signature**:
```php
admin_audit_trail_get_event_handlers()
```

**Parameters**: None

**Return value**: Array of event handlers

**Return structure**:
```php
[
  'handler_id' => [
    'title' => 'Handler Title',
    'form_ids' => ['form_id_1', 'form_id_2'],
    'form_ids_regexp' => ['/pattern_1/', '/pattern_2/'],
    'form_submit_callback' => 'callback_function_name',
  ],
  // ... more handlers
]
```

**Example usage**:
```php
$handlers = admin_audit_trail_get_event_handlers();
foreach ($handlers as $handler_id => $handler) {
  print "Handler: {$handler['title']}\n";
}
```

**Notes**:
- Uses `drupal_static()` for caching
- Invokes `hook_admin_audit_trail_handlers()`
- Allows alterations via `hook_admin_audit_trail_handlers_alter()`
- Cached per request

---

### admin_audit_trail_form_submit()

Internal form submission callback handler.

**Function signature**:
```php
admin_audit_trail_form_submit(&$form, FormStateInterface $form_state)
```

**Parameters**:
- `$form` (array) - The form array
- `$form_state` (FormStateInterface) - The form state object

**Return value**: None (void)

**Behavior**:
1. Prevents duplicate logging for forms submitted multiple times
2. Identifies the form ID
3. Matches against registered handlers
4. Calls appropriate handler callbacks

**Notes**:
- Internal function - typically not called directly
- Attached to forms via `hook_form_alter()`
- Prevents duplicate logs using form state flag

---

## Hooks

### hook_admin_audit_trail_handlers()

Define event handlers for tracking specific events.

**Function signature**:
```php
function hook_admin_audit_trail_handlers()
```

**Return value**: Array of event handlers

**Return structure**:
```php
return [
  'handler_id' => [
    'title' => t('Handler Title'),

    // Match exact form IDs
    'form_ids' => [
      'node_article_form',
      'node_page_form',
    ],

    // OR match form IDs with regular expressions
    'form_ids_regexp' => [
      '/^node_.*_form$/',
      '/^user_.*_form$/',
    ],

    // Callback function to execute
    'form_submit_callback' => 'my_module_log_callback',
  ],
];
```

**Example implementation**:
```php
/**
 * Implements hook_admin_audit_trail_handlers().
 */
function my_module_admin_audit_trail_handlers() {
  $handlers = [];

  // Track contact form submissions.
  $handlers['contact_forms'] = [
    'title' => t('Contact Form Submissions'),
    'form_ids' => [
      'contact_message_feedback_form',
      'contact_message_personal_form',
    ],
    'form_submit_callback' => 'my_module_log_contact_submission',
  ];

  // Track all webform submissions using regex.
  $handlers['webforms'] = [
    'title' => t('Webform Submissions'),
    'form_ids_regexp' => ['/^webform_submission_.*_form$/'],
    'form_submit_callback' => 'my_module_log_webform_submission',
  ];

  return $handlers;
}

/**
 * Callback for contact form submissions.
 */
function my_module_log_contact_submission($form, FormStateInterface $form_state) {
  $log = [
    'type' => 'contact_form',
    'operation' => 'submit',
    'description' => t('Contact form submitted: @subject', [
      '@subject' => $form_state->getValue('subject'),
    ]),
  ];

  admin_audit_trail_insert($log);
}
```

**Handler callback signature**:
```php
function callback_name(&$form, FormStateInterface $form_state)
```

---

### hook_admin_audit_trail_handlers_alter()

Modify event handlers defined by other modules.

**Function signature**:
```php
function hook_admin_audit_trail_handlers_alter(array &$handlers)
```

**Parameters**:
- `$handlers` (array) - Array of event handlers

**Return value**: None (void)

**Example implementation**:
```php
/**
 * Implements hook_admin_audit_trail_handlers_alter().
 */
function my_module_admin_audit_trail_handlers_alter(array &$handlers) {
  // Disable a handler from another module.
  unset($handlers['unwanted_handler']);

  // Modify an existing handler.
  if (isset($handlers['node_forms'])) {
    $handlers['node_forms']['title'] = t('Modified Title');

    // Add additional form IDs.
    $handlers['node_forms']['form_ids'][] = 'custom_node_form';
  }

  // Replace a callback.
  if (isset($handlers['user_forms'])) {
    $handlers['user_forms']['form_submit_callback'] = 'my_module_custom_callback';
  }
}
```

---

### hook_admin_audit_trail_log_alter()

Alter log entries before they are saved to the database.

**Function signature**:
```php
function hook_admin_audit_trail_log_alter(array &$log)
```

**Parameters**:
- `$log` (array) - The log record to be altered

**Return value**: None (void)

**Example implementation**:
```php
/**
 * Implements hook_admin_audit_trail_log_alter().
 */
function my_module_admin_audit_trail_log_alter(array &$log) {
  // Add custom reference for node events.
  if ($log['type'] === 'node' && !empty($log['ref_numeric'])) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($log['ref_numeric']);

    if ($node && $node->hasField('field_department')) {
      $log['ref_char'] = 'dept_' . $node->get('field_department')->target_id;
    }
  }

  // Sanitize sensitive information.
  if (stripos($log['description'], 'password') !== FALSE) {
    $log['description'] = preg_replace(
      '/password[:\s]+\S+/i',
      'password: [REDACTED]',
      $log['description']
    );
  }

  // Add contextual metadata.
  if ($log['type'] === 'commerce_order') {
    $order = \Drupal::entityTypeManager()
      ->getStorage('commerce_order')
      ->load($log['ref_numeric']);

    if ($order) {
      $log['description'] .= ' [Total: ' . $order->getTotalPrice()->getNumber() . ']';
    }
  }

  // Prevent logging of specific events.
  // Note: Set $log to empty array to prevent logging.
  if ($log['type'] === 'ignored_type') {
    $log = [];
  }
}
```

**Use cases**:
- Add additional reference data
- Sanitize sensitive information
- Enhance descriptions with context
- Prevent specific events from being logged
- Add custom fields (requires schema modification)

---

## Entity Hooks for Tracking

### hook_entity_insert()

Track entity creation events.

**Example**:
```php
/**
 * Implements hook_entity_insert().
 */
function my_module_entity_insert(EntityInterface $entity) {
  // Track specific entity types.
  if ($entity->getEntityTypeId() === 'my_custom_entity') {
    $log = [
      'type' => $entity->getEntityTypeId(),
      'operation' => 'insert',
      'description' => t('Created @type: @label', [
        '@type' => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]),
      'ref_numeric' => $entity->id(),
    ];

    admin_audit_trail_insert($log);
  }
}
```

---

### hook_entity_update()

Track entity update events.

**Example**:
```php
/**
 * Implements hook_entity_update().
 */
function my_module_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'node') {
    // Detect field changes.
    $changed_fields = [];
    if (isset($entity->original)) {
      foreach ($entity->getFields() as $field_name => $field) {
        if ($entity->get($field_name)->getValue() !== $entity->original->get($field_name)->getValue()) {
          $changed_fields[] = $field_name;
        }
      }
    }

    $log = [
      'type' => 'node',
      'operation' => 'update',
      'description' => t('Updated @bundle: @title - Changed: @fields', [
        '@bundle' => $entity->bundle(),
        '@title' => $entity->label(),
        '@fields' => implode(', ', $changed_fields),
      ]),
      'ref_numeric' => $entity->id(),
      'ref_char' => $entity->bundle(),
    ];

    admin_audit_trail_insert($log);
  }
}
```

---

### hook_entity_delete()

Track entity deletion events.

**Example**:
```php
/**
 * Implements hook_entity_delete().
 */
function my_module_entity_delete(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') {
    $log = [
      'type' => 'taxonomy_term',
      'operation' => 'delete',
      'description' => t('Deleted taxonomy term: @name (ID: @id)', [
        '@name' => $entity->label(),
        '@id' => $entity->id(),
      ]),
      'ref_numeric' => $entity->id(),
      'ref_char' => $entity->bundle(),
    ];

    admin_audit_trail_insert($log);
  }
}
```

---

## Database Query Examples

### Retrieve Recent Logs

```php
$database = \Drupal::database();

$logs = $database->select('admin_audit_trail', 'a')
  ->fields('a')
  ->orderBy('created', 'DESC')
  ->range(0, 50)
  ->execute()
  ->fetchAll();
```

### Filter by Type and Operation

```php
$logs = $database->select('admin_audit_trail', 'a')
  ->fields('a')
  ->condition('type', 'node')
  ->condition('operation', 'delete')
  ->orderBy('created', 'DESC')
  ->execute()
  ->fetchAll();
```

### Filter by Date Range

```php
$start_date = strtotime('2024-01-01');
$end_date = strtotime('2024-01-31 23:59:59');

$logs = $database->select('admin_audit_trail', 'a')
  ->fields('a')
  ->condition('created', $start_date, '>=')
  ->condition('created', $end_date, '<=')
  ->orderBy('created', 'DESC')
  ->execute()
  ->fetchAll();
```

### Filter by User

```php
$logs = $database->select('admin_audit_trail', 'a')
  ->fields('a')
  ->condition('uid', 3)
  ->orderBy('created', 'DESC')
  ->execute()
  ->fetchAll();
```

### Join with User Table

```php
$query = $database->select('admin_audit_trail', 'a');
$query->leftJoin('users_field_data', 'u', 'a.uid = u.uid');
$query->fields('a');
$query->addField('u', 'name', 'username');
$query->orderBy('a.created', 'DESC');

$logs = $query->execute()->fetchAll();
```

### Count Logs by Type

```php
$counts = $database->select('admin_audit_trail', 'a')
  ->fields('a', ['type'])
  ->groupBy('type')
  ->addExpression('COUNT(*)', 'count')
  ->orderBy('count', 'DESC')
  ->execute()
  ->fetchAllKeyed();
```

---

## Services

### Accessing via Dependency Injection

```php
namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyController extends ControllerBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function viewLogs() {
    $logs = $this->database->select('admin_audit_trail', 'a')
      ->fields('a')
      ->orderBy('created', 'DESC')
      ->range(0, 20)
      ->execute()
      ->fetchAll();

    return [
      '#theme' => 'my_logs_template',
      '#logs' => $logs,
    ];
  }
}
```

---

## Configuration API

### Get Configuration

```php
$config = \Drupal::config('admin_audit_trail.settings');
$filter_expanded = $config->get('filter_expanded');
$row_limit = $config->get('admin_audit_trail_row_limit');
```

### Set Configuration

```php
$config = \Drupal::configFactory()->getEditable('admin_audit_trail.settings');
$config->set('filter_expanded', TRUE);
$config->set('admin_audit_trail_row_limit', 10000);
$config->save();
```

---

## Constants and Defaults

### Default Configuration

```php
'filter_expanded' => FALSE        // Filters collapsed by default
'admin_audit_trail_row_limit' => 0  // Unlimited retention
```

### Common Operation Types

- `insert` - Entity created
- `update` - Entity updated
- `delete` - Entity deleted
- `login` - User logged in
- `logout` - User logged out
- `login_failed` - Failed login attempt
- `password_reset` - Password reset requested
- `state_change` - Workflow state changed
- `role_add` - Role added to user
- `role_remove` - Role removed from user

---

## Next Steps

- [Review database schema](2-database-schema.md)
- [Learn about testing](3-testing.md)
- [See custom handler examples](0-custom-handlers.md)
