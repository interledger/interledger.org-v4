# Permissions

Learn how to configure Admin Audit Trail permissions to control who can access and manage audit logs.

## Available Permissions

Admin Audit Trail provides two permissions for access control:

### 1. Access Admin Audit Trail

**Permission Name**: `access admin audit trail`

**Machine Name**: `access admin audit trail`

**Description**: Allows users to view and search the audit trail log entries.

**What this grants access to**:
- View audit trail page (`/admin/reports/audit-trail`)
- Search and filter audit logs
- View log entry details
- Export logs (if export functionality is available)

**What this does NOT grant**:
- Cannot modify log entries (logs are read-only)
- Cannot delete log entries
- Cannot configure audit trail settings
- Cannot enable/disable sub-modules

**Recommended for**: Administrators, security personnel, compliance officers, auditors

### 2. Configure Admin Audit Trail

**Permission Name**: `configure admin audit trail`

**Machine Name**: `configure admin audit trail`

**Description**: Allows users to access and modify the Admin Audit Trail configuration settings.

**What this grants access to**:
- Access settings page (`/admin/config/development/audit-trail/settings`)
- Modify retention limits
- Change filter display preferences
- Configure all module settings

**What this does NOT grant**:
- Does not automatically grant view access (must also have "Access" permission)
- Cannot enable/disable modules (requires "Administer modules" permission)

**Recommended for**: Site administrators only

## Permission Relationships

### View Access Only (Read-Only Auditor)
- ✓ Access Admin Audit Trail
- ✗ Configure Admin Audit Trail

**Use case**: Compliance officers, security analysts, managers who need to review logs but not change settings.

### Full Admin Access
- ✓ Access Admin Audit Trail
- ✓ Configure Admin Audit Trail

**Use case**: Site administrators, DevOps team members who manage the audit system.

### No Access (Default)
- ✗ Access Admin Audit Trail
- ✗ Configure Admin Audit Trail

**Use case**: Content editors, anonymous users, authenticated users without audit responsibilities.

## Configuring Permissions

### Via Drupal UI

1. Navigate to **Administration > People > Permissions** (`/admin/people/permissions`)
2. Scroll to the **Admin Audit Trail** section
3. Check appropriate boxes for each role
4. Click **Save permissions** at the bottom

### Via Drush

**Grant access permission**:
```bash
drush role:perm:add editor 'access admin audit trail'
```

**Grant configuration permission**:
```bash
drush role:perm:add administrator 'configure admin audit trail'
```

**Remove permission**:
```bash
drush role:perm:remove editor 'access admin audit trail'
```

**List permissions for a role**:
```bash
drush role:perm:list administrator
```

## Recommended Permission Setup

### Standard Drupal Roles

#### Administrator Role
```
✓ Access Admin Audit Trail
✓ Configure Admin Audit Trail
✓ Administer modules (to enable/disable sub-modules)
```

**Why**: Full control over audit trail system

#### Editor / Content Manager Role
```
✗ Access Admin Audit Trail
✗ Configure Admin Audit Trail
```

**Why**: Don't need to see audit logs; focus on content management

#### Authenticated User Role
```
✗ Access Admin Audit Trail
✗ Configure Admin Audit Trail
```

**Why**: Regular users don't need audit access

#### Anonymous User Role
```
✗ Access Admin Audit Trail
✗ Configure Admin Audit Trail
```

**Why**: Never grant audit access to anonymous users

### Custom Security Roles

Many organizations create specialized roles for audit management:

#### Security Officer Role
```
✓ Access Admin Audit Trail
✗ Configure Admin Audit Trail
```

**Purpose**: Monitor security events, investigate incidents
**Can**: View all logs, filter and search
**Cannot**: Change settings, delete logs

#### Compliance Auditor Role
```
✓ Access Admin Audit Trail
✗ Configure Admin Audit Trail
```

**Purpose**: Review logs for compliance reporting
**Can**: View and export logs
**Cannot**: Modify system configuration

#### Site Manager Role
```
✓ Access Admin Audit Trail
✓ Configure Admin Audit Trail
```

**Purpose**: Manage audit trail system
**Can**: Everything except enabling modules
**Cannot**: Enable/disable modules (needs separate permission)

## Security Best Practices

### 1. Principle of Least Privilege

Grant audit trail access only to those who need it:

❌ **Don't**:
- Grant to all authenticated users
- Give configuration access to non-admins
- Allow anonymous access

✓ **Do**:
- Limit access to specific roles
- Separate read from write permissions
- Regularly review who has access

### 2. Audit the Auditors

Track who accesses the audit trail:

**If possible**:
- Log views of the audit trail page
- Track who exports logs
- Monitor configuration changes

**Manual approach**:
- Review user login times
- Cross-reference with audit trail access times
- Document who has access and why

### 3. Role Segregation

Separate responsibilities:

| Responsibility | Role | Permissions |
|----------------|------|-------------|
| View logs | Security Officer | Access only |
| Configure settings | System Admin | Both |
| Daily monitoring | Compliance Team | Access only |
| Incident response | Security Team | Access only |

### 4. Regular Permission Audits

**Monthly review**:
```bash
# List all users with audit trail access
drush sql:query "SELECT DISTINCT u.uid, u.name, u.mail
FROM users_field_data u
INNER JOIN user__roles ur ON u.uid = ur.entity_id
INNER JOIN role_permission rp ON ur.roles_target_id = rp.rid
WHERE rp.permission IN ('access admin audit trail', 'configure admin audit trail')"
```

Review and verify:
- Is access still needed?
- Are there departing employees?
- Any suspicious accounts?

### 5. Document Access Decisions

Maintain a record of:
- Who has audit trail access
- Why they have access
- When access was granted
- When access should be reviewed

**Example log**:
```
2024-01-15: Granted "Access Admin Audit Trail" to john.doe (Security Officer)
Reason: New security team member
Review date: 2024-07-15
```

## Common Permission Scenarios

### Scenario 1: New Security Team Member

**Requirement**: New hire needs to review security logs

**Solution**:
1. Create or assign to "Security Officer" role
2. Grant "Access Admin Audit Trail" permission
3. Do not grant "Configure" permission
4. Document access in personnel records

### Scenario 2: External Compliance Audit

**Requirement**: External auditor needs temporary access

**Solution**:
1. Create temporary "External Auditor" role
2. Grant "Access Admin Audit Trail" only
3. Create temporary user account
4. Set account expiration date
5. Remove access after audit completes

### Scenario 3: Contractor Access

**Requirement**: Contractor needs to configure audit settings

**Solution**:
1. Assign to "Site Manager" role (limited admin)
2. Grant both permissions
3. Set account expiration date
4. Review access weekly
5. Remove immediately upon contract end

### Scenario 4: Department Manager

**Requirement**: Manager wants to review team member activity

**Consideration**: Privacy implications

**Solution**:
1. Evaluate privacy policies
2. If appropriate, grant "Access Admin Audit Trail"
3. Train on appropriate use
4. Document business justification
5. Consider limited date range exports instead

## Integration with Other Permissions

### Related Core Permissions

Audit trail permissions often work with:

**View reports**:
- "Access site reports" - General reports permission
- May be required for audit trail page access (check your setup)

**User administration**:
- "Administer users" - To see user details in logs
- "View user information" - To understand logged user actions

**Content permissions**:
- Understanding logged content requires content view permissions
- Consider granting relevant view permissions to auditors

**Configuration**:
- "Administer modules" - To enable/disable sub-modules
- "Administer site configuration" - General config access

### Module-Specific Permissions

If using related modules:

**Views integration**:
- "Administer views" - To create custom audit log views
- "Access [view name]" - To view custom audit trail views

**Export modules**:
- "Export data" - If using Views Data Export or similar
- Allows exporting audit logs to CSV/Excel

## Testing Permissions

### Verify Permission Setup

**Test as each role**:
1. Log in as user with that role
2. Try to access `/admin/reports/audit-trail`
3. Try to access `/admin/config/development/audit-trail/settings`
4. Verify expected access is granted/denied

**Using Drush**:
```bash
# Check if role has permission
drush role:perm:list security_officer | grep "audit trail"

# Simulate user access
drush user:login security_officer_user
```

**Using Masquerade module**:
1. Install Masquerade module
2. Switch to test user
3. Verify permissions work as expected

## Troubleshooting Permissions

### User Can't Access Audit Trail

**Issue**: User with permission still gets "Access Denied"

**Solutions**:
1. **Clear cache**: `drush cr`
2. **Verify role assignment**: Check user has the role
3. **Check role has permission**: Review permission page
4. **Check path**: Ensure correct URL `/admin/reports/audit-trail`
5. **Review conflicting modules**: Some security modules may override

### Configuration Page Not Accessible

**Issue**: Admin can't access settings page

**Solutions**:
1. Verify user has both:
   - "Configure Admin Audit Trail"
   - May also need "Administer site configuration"
2. Check path: `/admin/config/development/audit-trail/settings`
3. Clear cache
4. Check module is enabled

### Permission Not Appearing

**Issue**: Permission not listed on permissions page

**Solutions**:
1. Verify module is enabled
2. Clear cache: `drush cr`
3. Rebuild permissions: `drush php:eval "node_access_rebuild()"`
4. Check module .permissions.yml file exists

## Permission Audit Checklist

Use this checklist for regular permission reviews:

- [ ] List all roles with audit trail access
- [ ] Verify each role still needs access
- [ ] Check for departing employees
- [ ] Remove unnecessary access
- [ ] Document any changes
- [ ] Test access for each role
- [ ] Review custom roles
- [ ] Verify temporary access was removed
- [ ] Update documentation
- [ ] Schedule next review

## Next Steps

- [Learn about sub-modules](2-submodules.md) that can be enabled
- [Configure retention settings](0-configuration.md)
- [Review performance optimization](3-performance.md)
- [Set up custom event tracking](../3-developers/0-custom-handlers.md)
