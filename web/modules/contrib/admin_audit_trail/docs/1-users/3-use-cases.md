# Common Use Cases

Real-world scenarios and examples of how Admin Audit Trail helps solve common problems.

## Security and Access Control

### Use Case 1: Investigating Unauthorized Access

**Scenario**: You suspect someone accessed the site without authorization.

**Solution**:
1. Navigate to the audit trail (`/admin/reports/audit-trail`)
2. Filter by **Operation**: Login
3. Filter by **Date Range**: Suspected timeframe
4. Look for:
   - Login attempts from unusual IP addresses
   - Multiple failed login attempts (brute force)
   - Successful logins at unusual times

**What to look for**:
```
Failed login for admin from IP 45.12.34.56 [repeated 50 times]
Login successful for admin from IP 45.12.34.56
```

**Action**: If unauthorized access confirmed, immediately change passwords and review security settings.

### Use Case 2: Tracking Failed Login Attempts

**Scenario**: Monitor for brute force attacks or unauthorized access attempts.

**Solution**:
1. Filter by **Operation**: Login Failed
2. Review patterns:
   - Same IP with multiple attempts = potential brute force
   - Different IPs targeting same account = distributed attack
   - Failed attempts for non-existent users = scanning

**Prevention**:
- Enable account lockout after X failed attempts
- Implement IP blocking for repeated failures
- Use two-factor authentication
- Monitor logs regularly

### Use Case 3: Audit User Role Changes

**Scenario**: Ensure no unauthorized privilege escalation.

**Solution**:
1. Enable **Admin Audit Trail User Roles** module
2. Filter by **Type**: User Role
3. Review all role assignments and removals
4. Verify each change was authorized

**Red flags**:
```
User editor added Administrator role to editor [suspicious self-promotion]
User temp_contractor added Administrator role [temporary user shouldn't have admin]
```

## Content Management

### Use Case 4: Find Who Deleted Important Content

**Scenario**: Critical content disappeared and you need to know who removed it.

**Solution**:
1. Filter by **Type**: Node
2. Filter by **Operation**: Delete
3. Filter by **Date Range**: When content was last seen
4. Review deletion entries

**Example log entry**:
```
Type: node
Operation: delete
Description: Deleted article "Q4 Financial Report"
User: john.doe
Timestamp: 2024-01-15 16:45:00
```

**Next steps**:
- Contact john.doe to understand why
- Restore from backup if needed
- Review deletion permissions

### Use Case 5: Track Content Approval Workflow

**Scenario**: Ensure content follows proper editorial approval before publication.

**Solution**:
1. Enable **Admin Audit Trail Workflows** module
2. Filter by **Type**: Workflow
3. Review state transitions for specific content

**Typical approval flow**:
```
10:00 - User writer: Created article "New Product"
10:15 - User writer: Workflow change Draft → Needs Review
11:30 - User editor: Workflow change Needs Review → Approved
11:35 - User manager: Workflow change Approved → Published
```

**Compliance benefit**: Prove content went through required review process.

### Use Case 6: Monitor Content Updates

**Scenario**: Track changes to high-value content (legal pages, pricing, terms of service).

**Solution**:
1. Filter by **Type**: Node
2. Filter by **Operation**: Update
3. Optionally filter by specific content titles or date ranges
4. Review who made changes and when

**Example monitoring**:
```
Updated page "Terms of Service" - Changed legal text
User: legal.admin
Timestamp: 2024-01-10 09:00:00
```

**Best practice**: Review critical page updates daily or weekly.

## Compliance and Auditing

### Use Case 7: HIPAA Compliance Auditing

**Scenario**: Healthcare organization must track access to protected health information (PHI).

**Solution**:
1. Enable tracking for all content types containing PHI
2. Regularly review audit logs for access patterns
3. Export logs for compliance reporting

**What to track**:
- Who accessed patient records (content views, if tracked)
- Who modified patient data
- When records were created or deleted
- Authentication events (login/logout)

**Reporting period**: Typically quarterly or annually for compliance audits.

### Use Case 8: GDPR Data Access Requests

**Scenario**: User requests all data showing what information about them has been accessed or modified.

**Solution**:
1. Filter by **User**: Specific user account
2. Export all log entries for that user
3. Filter by **Reference ID**: User ID for logs about that user
4. Provide comprehensive report

**Data to include**:
- All actions performed by the user
- All actions performed on the user's account
- Timestamps and IP addresses
- Related content modifications

### Use Case 9: SOC 2 Audit Trail Requirements

**Scenario**: SaaS company needs comprehensive audit logs for SOC 2 compliance.

**Solution**:
1. Enable all relevant sub-modules
2. Configure unlimited log retention (or very high limit)
3. Implement regular log reviews
4. Document log review procedures

**Key requirements**:
- **Security**: Track all authentication events
- **Availability**: Monitor system changes
- **Processing Integrity**: Track data modifications
- **Confidentiality**: Log access to sensitive data
- **Privacy**: Track personal data handling

## Team Management

### Use Case 10: Onboarding Review

**Scenario**: New employee completed onboarding; verify proper setup.

**Solution**:
1. Filter by **Date Range**: Onboarding period
2. Filter by **User**: HR admin or IT admin
3. Review user account creation and role assignments

**Checklist verification**:
```
✓ User account created
✓ Correct roles assigned
✓ First successful login recorded
✓ No failed login attempts
✓ Access to appropriate content areas
```

### Use Case 11: Offboarding Verification

**Scenario**: Employee left company; verify account was properly disabled.

**Solution**:
1. Filter by **Type**: User
2. Search for the departing employee's username
3. Verify account deactivation or deletion

**What to verify**:
```
✓ Roles removed or account blocked
✓ No login activity after departure date
✓ Content ownership transferred (if applicable)
✓ No ongoing sessions
```

### Use Case 12: Team Productivity Analysis

**Scenario**: Understand team content creation patterns.

**Solution**:
1. Filter by **Type**: Node
2. Filter by **Operation**: Insert (new content)
3. Filter by **Date Range**: Reporting period
4. Group by user to see activity levels

**Analysis questions**:
- Who creates the most content?
- What are peak creation times?
- How does productivity vary by day/week?

## Troubleshooting

### Use Case 13: Debugging Content Issues

**Scenario**: Content appears broken; determine what changed.

**Solution**:
1. Find the affected content's node ID
2. Filter by **Reference ID**: That node ID
3. Review recent updates
4. Compare timestamps with when issue appeared

**Timeline reconstruction**:
```
Day 1, 10:00 - User editor: Updated article "Product Guide"
Day 1, 14:00 - User admin: Updated article "Product Guide"
Day 1, 14:05 - [User reports content is broken]
```

**Conclusion**: admin's change at 14:00 likely caused the issue.

### Use Case 14: Finding Spam or Malicious Content

**Scenario**: Spam content appeared on the site.

**Solution**:
1. Filter by **Operation**: Insert
2. Filter by **Date Range**: When spam appeared
3. Look for unusual patterns:
   - Rapid content creation
   - New or suspicious user accounts
   - Off-hours activity

**Red flags**:
```
02:00 - User spammer123: Created article "Buy Cheap Products"
02:01 - User spammer123: Created article "Click Here Now"
02:02 - User spammer123: Created article "Amazing Offer"
[10 more similar entries in 5 minutes]
```

**Action**: Delete spam, block user, review registration process.

## Site Maintenance

### Use Case 15: Pre/Post Migration Verification

**Scenario**: Verify content migration completed successfully.

**Solution**:
1. Review audit logs before migration (baseline)
2. Perform migration
3. Compare audit logs after migration
4. Identify any unexpected changes

**What to verify**:
- Content counts match (inserts)
- No unexpected deletions
- User accounts migrated correctly
- Taxonomy terms preserved

### Use Case 16: Configuration Change Tracking

**Scenario**: Site behavior changed unexpectedly; identify configuration modifications.

**Solution**:
1. Filter by **Date Range**: Before behavior changed
2. Look for relevant entity type updates
3. Review who made configuration changes

**Configuration entities to monitor**:
- Views
- Blocks
- Menus
- Content types
- Field configurations

### Use Case 17: Bulk Operation Verification

**Scenario**: Performed bulk content update; verify it worked correctly.

**Solution**:
1. Filter by **Operation**: Update
2. Filter by **User**: Your account
3. Filter by **Date Range**: When bulk operation ran
4. Review number of updates matches expected count

**Example verification**:
```
Bulk operation: Update 50 articles
Audit log shows: 50 update operations
Timestamp range: 14:00:00 to 14:00:05
Conclusion: ✓ All updates completed
```

## Media Management

### Use Case 18: Track Media Asset Changes

**Scenario**: Monitor changes to important media files (logos, branding).

**Solution**:
1. Enable **Admin Audit Trail Media** module
2. Filter by **Type**: Media
3. Review media uploads, updates, and deletions

**Use cases**:
- Brand logo replaced
- Product images updated
- Old media assets removed
- Alt text and metadata changes

### Use Case 19: File Upload Monitoring

**Scenario**: Track who uploads files and when.

**Solution**:
1. Enable **Admin Audit Trail File** module
2. Filter by **Type**: File
3. Monitor for:
   - Large file uploads
   - Suspicious file types
   - Unauthorized uploads

## Advanced Scenarios

### Use Case 20: Multi-site Network Monitoring

**Scenario**: Drupal multisite installation; monitor all sites centrally.

**Solution**:
1. Enable Admin Audit Trail on all sites
2. Configure shared database table (advanced)
3. Review combined logs from all sites
4. Filter by site ID if needed

**Benefits**:
- Centralized security monitoring
- Cross-site user activity tracking
- Unified compliance reporting

## Best Practices Summary

1. **Regular Reviews**
   - Daily: Authentication logs
   - Weekly: Content changes
   - Monthly: Full audit review

2. **Set Up Alerts** (manual or automated)
   - Failed login spikes
   - Off-hours deletions
   - Role assignment changes

3. **Document Procedures**
   - How to review logs
   - What to look for
   - Escalation procedures

4. **Team Training**
   - Train staff on proper logging
   - Educate on what gets tracked
   - Review log interpretation

5. **Retention Balance**
   - Keep enough for compliance
   - Don't store indefinitely (privacy)
   - Archive old logs if needed

## Next Steps

- [Configure log retention policies](../2-admins/0-configuration.md)
- [Set up appropriate permissions](../2-admins/1-permissions.md)
- [Learn about extending with custom events](../3-developers/0-custom-handlers.md)
