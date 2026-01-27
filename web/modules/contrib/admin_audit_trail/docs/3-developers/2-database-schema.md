# Database Schema

Complete reference for the Admin Audit Trail database schema.

## Overview

Admin Audit Trail stores all log entries in a single database table: `admin_audit_trail`.

**Key characteristics**:
- Single table design for simplicity
- Indexed for performance
- Supports both numeric and character references
- Stores timestamps as Unix timestamps
- Text field for descriptions (supports HTML)

## Table Structure

### admin_audit_trail

**Table name**: `admin_audit_trail`

**Description**: Logged events by the admin_audit_trail module

**Storage engine**: InnoDB (default for Drupal)

**Character set**: utf8mb4 (supports full Unicode)

### Schema Definition

```php
$schema['admin_audit_trail'] = [
  'description' => 'Logged events by the admin_audit_trail module.',
  'fields' => [
    'lid' => [
      'description' => 'Log id.',
      'type' => 'serial',
      'not null' => TRUE,
    ],
    'type' => [
      'description' => 'Event handler type.',
      'type' => 'varchar',
      'length' => '50',
      'not null' => TRUE,
    ],
    'operation' => [
      'description' => 'The operation performed.',
      'type' => 'varchar',
      'length' => '50',
      'not null' => TRUE,
    ],
    'path' => [
      'type' => 'varchar',
      'length' => '255',
      'not null' => TRUE,
      'default' => '',
      'description' => 'Current path.',
    ],
    'ref_numeric' => [
      'description' => 'A numeric value that can be used to reference an object.',
      'type' => 'int',
      'not null' => FALSE,
    ],
    'ref_char' => [
      'description' => 'A character value that can be used to reference an object.',
      'type' => 'varchar',
      'length' => '255',
      'not null' => FALSE,
    ],
    'description' => [
      'description' => 'Description of the event, in HTML.',
      'type' => 'text',
      'size' => 'medium',
      'not null' => TRUE,
    ],
    'uid' => [
      'description' => 'User id that triggered this event (0 = anonymous user).',
      'type' => 'int',
      'not null' => TRUE,
    ],
    'ip' => [
      'description' => 'IP address of the visitor that triggered this event.',
      'type' => 'varchar',
      'length' => '255',
      'not null' => FALSE,
    ],
    'created' => [
      'description' => 'The event timestamp.',
      'type' => 'int',
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['lid'],
  'indexes' => [
    'created' => ['created'],
    'user' => ['uid', 'ip'],
    'ip' => ['ip'],
    'join' => ['type', 'operation', 'ref_numeric', 'ref_char'],
  ],
];
```

## Field Definitions

### lid (Primary Key)

**Type**: Serial (Auto-increment integer)
**NULL**: NOT NULL
**Description**: Unique log entry identifier

**Characteristics**:
- Auto-incremented on insert
- Primary key for the table
- Uniquely identifies each log entry
- Never reused even after deletion

**Example values**: 1, 2, 3, 4, 5...

**SQL Type**: `INT AUTO_INCREMENT`

---

### type

**Type**: VARCHAR(50)
**NULL**: NOT NULL
**Description**: Event handler type (usually the entity type)

**Purpose**: Categorizes the log entry by entity or event type

**Common values**:
- `node` - Content entities
- `user` - User accounts
- `taxonomy_term` - Taxonomy terms
- `media` - Media entities
- `file` - File entities
- `menu_link_content` - Menu links
- `comment` - Comments
- `workflow` - Workflow transitions
- `custom_form` - Custom form submissions

**Indexed**: Yes (as part of `join` composite index)

**Max length**: 50 characters

**Example SQL**:
```sql
SELECT DISTINCT type FROM admin_audit_trail;
```

---

### operation

**Type**: VARCHAR(50)
**NULL**: NOT NULL
**Description**: The operation/action performed

**Purpose**: Describes what action was taken

**Common values**:
- `insert` - Entity created
- `update` - Entity updated
- `delete` - Entity deleted
- `login` - User logged in
- `logout` - User logged out
- `login_failed` - Failed login attempt
- `password_reset` - Password reset requested
- `state_change` - Workflow state transition
- `role_add` - Role added to user
- `role_remove` - Role removed from user

**Indexed**: Yes (as part of `join` composite index)

**Max length**: 50 characters

**Example SQL**:
```sql
SELECT operation, COUNT(*) as count
FROM admin_audit_trail
GROUP BY operation
ORDER BY count DESC;
```

---

### path

**Type**: VARCHAR(255)
**NULL**: NOT NULL
**Default**: '' (empty string)
**Description**: Current path where the event occurred

**Purpose**: Records the URL path where the action was performed

**Example values**:
- `/node/123/edit`
- `/user/5/edit`
- `/admin/structure/menu/manage/main/add`
- `/taxonomy/term/10/edit`

**Max length**: 255 characters

**Use cases**:
- Determine where actions were performed
- Identify problematic pages
- Track administrative interface usage

---

### ref_numeric

**Type**: INT
**NULL**: NULL (optional)
**Description**: Numeric reference value (typically entity ID)

**Purpose**: Links the log entry to a specific entity by ID

**Common uses**:
- Node ID (nid)
- User ID (uid)
- Term ID (tid)
- Media ID (mid)
- Any numeric entity identifier

**Indexed**: Yes (as part of `join` composite index)

**Example values**: 123, 456, 789

**SQL Type**: `INT(11)` (signed integer)

**Example queries**:
```sql
-- Find all logs for node ID 123
SELECT * FROM admin_audit_trail
WHERE type = 'node' AND ref_numeric = 123
ORDER BY created DESC;

-- Find most frequently modified entities
SELECT ref_numeric, COUNT(*) as changes
FROM admin_audit_trail
WHERE type = 'node' AND operation = 'update'
GROUP BY ref_numeric
ORDER BY changes DESC
LIMIT 10;
```

---

### ref_char

**Type**: VARCHAR(255)
**NULL**: NULL (optional)
**Description**: Character reference value

**Purpose**: Provides additional context or alternative reference

**Common uses**:
- Entity bundle name (e.g., 'article', 'page')
- Machine names (e.g., 'main-menu')
- UUIDs
- String identifiers
- Custom categorization

**Indexed**: Yes (as part of `join` composite index)

**Max length**: 255 characters

**Example values**:
- `article`
- `page`
- `main`
- `dept_5`
- `product_electronics`

**Example queries**:
```sql
-- Count logs by bundle
SELECT ref_char as bundle, COUNT(*) as count
FROM admin_audit_trail
WHERE type = 'node'
GROUP BY ref_char
ORDER BY count DESC;
```

---

### description

**Type**: TEXT (MEDIUMTEXT)
**NULL**: NOT NULL
**Description**: Human-readable description of the event

**Purpose**: Provides detailed, human-readable information about what happened

**Characteristics**:
- Supports HTML content
- Medium text size (16MB max)
- Should be translatable (use `t()` function)
- May contain variable substitutions

**Max size**: 16,777,215 characters (16MB)

**Example values**:
```
Created article "How to Install Drupal"
Updated user john.doe - Changed email to john@example.com
Deleted taxonomy term "Obsolete Category"
User admin logged in successfully
Failed login attempt for user: admin
```

**Best practices**:
- Use `t()` for translation support
- Include key details (entity label, changed fields)
- Be concise but informative
- Avoid sensitive data (passwords, API keys)

---

### uid

**Type**: INT
**NULL**: NOT NULL
**Description**: User ID that triggered the event

**Purpose**: Identifies which user performed the action

**Special values**:
- `0` - Anonymous user
- `1` - Admin/root user
- `>1` - Regular users

**Indexed**: Yes (as part of `user` composite index)

**Foreign key**: Links to `users.uid` (not enforced at database level)

**Auto-filled**: Yes (current user if not provided)

**Example queries**:
```sql
-- Find all actions by user ID 3
SELECT * FROM admin_audit_trail
WHERE uid = 3
ORDER BY created DESC;

-- Count actions per user
SELECT u.name, COUNT(*) as actions
FROM admin_audit_trail a
LEFT JOIN users_field_data u ON a.uid = u.uid
GROUP BY a.uid, u.name
ORDER BY actions DESC
LIMIT 10;

-- Find anonymous user actions
SELECT * FROM admin_audit_trail
WHERE uid = 0;
```

---

### ip

**Type**: VARCHAR(255)
**NULL**: NULL (optional)
**Description**: IP address of the visitor

**Purpose**: Records the client IP address for security tracking

**Supports**:
- IPv4 addresses (e.g., `192.168.1.1`)
- IPv6 addresses (e.g., `2001:0db8:85a3:0000:0000:8a2e:0370:7334`)
- Proxy headers (X-Forwarded-For)

**Indexed**: Yes (both standalone and as part of `user` composite index)

**Max length**: 255 characters

**Auto-filled**: Yes (from request)

**Privacy note**: May be considered PII under GDPR/privacy laws

**Example queries**:
```sql
-- Find actions from specific IP
SELECT * FROM admin_audit_trail
WHERE ip = '192.168.1.50'
ORDER BY created DESC;

-- Find users with multiple IPs (possible security concern)
SELECT uid, COUNT(DISTINCT ip) as ip_count
FROM admin_audit_trail
GROUP BY uid
HAVING ip_count > 5
ORDER BY ip_count DESC;

-- Find unusual IP patterns
SELECT ip, COUNT(*) as attempts
FROM admin_audit_trail
WHERE operation = 'login_failed'
GROUP BY ip
HAVING attempts > 10
ORDER BY attempts DESC;
```

---

### created

**Type**: INT
**NULL**: NOT NULL
**Description**: Unix timestamp when the event occurred

**Purpose**: Records when the action was performed

**Format**: Unix timestamp (seconds since 1970-01-01 00:00:00 UTC)

**Indexed**: Yes (standalone index)

**Auto-filled**: Yes (current time if not provided)

**Example values**: 1705324800 (2024-01-15 12:00:00 UTC)

**Example queries**:
```sql
-- Recent logs (last 24 hours)
SELECT * FROM admin_audit_trail
WHERE created > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))
ORDER BY created DESC;

-- Logs from specific date
SELECT * FROM admin_audit_trail
WHERE created BETWEEN UNIX_TIMESTAMP('2024-01-01') AND UNIX_TIMESTAMP('2024-01-31 23:59:59')
ORDER BY created DESC;

-- Logs per day (last 30 days)
SELECT DATE(FROM_UNIXTIME(created)) as date, COUNT(*) as count
FROM admin_audit_trail
WHERE created > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
GROUP BY date
ORDER BY date DESC;

-- Convert to human-readable
SELECT lid, FROM_UNIXTIME(created) as datetime, description
FROM admin_audit_trail
ORDER BY created DESC
LIMIT 10;
```

## Indexes

### Primary Key: lid

**Type**: Primary Key
**Fields**: `lid`

**Purpose**: Unique identifier for each log entry

**Characteristics**:
- Automatically indexed
- Ensures uniqueness
- Used for direct lookups

---

### Index: created

**Type**: Single-column index
**Fields**: `created`

**Purpose**: Optimize date-based queries

**Benefits**:
- Fast filtering by date ranges
- Efficient ORDER BY created
- Quick recent log retrieval

**Optimized queries**:
```sql
WHERE created > X
WHERE created BETWEEN X AND Y
ORDER BY created DESC
```

---

### Index: user

**Type**: Composite index
**Fields**: `uid`, `ip`

**Purpose**: Optimize user-based and IP-based queries

**Benefits**:
- Fast user activity lookups
- Efficient IP-based filtering
- Combined user+IP queries

**Optimized queries**:
```sql
WHERE uid = X
WHERE ip = 'X.X.X.X'
WHERE uid = X AND ip = 'X.X.X.X'
```

---

### Index: ip

**Type**: Single-column index
**Fields**: `ip`

**Purpose**: Optimize IP address lookups

**Benefits**:
- Security monitoring
- Brute force detection
- Geographic analysis

**Optimized queries**:
```sql
WHERE ip = 'X.X.X.X'
GROUP BY ip
```

---

### Index: join

**Type**: Composite index
**Fields**: `type`, `operation`, `ref_numeric`, `ref_char`

**Purpose**: Optimize filtered queries and joins

**Benefits**:
- Fast type+operation filtering
- Entity reference lookups
- Complex filtering queries

**Optimized queries**:
```sql
WHERE type = 'node' AND operation = 'delete'
WHERE type = 'node' AND ref_numeric = 123
WHERE type = 'node' AND operation = 'update' AND ref_char = 'article'
```

## Example Queries

### Basic Queries

**Get all logs**:
```sql
SELECT * FROM admin_audit_trail
ORDER BY created DESC
LIMIT 100;
```

**Count total logs**:
```sql
SELECT COUNT(*) FROM admin_audit_trail;
```

**Table size**:
```sql
SELECT
  table_name,
  table_rows,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_name = 'admin_audit_trail';
```

### Advanced Queries

**Logs with user information**:
```sql
SELECT
  a.lid,
  a.type,
  a.operation,
  a.description,
  u.name as username,
  u.mail as email,
  FROM_UNIXTIME(a.created) as datetime,
  a.ip
FROM admin_audit_trail a
LEFT JOIN users_field_data u ON a.uid = u.uid
ORDER BY a.created DESC
LIMIT 50;
```

**Daily activity summary**:
```sql
SELECT
  DATE(FROM_UNIXTIME(created)) as date,
  COUNT(*) as total_events,
  COUNT(DISTINCT uid) as unique_users,
  COUNT(DISTINCT ip) as unique_ips
FROM admin_audit_trail
WHERE created > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
GROUP BY date
ORDER BY date DESC;
```

**Most active users**:
```sql
SELECT
  u.name,
  COUNT(*) as actions,
  MIN(FROM_UNIXTIME(a.created)) as first_action,
  MAX(FROM_UNIXTIME(a.created)) as last_action
FROM admin_audit_trail a
LEFT JOIN users_field_data u ON a.uid = u.uid
GROUP BY a.uid, u.name
ORDER BY actions DESC
LIMIT 20;
```

## Maintenance

### Cleanup Old Logs

```sql
-- Delete logs older than 90 days
DELETE FROM admin_audit_trail
WHERE created < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY));
```

### Optimize Table

```sql
OPTIMIZE TABLE admin_audit_trail;
```

### Analyze Table

```sql
ANALYZE TABLE admin_audit_trail;
```

## Next Steps

- [Review API functions](1-api-reference.md)
- [Learn about testing](3-testing.md)
- [Create custom handlers](0-custom-handlers.md)
