# Admin Audit Trail

![Admin Audit Trail](logo.png)

A comprehensive Drupal audit logging module that tracks and records all content management and administrative actions within your website. This module logs specific events that you want to review and maintain for compliance, accountability, and security purposes.

## Overview

The Admin Audit Trail module automatically logs events performed by users through the Drupal administrative interface and system operations. All logged events are saved in the database and can be viewed on the page **admin/reports/audit-trail**. You can use this module to:

* Track the number of times CUD (Create, Update & Delete) operations are performed
* Monitor which users made specific changes to your website
* Maintain compliance audit trails for regulatory requirements (HIPAA, GDPR, SOC 2, etc.)
* Investigate when and how content was modified
* Review administrative actions for security and accountability

## Supported Sub-modules

The Admin Audit Trail module includes comprehensive tracking through specialized sub-modules:

### Authentication & User Management
- **Admin Audit Trail User Authentication** - Logs user logins, logouts, password resets, and failed login attempts
- **Admin Audit Trail User** - Logs user account creation, updates, and deletion
- **Admin Audit Trail User Roles** - Logs user role assignments, removals, and role management

### Content Management
- **Admin Audit Trail Node** - Logs node (content) creation, updates, deletion, and translations
- **Admin Audit Trail Comment** - Logs comment creation, updates, and deletion
- **Admin Audit Trail Block Content** - Logs custom block creation, updates, and deletion
- **Admin Audit Trail Media** - Logs media asset (images, videos, documents) creation, updates, and deletion
- **Admin Audit Trail File** - Logs file entity creation, updates, and deletion

### Site Structure & Organization
- **Admin Audit Trail Menu** - Logs menu and menu link creation, updates, and deletion
- **Admin Audit Trail Taxonomy** - Logs taxonomy vocabulary and term creation, updates, and deletion
- **Admin Audit Trail Redirect** - Logs URL redirect creation, updates, and deletion

### Advanced Features
- **Admin Audit Trail Workflows** - Logs content workflow state transitions and moderation changes
- **Admin Audit Trail Entityqueue** - Logs entity queue and subqueue creation, updates, and deletion
- **Admin Audit Trail Paragraph** - Logs paragraph entity creation, updates, and deletion
- **Admin Audit Trail Group** - Logs group entity creation, updates, and deletion (requires Group module)

## Key Features

* **Comprehensive Logging**: Tracks both entity operations (insert, update, delete) and user actions (login, logout, form submissions)
* **Easy Audit Trail Review**: View all logged events on a dedicated audit trail page with filtering capabilities
* **User Identification**: Every log entry records which user performed the action
* **Detailed Descriptions**: Human-readable descriptions of each action with relevant entity details
* **Extensible Architecture**: Easy to extend with custom events through the audit trail API
* **Zero Configuration**: Sub-modules automatically begin logging once enabled
* **Compliance Ready**: Maintains permanent records for regulatory compliance auditing

## Event Log Tracking

The event log tracking system is easily extensible. Custom events can be added by:

1. Implementing `hook_admin_audit_trail_handlers()` to register custom event handlers
2. Creating event log callbacks to capture and log specific events
3. Using `admin_audit_trail_insert()` to record custom audit trail events

For more information on extending the module with custom events, see the [project documentation](https://www.drupal.org/project/admin_audit_trail).

## Project Resources

For a full description of the module, visit the
[project page](https://www.drupal.org/project/admin_audit_trail).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/admin_audit_trail).


## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Viewing Audit Logs](#viewing-audit-logs)
- [Enabling Sub-modules](#enabling-sub-modules)
- [Maintainers](#maintainers)

## Requirements

* Drupal
* Core Drupal modules required for specific sub-modules:
  - Drupal Comment module (for Admin Audit Trail Comment)
  - Drupal Media module (for Admin Audit Trail Media)
  - Drupal Workflows module (for Admin Audit Trail Workflows)
  - Contributed modules:
    - Redirect module (for Admin Audit Trail Redirect)
    - Entityqueue module (for Admin Audit Trail Entityqueue)
    - Group module (for Admin Audit Trail Group)

## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

### Quick Install via Drush

```bash
drush en admin_audit_trail
drush en admin_audit_trail_node admin_audit_trail_user admin_audit_trail_auth
```

### Manual Installation

1. Download the module to your `modules` directory
2. Navigate to Administration > Extend (admin/modules)
3. Enable the "Admin Audit Trail" module
4. Enable any desired sub-modules for specific entity types
5. Clear the site cache

## Configuration

The Admin Audit Trail module requires minimal configuration to get started. While the module begins logging events immediately upon enabling, you can customize two key settings through the Admin Audit Trail Settings page.

### Accessing the Settings Page

To access the Admin Audit Trail settings:

1. Navigate to **Administration > Configuration > Development > Admin Audit Trails Settings** (path: `/admin/config/development/audit-trail/settings`)
2. Or navigate to **Administration > Reports > Audit Trail**, then click the "Settings" link

**Required Permission:** `configure admin audit trail`

### Available Settings

#### 1. Filters Expanded

**Setting:** `Filters Expanded`

**Description:** Controls whether the audit trail filter section is expanded or collapsed by default when viewing the audit trail report.

**Options:**
* **Unchecked (Collapsed):** The filter section will be collapsed, giving more screen space to the audit log table
* **Checked (Expanded):** The filter section will be open by default, allowing users to immediately apply filters

**Default:** Checked (Expanded)

**Use Case:** Enable this if your team frequently uses filters to search the audit trail. Disable it if you prefer a cleaner interface focused on viewing the logs.

#### 2. Audit Trail Log Messages to Keep

**Setting:** `Audit Trail log messages to keep`

**Description:** Sets the maximum number of audit trail log entries to retain in the database. Older entries will be automatically removed during cron maintenance tasks.

**Available Options:**
* **All (0)** - Keep all audit trail records indefinitely (no automatic cleanup)
* **100** - Keep only the 100 most recent entries
* **500** - Keep only the 500 most recent entries
* **1,000** - Keep only the 1,000 most recent entries
* **3,000** - Keep only the 3,000 most recent entries
* **10,000** - Keep only the 10,000 most recent entries
* **100,000** - Keep only the 100,000 most recent entries

**Default:** All (unlimited retention)

**Important Notes:**
* This setting requires a **cron maintenance task** to execute the cleanup
* The database cleanup runs on the schedule defined by your cron configuration
* Cron runs must be configured on your Drupal site for this setting to take effect
* The oldest entries are automatically removed when the limit is reached
* If you select "All", the audit trail will grow indefinitely, which may impact database performance over time

**Use Case Examples:**

* **Compliance-Heavy Organizations:** Select "All" to retain complete audit trails for regulatory compliance (HIPAA, GDPR, SOC 2, etc.)
* **High-Traffic Sites:** Select a limit like 10,000 to manage database size while keeping recent activity records
* **Development Sites:** Select 1,000 to keep disk space minimal while having enough recent history for troubleshooting
* **Small Sites:** Select 100,000 for a good balance of retention and performance

### Saving Settings

1. Adjust the settings as needed
2. Click the **Save configuration** button at the bottom of the form
3. A confirmation message will display when settings are successfully saved

### Reverting to Defaults

The default settings are:
* Filters Expanded: **Checked**
* Audit Trail log messages to keep: **All** (0 - unlimited)

## Permissions

The Admin Audit Trail module defines two permissions for controlling access to audit trail features:

### Available Permissions

#### 1. Access Admin Audit Trail
**Permission Name:** `access admin audit trail`

**Description:** Allows users to view and search the audit trail log entries.

**Access:** Administration > People > Permissions (path: `/admin/people/permissions`)

**Use:** Grant this permission to administrators, security personnel, and any staff who need to review audit trail logs.

#### 2. Configure Admin Audit Trail
**Permission Name:** `configure admin audit trail`

**Description:** Allows users to access and modify the Admin Audit Trail settings page (filters, log retention limits, etc.).

**Access:** Administration > People > Permissions (path: `/admin/people/permissions`)

**Use:** Grant this permission only to site administrators who should manage module configuration and settings.

### Recommended Permission Assignments

**Admin Role:**
- ✓ Access Admin Audit Trail
- ✓ Configure Admin Audit Trail

**Auditor / Security Role:**
- ✓ Access Admin Audit Trail
- ✗ Configure Admin Audit Trail (read-only access)

**Content Editor Role:**
- ✗ Access Admin Audit Trail
- ✗ Configure Admin Audit Trail (no audit trail access)

## Usage

### Viewing Audit Logs

1. Navigate to **Administration > Reports > Audit Trail** (path: `admin/reports/events-track`)
2. Browse the audit trail log entries
3. Filter by log type, operation, or user as needed
4. Click on any entry to view detailed information

### Enabling Sub-modules

Each sub-module handles a specific entity type:

1. Navigate to **Administration > Extend** (admin/modules)
2. Search for "Admin Audit Trail"
3. Enable the desired sub-modules:
   - **Admin Audit Trail User Authentication** - Track logins, logouts, password resets
   - **Admin Audit Trail User** - Track user account management
   - **Admin Audit Trail Node** - Track content (nodes)
   - **Admin Audit Trail Comment** - Track comments
   - And more...
4. Click "Install" or save the modules page
5. Clear the cache

### What Gets Logged

Each sub-module logs specific events:

* **Create operations** - When new entities (content, users, menus, etc.) are created
* **Update operations** - When existing entities are modified
* **Delete operations** - When entities are removed from the system
* **Special events** - Login/logout events, workflow state changes, etc.

### Log Entry Information

Each audit trail log entry includes:

* **Type** - The entity type being logged (node, user, menu, etc.)
* **Operation** - What action was performed (insert, update, delete, login, etc.)
* **Description** - Human-readable description of the action
* **User** - Who performed the action
* **Timestamp** - When the action occurred
* **IP Address** - IP address of the user who made the change
* **Path** - The page/path where the action was performed


## Maintainers

- Rajab Natshah - [Rajab Natshah](https://www.drupal.org/u/rajab-natshah)
- Mohammed Razem - [Mohammed J. Razem](https://www.drupal.org/u/mohammed-j-razem)