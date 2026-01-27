# Installation and Setup

This guide will help you install and set up the Admin Audit Trail module on your Drupal site.

## Requirements

### Core Requirements
- Drupal 10 or 11
- PHP 8.3 or higher

### Required Drupal Core Modules
Different sub-modules may require specific Drupal core modules:

- **Comment module** - Required for Admin Audit Trail Comment
- **Media module** - Required for Admin Audit Trail Media
- **Workflows module** - Required for Admin Audit Trail Workflows

### Optional Contributed Modules
Some sub-modules require contributed modules:

- [Redirect module](https://www.drupal.org/project/redirect) - For Admin Audit Trail Redirect
- [Entityqueue module](https://www.drupal.org/project/entityqueue) - For Admin Audit Trail Entityqueue
- [Group module](https://www.drupal.org/project/group) - For Admin Audit Trail Group
- [Paragraphs module](https://www.drupal.org/project/paragraphs) - For Admin Audit Trail Paragraphs

## Installation Methods

### Method 1: Using Composer (Recommended)

```bash
composer require drupal/admin_audit_trail
drush en admin_audit_trail
```

### Method 2: Using Drush

If the module is already downloaded:

```bash
drush en admin_audit_trail
```

To enable specific sub-modules:

```bash
drush en admin_audit_trail_node admin_audit_trail_user admin_audit_trail_auth
```

### Method 3: Manual Installation

1. Download the module from [Drupal.org](https://www.drupal.org/project/admin_audit_trail)
2. Extract the archive to your `modules/contrib` directory
3. Navigate to **Administration > Extend** (`/admin/modules`)
4. Find "Admin Audit Trail" in the module list
5. Check the box next to "Admin Audit Trail"
6. Check boxes for any desired sub-modules
7. Click "Install" at the bottom of the page
8. Clear the site cache

## Quick Start Installation

For most sites, we recommend enabling these essential sub-modules:

```bash
# Enable the base module
drush en admin_audit_trail

# Enable core tracking modules
drush en admin_audit_trail_auth      # User authentication tracking
drush en admin_audit_trail_user      # User account tracking
drush en admin_audit_trail_node      # Content tracking

# Clear cache
drush cr
```

## Enabling Sub-modules

Admin Audit Trail uses a modular architecture. The base module provides the framework, while sub-modules track specific entity types.

### Via Drush

Enable all authentication and user tracking:
```bash
drush en admin_audit_trail_auth admin_audit_trail_user admin_audit_trail_user_roles
```

Enable content tracking:
```bash
drush en admin_audit_trail_node admin_audit_trail_comment admin_audit_trail_media
```

### Via UI

1. Navigate to **Administration > Extend** (`/admin/modules`)
2. Search for "Admin Audit Trail"
3. Enable the base module if not already enabled
4. Enable desired sub-modules:
   - Expand the "Admin Audit Trail" section
   - Check boxes for sub-modules you want to enable
   - Click "Install"

## Verifying Installation

After installation, verify that the module is working:

1. Navigate to **Administration > Reports > Audit Trail** (`/admin/reports/audit-trail`)
2. You should see the audit trail page (it may be empty if no tracked events have occurred yet)
3. Perform a tracked action (e.g., edit a user account if `admin_audit_trail_user` is enabled)
4. Refresh the audit trail page to see the logged event

## Initial Configuration

After installation, you may want to configure these settings:

1. **Set Permissions**
   - Navigate to **Administration > People > Permissions** (`/admin/people/permissions`)
   - Grant "Access Admin Audit Trail" to appropriate roles
   - Grant "Configure Admin Audit Trail" to administrators only

2. **Configure Settings** (Optional)
   - Navigate to **Administration > Configuration > Development > Admin Audit Trail Settings** (`/admin/config/development/audit-trail/settings`)
   - Set log retention limits
   - Configure filter display preferences

3. **Enable Cron** (For log rotation)
   - Ensure your site's cron is running regularly
   - This is required for automatic log cleanup based on retention settings

## What Happens After Installation?

Once you enable a sub-module, it immediately begins logging events:

- **No additional configuration required** - Logging starts automatically
- **Logs are stored in the database** - In the `admin_audit_trail` table
- **View logs at any time** - Visit `/admin/reports/audit-trail`
- **Logs persist until manually deleted** - Or until automatic cleanup runs (if configured)

## Recommended Sub-modules by Use Case

### For Compliance and Security Auditing
```bash
drush en admin_audit_trail_auth \
         admin_audit_trail_user \
         admin_audit_trail_user_roles \
         admin_audit_trail_node
```

### For Content Management Sites
```bash
drush en admin_audit_trail_node \
         admin_audit_trail_media \
         admin_audit_trail_taxonomy \
         admin_audit_trail_menu
```

### For Sites with Workflows
```bash
drush en admin_audit_trail_node \
         admin_audit_trail_workflows
```

### For Group-based Sites
```bash
drush en admin_audit_trail_group \
         admin_audit_trail_user
```

## Next Steps

- [Configure permissions](../2-admins/1-permissions.md) for your team
- [Learn how to view audit logs](1-viewing-logs.md)
- [Configure retention settings](../2-admins/0-configuration.md)
- [Explore sub-modules](../2-admins/2-submodules.md) to understand what each tracks

## Troubleshooting

### Module Won't Enable

**Issue**: Error when trying to enable the module

**Solutions**:
- Check that all required modules are enabled
- Clear the cache: `drush cr`
- Check PHP error logs for specific errors
- Verify database permissions

### Audit Trail Page is Empty

**Issue**: No logs appear on the audit trail page

**Solutions**:
- Ensure you've enabled at least one sub-module
- Perform an action that should be tracked (e.g., edit content)
- Refresh the page
- Check that you have the "Access Admin Audit Trail" permission

### Sub-module Missing from List

**Issue**: A sub-module doesn't appear in the module list

**Solutions**:
- Check that required dependencies are installed
- For example, Admin Audit Trail Redirect requires the Redirect module
- Install the dependency first, then the sub-module will appear
