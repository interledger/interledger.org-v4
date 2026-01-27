# Performance and Maintenance

Best practices for maintaining optimal performance with Admin Audit Trail enabled.

## Performance Overview

Admin Audit Trail is designed to have minimal performance impact, but like any logging system, it consumes resources:

**Resources Used**:
- Database storage (log entries)
- Database write operations (logging events)
- Database read operations (viewing logs)
- CPU (processing hooks, formatting descriptions)

**Performance factors**:
- Number of enabled sub-modules
- Site traffic and activity level
- Log retention settings
- Database optimization
- Server resources

## Measuring Performance Impact

### Before Enabling

Establish baseline metrics:

```bash
# Database size
drush sqlq "SELECT
  SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) AS 'Total Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = DATABASE()"

# Page load times
# Use tools like:
# - New Relic
# - Blackfire.io
# - Drupal Devel module
```

### After Enabling

Monitor changes:

```bash
# Check admin_audit_trail table size
drush sqlq "SELECT
  table_name,
  table_rows,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_name = 'admin_audit_trail'"

# Check total log count
drush sqlq "SELECT COUNT(*) FROM admin_audit_trail"

# Check logs per day (last 30 days)
drush sqlq "SELECT
  DATE(FROM_UNIXTIME(timestamp)) as date,
  COUNT(*) as logs_per_day
FROM admin_audit_trail
WHERE timestamp > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
GROUP BY DATE(FROM_UNIXTIME(timestamp))
ORDER BY date DESC"
```

## Database Optimization

### 1. Indexes

Ensure proper indexes exist (usually created automatically):

```sql
-- Check existing indexes
SHOW INDEX FROM admin_audit_trail;

-- Recommended indexes (usually already present)
CREATE INDEX idx_type ON admin_audit_trail(type);
CREATE INDEX idx_operation ON admin_audit_trail(operation);
CREATE INDEX idx_uid ON admin_audit_trail(uid);
CREATE INDEX idx_timestamp ON admin_audit_trail(timestamp);
CREATE INDEX idx_ref_id ON admin_audit_trail(ref_id);
```

### 2. Table Optimization

Periodically optimize the table:

```bash
# Via Drush
drush sqlq "OPTIMIZE TABLE admin_audit_trail"

# Schedule monthly via cron
# Add to crontab:
0 2 1 * * cd /var/www/html && drush sqlq "OPTIMIZE TABLE admin_audit_trail"
```

### 3. Partition Large Tables (Advanced)

For very large audit logs, consider partitioning by date:

```sql
-- Example: Partition by year
ALTER TABLE admin_audit_trail
PARTITION BY RANGE (YEAR(FROM_UNIXTIME(timestamp))) (
  PARTITION p2023 VALUES LESS THAN (2024),
  PARTITION p2024 VALUES LESS THAN (2025),
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

**Benefits**:
- Faster queries on recent data
- Easier archival (drop old partitions)
- Better index performance

## Log Retention Strategy

### Setting Appropriate Limits

**Calculation**:
1. Measure daily log volume for one week
2. Calculate average: `total_logs / 7`
3. Multiply by desired retention days
4. Add 20% buffer

**Example**:
```
Week 1 logs: 7,000
Daily average: 1,000
Retention goal: 90 days
Calculation: 1,000 × 90 × 1.2 = 108,000
Setting: 100,000 (closest option)
```

### Retention by Site Type

| Site Type | Traffic | Recommended Retention |
|-----------|---------|----------------------|
| Small blog | Low | 10,000 - 100,000 |
| Corporate site | Medium | 100,000 |
| News site | High | 100,000 or unlimited |
| E-commerce | High | Unlimited (compliance) |
| Enterprise | Very High | Unlimited with archival |

### Archival Strategy

For unlimited retention sites, implement archival:

**Monthly archival**:
```bash
#!/bin/bash
# archive-audit-logs.sh

DATE=$(date +%Y-%m)
ARCHIVE_DIR="/var/backups/audit-trail"

# Export logs older than 6 months
drush sqlq "SELECT * FROM admin_audit_trail
WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH))
INTO OUTFILE '$ARCHIVE_DIR/audit-trail-$DATE.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
LINES TERMINATED BY '\n'"

# Compress archive
gzip "$ARCHIVE_DIR/audit-trail-$DATE.csv"

# Delete archived logs from database
drush sqlq "DELETE FROM admin_audit_trail
WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH))"

# Optimize table
drush sqlq "OPTIMIZE TABLE admin_audit_trail"
```

**Schedule via cron**:
```
0 3 1 * * /path/to/archive-audit-logs.sh
```

## Selective Sub-module Enabling

Enable only necessary sub-modules to reduce log volume:

### High-Traffic Entities

These generate the most logs:

1. **admin_audit_trail_node** - Very high on content sites
2. **admin_audit_trail_paragraphs** - High (many paragraphs per page)
3. **admin_audit_trail_comment** - High on active communities

**Strategy**: Enable selectively based on needs

### Low-Traffic Entities

These generate few logs:

1. **admin_audit_trail_menu** - Rare changes
2. **admin_audit_trail_user** - Infrequent
3. **admin_audit_trail_taxonomy** - Occasional

**Strategy**: Safe to enable on all sites

### Recommendation

**Start minimal**:
```bash
drush en admin_audit_trail_auth admin_audit_trail_user admin_audit_trail_user_roles
```

**Add as needed**:
```bash
drush en admin_audit_trail_node  # When content tracking needed
drush en admin_audit_trail_workflows  # When workflow tracking needed
```

## Query Optimization

### Slow Query Identification

**Enable MySQL slow query log**:
```sql
-- In my.cnf
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-queries.log
long_query_time = 2
```

**Check for slow audit trail queries**:
```bash
grep "admin_audit_trail" /var/log/mysql/slow-queries.log
```

### Optimizing Common Queries

**Filter by date range** (most common):
```sql
-- Good: Uses index
SELECT * FROM admin_audit_trail
WHERE timestamp BETWEEN UNIX_TIMESTAMP('2024-01-01') AND UNIX_TIMESTAMP('2024-01-31')
ORDER BY timestamp DESC
LIMIT 50;

-- Bad: Full table scan
SELECT * FROM admin_audit_trail
WHERE FROM_UNIXTIME(timestamp) BETWEEN '2024-01-01' AND '2024-01-31';
```

**Filter by type and operation**:
```sql
-- Good: Uses indexes
SELECT * FROM admin_audit_trail
WHERE type = 'node' AND operation = 'delete'
ORDER BY timestamp DESC;
```

**Filter by user**:
```sql
-- Good: Uses index
SELECT * FROM admin_audit_trail
WHERE uid = 3
ORDER BY timestamp DESC;
```

## Caching Considerations

### Page Caching

Audit trail pages should not be cached:

**In settings.php**:
```php
// Exclude audit trail from page cache
$settings['cache']['bins']['page']['exclude'] = [
  '/admin/reports/audit-trail*',
  '/admin/config/development/audit-trail*',
];
```

### Database Caching

Consider database query caching for repeated queries:

**MySQL query cache** (if supported):
```sql
-- In my.cnf
[mysqld]
query_cache_type = 1
query_cache_size = 32M
query_cache_limit = 2M
```

**Note**: Query cache deprecated in MySQL 8.0+

## Server Resource Optimization

### Database Server

**Recommended MySQL settings for large audit logs**:

```ini
# my.cnf
[mysqld]
innodb_buffer_pool_size = 1G  # Or 70% of RAM
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2  # Better performance, slight durability trade-off
innodb_flush_method = O_DIRECT
```

### PHP Configuration

**Recommended settings**:

```ini
# php.ini
memory_limit = 256M  # For viewing large log sets
max_execution_time = 60  # For exports
```

### Web Server

**Apache**:
```apache
# Increase timeout for audit trail pages
<Location /admin/reports/audit-trail>
  Timeout 300
</Location>
```

**Nginx**:
```nginx
location /admin/reports/audit-trail {
  fastcgi_read_timeout 300;
}
```

## Monitoring and Alerts

### Database Growth Monitoring

**Weekly check**:
```bash
#!/bin/bash
# check-audit-trail-size.sh

SIZE=$(drush sqlq "SELECT
  ROUND(((data_length + index_length) / 1024 / 1024), 2)
FROM information_schema.TABLES
WHERE table_name = 'admin_audit_trail'" --format=string)

THRESHOLD=1000  # MB

if (( $(echo "$SIZE > $THRESHOLD" | bc -l) )); then
  echo "WARNING: Audit trail table size ($SIZE MB) exceeds threshold ($THRESHOLD MB)"
  # Send alert email
  echo "Audit trail size: $SIZE MB" | mail -s "Audit Trail Size Alert" admin@example.com
fi
```

### Performance Monitoring

**Track key metrics**:
- Audit trail page load time
- Database query time
- Table size growth rate
- Logs per day

**Tools**:
- New Relic
- Blackfire.io
- MySQL Workbench
- Drupal Devel module

### Automated Alerts

**Set up alerts for**:
1. Table size exceeds threshold
2. Query time exceeds 2 seconds
3. Log volume spike (10x normal)
4. Disk space low

## Troubleshooting Performance Issues

### Slow Audit Trail Page

**Symptoms**: Audit trail page takes >5 seconds to load

**Solutions**:
1. **Check table size**:
   ```bash
   drush sqlq "SELECT COUNT(*) FROM admin_audit_trail"
   ```
2. **Enable retention limit** to reduce table size
3. **Check indexes**: Ensure all recommended indexes exist
4. **Optimize table**: `OPTIMIZE TABLE admin_audit_trail`
5. **Check slow query log** for problematic queries

### High Database I/O

**Symptoms**: High disk I/O, slow site performance

**Solutions**:
1. **Reduce log volume**: Disable unnecessary sub-modules
2. **Increase buffer pool**: Allocate more RAM to MySQL
3. **Use SSD storage**: Faster disk = faster writes
4. **Archive old logs**: Move to separate table/database

### Out of Disk Space

**Symptoms**: Database errors, cannot save content

**Immediate fix**:
```bash
# Delete oldest logs
drush sqlq "DELETE FROM admin_audit_trail
WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY))
LIMIT 10000"

# Optimize table to reclaim space
drush sqlq "OPTIMIZE TABLE admin_audit_trail"
```

**Long-term fix**:
1. Set retention limit
2. Implement archival strategy
3. Increase disk space
4. Monitor growth trends

## Best Practices Summary

### Do's

✓ **Set retention limits** appropriate for your needs
✓ **Monitor table size** regularly
✓ **Optimize table** monthly
✓ **Archive old logs** for compliance sites
✓ **Enable only needed sub-modules**
✓ **Use indexed fields** in queries
✓ **Test on staging** before production changes
✓ **Document procedures** for team

### Don'ts

✗ **Don't enable unlimited retention** without monitoring
✗ **Don't skip index optimization**
✗ **Don't cache audit trail pages**
✗ **Don't ignore performance warnings**
✗ **Don't delete logs without backup** (compliance sites)
✗ **Don't enable all sub-modules** unless needed
✗ **Don't skip regular reviews** of log volume

## Performance Checklist

Use this checklist for optimal performance:

- [ ] Baseline metrics established
- [ ] Retention limit configured appropriately
- [ ] Cron running regularly
- [ ] Table indexes verified
- [ ] Only necessary sub-modules enabled
- [ ] Database optimized monthly
- [ ] Disk space monitored
- [ ] Slow queries identified and fixed
- [ ] Archival strategy implemented (if needed)
- [ ] Performance monitoring in place
- [ ] Alert thresholds configured
- [ ] Team trained on maintenance procedures

## Next Steps

- [Review sub-modules](2-submodules.md) to enable only what's needed
- [Configure retention settings](0-configuration.md)
- [Set up permissions](1-permissions.md)
- [Learn about custom event tracking](../3-developers/0-custom-handlers.md)
