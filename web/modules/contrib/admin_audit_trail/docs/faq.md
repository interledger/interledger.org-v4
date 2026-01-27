# Frequently Asked Questions

Common questions and answers about Admin Audit Trail.

## General Questions

### What is Admin Audit Trail?

Admin Audit Trail is a Drupal module that automatically tracks and logs administrative actions and content changes on your website. It provides a comprehensive audit trail for accountability, security monitoring, and compliance requirements.

### Who should use Admin Audit Trail?

- **Compliance-driven organizations** (healthcare, finance, government)
- **Multi-editor websites** requiring accountability
- **Security-conscious sites** monitoring for unauthorized access
- **Teams** needing to track who changed what and when
- **Sites with editorial workflows** requiring approval tracking

### Is Admin Audit Trail free?

Yes, Admin Audit Trail is free and open-source software distributed under the GPL license.

---

## Installation and Setup

### How do I install Admin Audit Trail?

**Via Composer (recommended)**:
```bash
composer require drupal/admin_audit_trail
drush en admin_audit_trail
```

**Via Drush**:
```bash
drush en admin_audit_trail
```

See the [Installation Guide](1-users/0-installation.md) for detailed instructions.

### Do I need to enable sub-modules?

Yes! The base `admin_audit_trail` module provides the framework, but you must enable specific sub-modules to track different entity types:

- `admin_audit_trail_node` - Track content
- `admin_audit_trail_user` - Track user accounts
- `admin_audit_trail_auth` - Track logins/logouts

See [Sub-modules Guide](2-admins/2-submodules.md) for complete list.

### Which sub-modules should I enable?

It depends on your needs:

**Security monitoring**: Enable `admin_audit_trail_auth`, `admin_audit_trail_user`, `admin_audit_trail_user_roles`

**Content tracking**: Enable `admin_audit_trail_node`, `admin_audit_trail_media`, `admin_audit_trail_taxonomy`

**Compliance**: Enable all relevant sub-modules for your content types

See [Sub-modules Guide](2-admins/2-submodules.md) for recommendations.

### Can I enable all sub-modules at once?

Yes, but consider:
- **Performance impact** - More sub-modules = more logs
- **Database storage** - Logs consume disk space
- **Relevance** - Only enable what you actually need

Start with essential sub-modules and add more as needed.

---

## Configuration

### Where are the configuration settings?

Navigate to: **Administration > Configuration > Development > Admin Audit Trail Settings**

Or visit: `/admin/config/development/audit-trail/settings`

You need the "Configure Admin Audit Trail" permission.

### How long are logs kept?

By default, logs are kept **indefinitely** (unlimited retention).

You can configure automatic cleanup:
1. Go to Settings page
2. Set "Audit Trail log messages to keep"
3. Options: 100, 500, 1,000, 3,000, 10,000, 100,000, or unlimited

**Note**: Requires cron to run for automatic cleanup.

### How do I configure log retention?

1. Visit `/admin/config/development/audit-trail/settings`
2. Select desired retention limit
3. Save configuration
4. Ensure cron is running

See [Configuration Guide](2-admins/0-configuration.md) for details.

### Does the module work without cron?

Yes, logging works without cron. However:
- **Without cron**: Logs are never automatically deleted
- **With cron**: Old logs are cleaned up based on retention settings

---

## Using the Audit Trail

### Where do I view audit logs?

Navigate to: **Administration > Reports > Audit Trail**

Or visit: `/admin/reports/audit-trail`

You need the "Access Admin Audit Trail" permission.

### How do I filter logs?

1. Visit the audit trail page
2. Expand the "Filters" section (if collapsed)
3. Select filter criteria:
   - Type (node, user, etc.)
   - Operation (insert, update, delete)
   - User
   - Date range
4. Click "Apply" or "Filter"

See [Viewing Logs](1-users/1-viewing-logs.md) for details.

### Can I export audit logs?

The base module doesn't include export functionality, but you can:

1. **Use Views** - Create a View of the audit trail and export via Views Data Export
2. **Database export** - Export the `admin_audit_trail` table directly
3. **Custom module** - Create custom export functionality

### Can I delete audit logs?

Yes, but carefully:
- **Automatic deletion**: Configure retention limits
- **Manual deletion**: Delete from database (requires permissions)
- **Compliance warning**: Some regulations prohibit deleting audit logs

### What information is captured in each log entry?

Each log contains:
- **Type** - Entity or event type
- **Operation** - Action performed
- **Description** - Human-readable details
- **User** - Who performed the action
- **Timestamp** - When it occurred
- **IP Address** - Where it came from
- **Path** - URL where action was performed
- **Reference ID** - Related entity ID (if applicable)

See [Understanding Logs](1-users/2-understanding-logs.md) for complete reference.

---

## Permissions

### What permissions does the module provide?

Two permissions:
1. **Access Admin Audit Trail** - View audit logs
2. **Configure Admin Audit Trail** - Modify settings

### Who should have access to audit logs?

Grant "Access Admin Audit Trail" to:
- Site administrators
- Security officers
- Compliance auditors
- Managers (if appropriate)

**Do NOT grant to**:
- Anonymous users
- General content editors (unless required)

### Can users see their own activity?

Yes, if they have "Access Admin Audit Trail" permission, they can see all logs including their own actions. Logs cannot be filtered to hide self-activity.

---

## Performance and Storage

### Will this slow down my site?

Generally no, Admin Audit Trail has minimal performance impact:
- Logging happens after actions complete (not blocking)
- Database writes are fast
- No impact on front-end performance

High-traffic sites should monitor database growth.

### How much disk space do logs use?

Varies by site activity:
- **Low traffic**: ~10-50 MB per year
- **Medium traffic**: ~100-500 MB per year
- **High traffic**: ~1+ GB per year

Monitor with:
```bash
drush sql:query "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_name = 'admin_audit_trail'"
```

### How do I check database size?

```bash
# Via Drush
drush sql:query "SELECT table_name, table_rows, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_name = 'admin_audit_trail'"

# Via MySQL
SELECT COUNT(*) FROM admin_audit_trail;
```

### Can I archive old logs?

Yes! Export and delete old logs:

```bash
# Export logs older than 1 year
drush sql:query "SELECT * FROM admin_audit_trail WHERE created < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))" --result-file=archive.csv

# Verify export, then delete
drush sql:query "DELETE FROM admin_audit_trail WHERE created < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))"

# Optimize table
drush sql:query "OPTIMIZE TABLE admin_audit_trail"
```

See [Performance Guide](2-admins/3-performance.md) for details.

---

## Compliance and Security

### Is Admin Audit Trail HIPAA compliant?

Admin Audit Trail provides the logging infrastructure needed for HIPAA compliance, but compliance also requires:
- Proper server security
- Access controls
- Data encryption
- Staff training
- Documented procedures

Enable: `admin_audit_trail_auth`, `admin_audit_trail_user`, `admin_audit_trail_user_roles`, and all content-related sub-modules.

### Is it GDPR compliant?

Admin Audit Trail logs may contain personal data (usernames, IP addresses, email addresses in descriptions). For GDPR:
- **Right to access**: Provide user's audit logs upon request
- **Right to erasure**: May need to anonymize or delete logs
- **Data minimization**: Only enable necessary sub-modules
- **Retention limits**: Configure appropriate retention

Consult legal counsel for specific requirements.

### Can I anonymize user data in logs?

Not built-in, but you can:
1. **Use `hook_admin_audit_trail_log_alter()`** to sanitize data before saving
2. **Create custom script** to anonymize old logs
3. **Delete old logs** after retention period

Example:
```php
function my_module_admin_audit_trail_log_alter(array &$log) {
  // Remove IP addresses older than 90 days
  // (Implement via update hook or cron job)
}
```

### Are passwords logged?

No, Admin Audit Trail does NOT log passwords. It logs that a password was changed, but not the actual password value.

Always verify your custom implementations don't accidentally log sensitive data.

---

## Troubleshooting

### Logs aren't appearing

**Check**:
1. Is the relevant sub-module enabled?
   ```bash
   drush pml | grep admin_audit_trail
   ```
2. Clear cache:
   ```bash
   drush cr
   ```
3. Do you have permission to view logs?
4. Trigger a new event and check immediately
5. Check database directly:
   ```bash
   drush sql:query "SELECT * FROM admin_audit_trail ORDER BY created DESC LIMIT 5"
   ```

### Old logs aren't being deleted

**Check**:
1. Is retention limit set (not "All")?
2. Is cron running?
   ```bash
   drush core:cron
   ```
3. Check cron last run:
   - Visit `/admin/reports/status`
   - Look for "Cron maintenance tasks"

### Can't access audit trail page

**Check**:
1. Do you have "Access Admin Audit Trail" permission?
2. Are you logged in as admin or appropriate role?
3. Clear cache: `drush cr`
4. Try direct URL: `/admin/reports/audit-trail`

### Filters not working

**Clear cache**:
```bash
drush cr
```

Check filter criteria matches available data.

### Database errors

**Check**:
1. Is the `admin_audit_trail` table installed?
   ```bash
   drush sql:query "DESCRIBE admin_audit_trail"
   ```
2. Run database updates:
   ```bash
   drush updatedb
   ```
3. Check database permissions

---

## Development and Customization

### Can I track custom entity types?

Yes! Use entity hooks:

```php
function my_module_entity_insert(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'my_custom_entity') {
    $log = [
      'type' => 'my_custom_entity',
      'operation' => 'insert',
      'description' => t('Created @label', ['@label' => $entity->label()]),
      'ref_numeric' => $entity->id(),
    ];
    admin_audit_trail_insert($log);
  }
}
```

See [Custom Handlers](3-developers/0-custom-handlers.md) guide.

### Can I log custom events?

Yes! Call `admin_audit_trail_insert()` directly:

```php
$log = [
  'type' => 'custom_event',
  'operation' => 'api_call',
  'description' => t('API endpoint accessed: @endpoint', ['@endpoint' => $endpoint]),
];
admin_audit_trail_insert($log);
```

### How do I modify log entries before saving?

Implement `hook_admin_audit_trail_log_alter()`:

```php
function my_module_admin_audit_trail_log_alter(array &$log) {
  // Add custom data
  $log['ref_char'] = 'custom_value';

  // Sanitize sensitive information
  if (strpos($log['description'], 'password') !== FALSE) {
    $log['description'] = preg_replace('/password.*/', 'password: [REDACTED]', $log['description']);
  }
}
```

See [API Reference](3-developers/1-api-reference.md) for details.

### Is there an API I can use?

Yes! Main functions:
- `admin_audit_trail_insert($log)` - Insert a log entry
- `admin_audit_trail_get_event_handlers()` - Get registered handlers
- `hook_admin_audit_trail_handlers()` - Register custom handlers
- `hook_admin_audit_trail_log_alter()` - Modify logs before saving

See [API Reference](3-developers/1-api-reference.md) for complete documentation.

---

## Comparison with Other Modules

### Admin Audit Trail vs Logging and Alerts

**Admin Audit Trail**:
- Focused on CUD operations and user actions
- Dedicated audit trail interface
- Specialized sub-modules for entity types
- Designed for compliance and accountability

**Logging and Alerts**:
- General system logging (errors, warnings)
- Broader scope (not just admin actions)
- Watchdog integration
- More technical/debugging focus

Both modules can coexist and serve different purposes.

### Admin Audit Trail vs Content Moderation

**Admin Audit Trail**:
- Logs all actions (not just workflow)
- Read-only audit trail
- No approval process
- Compliance focused

**Content Moderation**:
- Manages editorial workflow
- Controls publication process
- Approval/rejection functionality
- Editorial process focused

Use both together for complete editorial and audit trail.

---

## Best Practices

### What should I track?

**Always track**:
- User authentication (login/logout)
- User account changes
- Role assignments

**Track if relevant**:
- Content operations (for content sites)
- Workflow transitions (for editorial sites)
- Media/file operations (for DAM sites)

See [Use Cases](1-users/3-use-cases.md) for examples.

### How often should I review logs?

**Recommended schedule**:
- **Daily**: Authentication events (logins, failures)
- **Weekly**: Content changes and deletions
- **Monthly**: Comprehensive review
- **After incidents**: Immediate investigation

### Should I keep logs forever?

Depends on requirements:
- **Compliance sites**: Often yes (HIPAA, SOC 2)
- **General sites**: 30-90 days usually sufficient
- **Development sites**: 7-30 days

Balance compliance needs with storage costs.

---

## Getting Help

### Where can I get support?

1. **Documentation**: Review the [complete documentation](index.md)
2. **Issue Queue**: [drupal.org/project/issues/admin_audit_trail](https://www.drupal.org/project/issues/admin_audit_trail)
3. **Community**: Drupal Slack, forums, and community channels

### How do I report a bug?

1. Search existing issues first
2. Create detailed bug report:
   - Steps to reproduce
   - Expected vs actual behavior
   - Drupal version
   - Module version
   - Enabled sub-modules
3. Post to [issue queue](https://www.drupal.org/project/issues/admin_audit_trail)

### How can I contribute?

- Report bugs
- Suggest features
- Submit patches
- Improve documentation
- Test new releases
- Share use cases

Visit the [project page](https://www.drupal.org/project/admin_audit_trail) to contribute.

---

## Next Steps

- [Install the module](1-users/0-installation.md)
- [Configure settings](2-admins/0-configuration.md)
- [Learn about sub-modules](2-admins/2-submodules.md)
- [Review use cases](1-users/3-use-cases.md)
- [Explore developer API](3-developers/1-api-reference.md)
