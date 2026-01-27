# Configuration

Learn how to configure Admin Audit Trail settings to match your organization's requirements.

## Accessing Configuration Settings

### Via Admin Menu

1. Navigate to **Administration > Configuration > Development > Admin Audit Trail Settings**
2. Or directly visit: `/admin/config/development/audit-trail/settings`

### Via Audit Trail Page

1. Go to **Administration > Reports > Audit Trail**
2. Click the **"Settings"** link in the page header

### Required Permission

You must have the **"Configure Admin Audit Trail"** permission to access configuration settings.

## Available Settings

### 1. Filters Expanded

**Setting Name**: Filters Expanded

**Location**: Admin Audit Trail Settings page

**Description**: Controls the default state of the filter section on the audit trail page.

**Options**:
- **Checked (Expanded)**: Filters are visible by default
- **Unchecked (Collapsed)**: Filters are hidden by default

**Default Value**: Checked (Expanded)

**When to Use**:

✓ **Enable (Expanded)** if:
- Your team frequently filters audit logs
- You want filters immediately accessible
- Users are trained on using filters

✓ **Disable (Collapsed)** if:
- Your team mostly reviews unfiltered logs
- You prefer a cleaner interface
- Screen space is limited

**Impact**: User interface only - does not affect logging functionality

### 2. Audit Trail Log Messages to Keep

**Setting Name**: Audit Trail log messages to keep

**Location**: Admin Audit Trail Settings page

**Description**: Maximum number of audit log entries to retain in the database.

**Available Options**:

| Option | Value | Description |
|--------|-------|-------------|
| **All** | 0 | Keep all logs indefinitely (unlimited) |
| **100** | 100 | Keep only 100 most recent entries |
| **500** | 500 | Keep only 500 most recent entries |
| **1,000** | 1,000 | Keep 1,000 most recent entries |
| **3,000** | 3,000 | Keep 3,000 most recent entries |
| **10,000** | 10,000 | Keep 10,000 most recent entries |
| **100,000** | 100,000 | Keep 100,000 most recent entries |

**Default Value**: All (0 - unlimited retention)

**How It Works**:
1. Cron runs on schedule (hourly, daily, etc.)
2. System counts total audit log entries
3. If count exceeds configured limit, oldest entries are deleted
4. Deletion continues until count matches limit

**Important Notes**:
- Requires **cron** to be running for cleanup to occur
- Cleanup only happens during cron runs
- Oldest entries (by timestamp) are removed first
- Deleted logs cannot be recovered

### Choosing the Right Retention Limit

#### Unlimited (All)

**Use when**:
- Regulatory compliance requires indefinite retention
- You have adequate database storage
- Audit history is critical for your organization
- You handle cleanup manually or via external archival

**Considerations**:
- Database will grow continuously
- May impact performance over time
- Requires database maintenance planning

**Recommended for**:
- Healthcare (HIPAA)
- Financial services
- Government sites
- Legal/compliance-heavy organizations

#### Limited (100 - 100,000)

**Use when**:
- Database space is limited
- Only recent activity matters
- You have external log archival
- Performance is a priority

**How to choose the number**:

**100 entries** - Development/Testing
- Keeps database small
- Good for local development
- Not for production

**500 - 1,000 entries** - Small sites
- Low traffic sites
- Limited storage
- Short retention needs

**3,000 - 10,000 entries** - Medium sites
- Moderate traffic
- Balanced retention vs. storage
- Typical business needs

**100,000 entries** - Large sites
- High traffic sites
- Extended retention
- Good storage capacity

### Calculating Your Needs

**Estimate daily log volume**:
1. Count current logs after one week
2. Divide by 7 to get daily average
3. Multiply by desired retention days

**Example**:
```
Current logs after 1 week: 700 entries
Daily average: 700 / 7 = 100 entries/day
Retention goal: 30 days
Minimum limit needed: 100 × 30 = 3,000 entries
Recommended setting: 10,000 (buffer included)
```

## Configuring Cron for Log Cleanup

### Verify Cron is Running

Check cron status:

```bash
drush core:cron
```

Or via UI:
1. Navigate to **Administration > Reports > Status report** (`/admin/reports/status`)
2. Look for "Cron maintenance tasks"
3. Should show recent run time

### Configure Cron Schedule

**Via Drush** (recommended for automated sites):
```bash
# Run cron manually
drush core:cron

# Set up system cron (Linux/Mac)
# Add to crontab: Run every hour
0 * * * * cd /var/www/html && drush core:cron
```

**Via Hosting Control Panel**:
- Most hosting providers offer cron configuration
- Set to run at least daily
- Hourly is recommended for active sites

**Via Drupal UI**:
1. Navigate to **Administration > Configuration > System > Cron** (`/admin/config/system/cron`)
2. Configure automated cron interval
3. Note: Automated cron requires site traffic to trigger

### Testing Log Cleanup

**Test the cleanup process**:

1. Set retention to a low number (e.g., 100)
2. Verify you have more than 100 log entries
3. Run cron manually:
   ```bash
   drush core:cron
   ```
4. Check log count:
   ```bash
   drush sqlq "SELECT COUNT(*) FROM admin_audit_trail"
   ```
5. Should now show approximately 100 entries
6. Reset to desired retention limit

## Saving Configuration

After making changes:

1. Review your settings
2. Click **"Save configuration"** button
3. Wait for confirmation message: "The configuration options have been saved."
4. Settings take effect immediately (except cleanup, which waits for next cron run)

## Configuration Best Practices

### 1. Plan Retention Before Enabling

**Before enabling sub-modules**:
- Determine retention requirements
- Calculate expected log volume
- Configure retention limit
- Set up cron schedule

**Why**: Prevents database bloat from the start

### 2. Match Retention to Compliance Needs

| Compliance Standard | Recommended Retention |
|---------------------|----------------------|
| HIPAA | Unlimited or 6+ years |
| GDPR | As needed, allow user deletion |
| SOC 2 | Unlimited or 1+ year |
| PCI DSS | Unlimited or 90+ days |
| General business | 30-90 days |
| Development/testing | 7-30 days |

### 3. Monitor Database Growth

**Monthly checks**:
```bash
# Check table size
drush sqlq "SELECT
  COUNT(*) as total_logs,
  MIN(timestamp) as oldest_log,
  MAX(timestamp) as newest_log
FROM admin_audit_trail"

# Check database size
drush sqlq "SELECT
  table_name,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_name = 'admin_audit_trail'"
```

### 4. Consider Archival Strategy

**For unlimited retention sites**:
1. Export old logs to archive storage
2. Keep recent logs in database
3. Archive logs older than X months
4. Maintain archived logs for compliance

**Archive process**:
```bash
# Export logs older than 1 year
drush sqlq "SELECT * FROM admin_audit_trail
WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))"
--result-file=audit_trail_archive_2024.csv

# Delete after verifying export
drush sqlq "DELETE FROM admin_audit_trail
WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 YEAR))"
```

### 5. Test Configuration Changes

Before applying to production:
1. Test on staging environment
2. Verify cron cleanup works
3. Monitor performance impact
4. Document configuration decisions

## Performance Considerations

### Database Performance

**Impact of unlimited logs**:
- Larger database size
- Slower queries as table grows
- Increased backup time
- More storage costs

**Optimization strategies**:
1. Add database indexes (usually already present)
2. Archive old logs periodically
3. Use retention limits
4. Monitor query performance

### Cron Performance

**Cleanup can be resource-intensive**:
- Deleting thousands of records takes time
- May slow down cron run
- Plan cleanup during low-traffic periods

**Optimization**:
```php
// In settings.php or settings.local.php
// Run audit trail cleanup at specific time
$config['admin_audit_trail.settings']['cleanup_time'] = '02:00'; // 2 AM
```

## Troubleshooting Configuration

### Settings Not Saving

**Issue**: Changes don't persist after clicking Save

**Solutions**:
- Check you have "Configure Admin Audit Trail" permission
- Clear cache: `drush cr`
- Check file permissions on config directory
- Review PHP error logs

### Cleanup Not Running

**Issue**: Old logs not being deleted despite retention limit

**Solutions**:
1. Verify cron is running: `drush core:cron`
2. Check retention limit is set (not "All")
3. Manually run cleanup:
   ```bash
   drush php:eval "admin_audit_trail_cron()"
   ```
4. Check database for locks or errors

### Too Many Logs Deleted

**Issue**: Needed logs were removed by cleanup

**Solutions**:
- Restore from database backup
- Increase retention limit
- Review retention calculation
- Consider unlimited retention for compliance

### Performance Degradation

**Issue**: Site slow after enabling audit trail

**Solutions**:
1. Check database size and indexes
2. Reduce retention limit
3. Archive old logs
4. Optimize database
   ```bash
   drush sqlq "OPTIMIZE TABLE admin_audit_trail"
   ```

## Configuration Checklist

Use this checklist when configuring Admin Audit Trail:

- [ ] Determine retention requirements (compliance, business needs)
- [ ] Calculate expected log volume
- [ ] Configure retention limit appropriately
- [ ] Verify cron is running
- [ ] Test cron cleanup process
- [ ] Set filters expanded preference
- [ ] Document configuration decisions
- [ ] Train team on retention policy
- [ ] Schedule regular log reviews
- [ ] Plan archival strategy (if needed)
- [ ] Monitor database growth
- [ ] Test restore procedures

## Next Steps

- [Configure permissions](1-permissions.md) for your team
- [Learn about sub-modules](2-submodules.md) and what they track
- [Optimize performance](3-performance.md) for your site
- [Review user guide](../1-users/1-viewing-logs.md) for viewing logs
