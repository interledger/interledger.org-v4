# Testing

Guide for testing Admin Audit Trail implementations and custom event handlers.

## Overview

Testing audit trail functionality ensures:
- Events are logged correctly
- Log entries contain accurate information
- Custom handlers work as expected
- Database integrity is maintained
- Performance remains acceptable

## Testing Approaches

### 1. Manual Testing
### 2. Automated Unit Tests
### 3. Kernel Tests
### 4. Functional Tests
### 5. Performance Testing

## Manual Testing

### Basic Verification

**Test that logging works**:

1. Enable a sub-module (e.g., `admin_audit_trail_node`)
2. Perform a tracked action (create a node)
3. Visit `/admin/reports/audit-trail`
4. Verify log entry appears

**Expected result**:
```
Type: node
Operation: insert
Description: Created article "Test Article"
User: admin
```

### Testing Custom Handlers

**Test custom event handler**:

1. Enable your custom module
2. Clear cache: `drush cr`
3. Trigger the event (submit form, create entity, etc.)
4. Check audit trail for log entry
5. Verify all fields are correct

**Verification checklist**:
- [ ] Log entry created
- [ ] Correct type
- [ ] Correct operation
- [ ] Accurate description
- [ ] Proper references (ref_numeric, ref_char)
- [ ] Correct user ID
- [ ] Valid timestamp
- [ ] IP address captured

### Testing with Drush

```bash
# View recent logs
drush sql:query "SELECT * FROM admin_audit_trail ORDER BY created DESC LIMIT 10"

# Count logs by type
drush sql:query "SELECT type, COUNT(*) as count FROM admin_audit_trail GROUP BY type"

# Check specific log entry
drush sql:query "SELECT * FROM admin_audit_trail WHERE lid = 123"
```

## Automated Testing

### Setting Up Test Environment

**phpunit.xml configuration**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
  <testsuites>
    <testsuite name="my_audit_module">
      <directory>./tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

### Unit Tests

Unit tests verify individual functions and methods.

**Example: Test log insertion**

**tests/src/Unit/AuditTrailTest.php**:
```php
<?php

namespace Drupal\Tests\my_audit_module\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests audit trail logging functions.
 *
 * @group my_audit_module
 */
class AuditTrailTest extends UnitTestCase {

  /**
   * Test log array structure.
   */
  public function testLogArrayStructure() {
    $log = [
      'type' => 'test_entity',
      'operation' => 'insert',
      'description' => 'Test description',
      'ref_numeric' => 123,
    ];

    $this->assertEquals('test_entity', $log['type']);
    $this->assertEquals('insert', $log['operation']);
    $this->assertArrayHasKey('description', $log);
  }

  /**
   * Test description formatting.
   */
  public function testDescriptionFormatting() {
    $title = 'Test Article';
    $description = t('Created article "@title"', ['@title' => $title]);

    $this->assertStringContainsString('Test Article', $description);
    $this->assertStringContainsString('Created article', $description);
  }
}
```

**Run unit tests**:
```bash
phpunit --group my_audit_module
```

### Kernel Tests

Kernel tests have access to Drupal's database and services.

**Example: Test log insertion to database**

**tests/src/Kernel/AuditTrailKernelTest.php**:
```php
<?php

namespace Drupal\Tests\my_audit_module\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for audit trail logging.
 *
 * @group my_audit_module
 */
class AuditTrailKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'admin_audit_trail',
    'my_audit_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install database schema.
    $this->installSchema('admin_audit_trail', ['admin_audit_trail']);
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test that logs are inserted correctly.
   */
  public function testLogInsertion() {
    // Create a test log entry.
    $log = [
      'type' => 'test_type',
      'operation' => 'test_operation',
      'description' => 'Test description',
      'ref_numeric' => 999,
      'ref_char' => 'test_ref',
    ];

    // Insert the log.
    admin_audit_trail_insert($log);

    // Verify it was saved.
    $saved_logs = \Drupal::database()
      ->select('admin_audit_trail', 'a')
      ->fields('a')
      ->condition('type', 'test_type')
      ->execute()
      ->fetchAll();

    $this->assertCount(1, $saved_logs);
    $this->assertEquals('test_operation', $saved_logs[0]->operation);
    $this->assertEquals('Test description', $saved_logs[0]->description);
    $this->assertEquals(999, $saved_logs[0]->ref_numeric);
    $this->assertEquals('test_ref', $saved_logs[0]->ref_char);
  }

  /**
   * Test that user ID is auto-filled.
   */
  public function testAutoFillUserId() {
    $log = [
      'type' => 'test',
      'operation' => 'insert',
      'description' => 'Test',
    ];

    admin_audit_trail_insert($log);

    $saved_log = \Drupal::database()
      ->select('admin_audit_trail', 'a')
      ->fields('a')
      ->orderBy('lid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();

    // Should have a user ID (even if 0 for anonymous).
    $this->assertNotNull($saved_log->uid);
  }

  /**
   * Test that timestamp is auto-filled.
   */
  public function testAutoFillTimestamp() {
    $before = \Drupal::time()->getRequestTime();

    $log = [
      'type' => 'test',
      'operation' => 'insert',
      'description' => 'Test',
    ];

    admin_audit_trail_insert($log);

    $after = \Drupal::time()->getRequestTime();

    $saved_log = \Drupal::database()
      ->select('admin_audit_trail', 'a')
      ->fields('a')
      ->orderBy('lid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();

    $this->assertGreaterThanOrEqual($before, $saved_log->created);
    $this->assertLessThanOrEqual($after, $saved_log->created);
  }

  /**
   * Test hook_admin_audit_trail_log_alter().
   */
  public function testLogAlterHook() {
    // Assuming you have an alter hook implementation to test.
    $log = [
      'type' => 'test',
      'operation' => 'insert',
      'description' => 'Test password: secret123',
    ];

    admin_audit_trail_insert($log);

    $saved_log = \Drupal::database()
      ->select('admin_audit_trail', 'a')
      ->fields('a')
      ->orderBy('lid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();

    // If your alter hook sanitizes passwords, verify it worked.
    $this->assertStringNotContainsString('secret123', $saved_log->description);
  }
}
```

**Run kernel tests**:
```bash
phpunit --group my_audit_module tests/src/Kernel/
```

### Functional Tests

Functional tests simulate user interactions via the browser.

**Example: Test audit trail page access**

**tests/src/Functional/AuditTrailFunctionalTest.php**:
```php
<?php

namespace Drupal\Tests\my_audit_module\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for audit trail.
 *
 * @group my_audit_module
 */
class AuditTrailFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_audit_trail',
    'admin_audit_trail_node',
    'node',
  ];

  /**
   * Test that audit trail page is accessible.
   */
  public function testAuditTrailPageAccess() {
    // Create a user with permission.
    $user = $this->drupalCreateUser([
      'access admin audit trail',
    ]);

    $this->drupalLogin($user);

    // Visit the audit trail page.
    $this->drupalGet('/admin/reports/audit-trail');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Audit Trail');
  }

  /**
   * Test that users without permission are denied access.
   */
  public function testAuditTrailPageAccessDenied() {
    // Create a user without permission.
    $user = $this->drupalCreateUser([]);

    $this->drupalLogin($user);

    // Try to visit the audit trail page.
    $this->drupalGet('/admin/reports/audit-trail');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that node creation is logged.
   */
  public function testNodeCreationLogged() {
    // Create a user with node creation and audit trail access.
    $user = $this->drupalCreateUser([
      'create article content',
      'access admin audit trail',
    ]);

    $this->drupalLogin($user);

    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article for Audit',
    ]);

    // Visit audit trail.
    $this->drupalGet('/admin/reports/audit-trail');

    // Check that the log entry appears.
    $this->assertSession()->pageTextContains('Test Article for Audit');
    $this->assertSession()->pageTextContains('node');
    $this->assertSession()->pageTextContains('insert');
  }

  /**
   * Test filtering by type.
   */
  public function testAuditTrailFiltering() {
    $user = $this->drupalCreateUser([
      'access admin audit trail',
      'create article content',
    ]);

    $this->drupalLogin($user);

    // Create some test data.
    $this->drupalCreateNode(['type' => 'article', 'title' => 'Article 1']);
    $this->drupalCreateNode(['type' => 'article', 'title' => 'Article 2']);

    // Visit audit trail and apply filter.
    $this->drupalGet('/admin/reports/audit-trail');

    // Submit filter form (adjust selectors based on your actual form).
    $this->submitForm([
      'type' => 'node',
    ], 'Apply');

    // Verify filtered results.
    $this->assertSession()->pageTextContains('Article 1');
    $this->assertSession()->pageTextContains('Article 2');
  }
}
```

**Run functional tests**:
```bash
phpunit --group my_audit_module tests/src/Functional/
```

## Testing Custom Event Handlers

### Test Form Handler Registration

**Test that handlers are registered**:
```php
public function testHandlerRegistration() {
  $handlers = admin_audit_trail_get_event_handlers();

  $this->assertArrayHasKey('my_custom_handler', $handlers);
  $this->assertEquals('my_module_callback', $handlers['my_custom_handler']['form_submit_callback']);
}
```

### Test Form Submission Logging

**Test that form submission creates log entry**:
```php
public function testFormSubmissionLogging() {
  // Create test user.
  $user = $this->drupalCreateUser(['access content']);
  $this->drupalLogin($user);

  // Get initial log count.
  $initial_count = \Drupal::database()
    ->select('admin_audit_trail', 'a')
    ->countQuery()
    ->execute()
    ->fetchField();

  // Submit the form.
  $this->drupalGet('/my-custom-form');
  $this->submitForm([
    'field_name' => 'test value',
  ], 'Submit');

  // Verify log entry created.
  $final_count = \Drupal::database()
    ->select('admin_audit_trail', 'a')
    ->countQuery()
    ->execute()
    ->fetchField();

  $this->assertEquals($initial_count + 1, $final_count);

  // Verify log content.
  $log = \Drupal::database()
    ->select('admin_audit_trail', 'a')
    ->fields('a')
    ->orderBy('lid', 'DESC')
    ->range(0, 1)
    ->execute()
    ->fetch();

  $this->assertEquals('custom_form', $log->type);
  $this->assertEquals('submit', $log->operation);
  $this->assertStringContainsString('test value', $log->description);
}
```

### Test Entity Hook Logging

**Test entity insert logging**:
```php
public function testEntityInsertLogging() {
  $this->installEntitySchema('node');
  $this->installConfig(['node', 'field']);

  // Create a node.
  $node = \Drupal\node\Entity\Node::create([
    'type' => 'article',
    'title' => 'Test Node',
  ]);
  $node->save();

  // Verify log entry.
  $logs = \Drupal::database()
    ->select('admin_audit_trail', 'a')
    ->fields('a')
    ->condition('type', 'node')
    ->condition('operation', 'insert')
    ->execute()
    ->fetchAll();

  $this->assertCount(1, $logs);
  $this->assertStringContainsString('Test Node', $logs[0]->description);
  $this->assertEquals($node->id(), $logs[0]->ref_numeric);
}
```

## Performance Testing

### Measure Insert Performance

**Benchmark log insertion**:
```php
public function testLogInsertionPerformance() {
  $start_time = microtime(TRUE);

  // Insert 100 logs.
  for ($i = 0; $i < 100; $i++) {
    $log = [
      'type' => 'test',
      'operation' => 'insert',
      'description' => 'Performance test ' . $i,
      'ref_numeric' => $i,
    ];

    admin_audit_trail_insert($log);
  }

  $end_time = microtime(TRUE);
  $duration = $end_time - $start_time;

  // Should complete in reasonable time (adjust threshold as needed).
  $this->assertLessThan(5, $duration, 'Inserting 100 logs took too long');

  // Average time per insert.
  $avg_time = $duration / 100;
  $this->assertLessThan(0.05, $avg_time, 'Average insert time too high');
}
```

### Load Testing with Drush

**Create test data**:
```bash
#!/bin/bash
# Generate 10,000 test log entries

for i in {1..10000}; do
  drush php:eval "admin_audit_trail_insert([
    'type' => 'load_test',
    'operation' => 'insert',
    'description' => 'Load test entry $i',
    'ref_numeric' => $i,
  ]);"
done
```

**Measure query performance**:
```bash
# Time a query
time drush sql:query "SELECT * FROM admin_audit_trail WHERE type = 'load_test' ORDER BY created DESC LIMIT 100"
```

## Testing Checklist

### For Custom Modules

- [ ] Handler registration works
- [ ] Callbacks are called correctly
- [ ] Log entries created with correct data
- [ ] Required fields populated
- [ ] Optional fields handled properly
- [ ] Auto-fill fields work (uid, created, ip, path)
- [ ] Alter hooks respected
- [ ] No duplicate logs created
- [ ] Performance acceptable
- [ ] No SQL errors
- [ ] Works with multiple sub-modules
- [ ] CLI requests ignored (if appropriate)

### For Sub-modules

- [ ] Entity insert logged
- [ ] Entity update logged
- [ ] Entity delete logged
- [ ] Correct entity type
- [ ] Correct operations
- [ ] Accurate descriptions
- [ ] Reference IDs correct
- [ ] Works with all bundles
- [ ] Multilingual support (if applicable)
- [ ] No conflicts with other modules

## Debugging Tests

### Enable Verbose Output

```bash
phpunit --verbose --debug tests/src/Kernel/AuditTrailKernelTest.php
```

### Print Database Contents

```php
public function testWithDebug() {
  // ... test code ...

  // Print all logs for debugging.
  $logs = \Drupal::database()
    ->select('admin_audit_trail', 'a')
    ->fields('a')
    ->execute()
    ->fetchAll();

  foreach ($logs as $log) {
    print_r($log);
  }

  // Continue test...
}
```

### Use DDT (Drupal Debug Tools)

```php
// Install devel module
composer require drupal/devel --dev

// In tests:
ksm($log);  // Krumo output
dpm($logs);  // Devel print message
```

## Continuous Integration

### GitLab CI Example

**.gitlab-ci.yml**:
```yaml
test:
  script:
    - composer install
    - vendor/bin/phpunit --group my_audit_module
  only:
    - merge_requests
    - main
```

### GitHub Actions Example

**.github/workflows/test.yml**:
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit --group my_audit_module
```

## Next Steps

- [Review API reference](1-api-reference.md)
- [Understand database schema](2-database-schema.md)
- [Create custom handlers](0-custom-handlers.md)
- [Review best practices](../2-admins/3-performance.md)
