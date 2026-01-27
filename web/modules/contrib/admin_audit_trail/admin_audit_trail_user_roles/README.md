# Admin Audit Trail User Roles

## Overview

Admin Audit Trail User Roles is a Drupal module that extends the Admin Audit Trail module by logging all user role-related activities.

Provides audit tracking for user role assignments, removals, and role management operations, helping administrators maintain detailed records of user access control changes.

## Features

- **User Role Assignment Tracking**: Logs when roles are added to user accounts
- **User Role Removal Tracking**: Logs when roles are removed from user accounts
- **Role Management Logging**: Records role creation, updates, and deletion events
- **Automatic Role Exclusion**: Automatically excludes the "authenticated" role from audit logs as it's system-assigned
- **Detailed Event Descriptions**: Provides human-readable descriptions of all role operations
- **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

- Drupal
- Admin Audit Trail module (admin_audit_trail)

## Installation

1. Download or clone this module into your Drupal `modules` directory
2. Enable the module via the Drupal admin interface or using Drush:
   ```
   drush en admin_audit_trail_user_roles
   ```
3. Ensure the Admin Audit Trail module is enabled
4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all user role events through the Admin Audit Trail system.

## Logged Events

### User Role Assignment and Removal

The module logs when individual roles are added to or removed from user accounts through the following events:

- **role_added**: Triggered when a role is assigned to a user
  - Logs the role name, user display name, and user ID
  - Excludes the "authenticated" role (system-assigned)

- **role_removed**: Triggered when a role is removed from a user
  - Logs the role name, user display name, and user ID

### Role Management

The module tracks all role administration events:

- **role_created**: Triggered when a new role is created
  - Logs the role name and role ID

- **role_updated**: Triggered when a role configuration is changed
  - Logs the role name and role ID

- **role_deleted**: Triggered when a role is deleted
  - Logs the role name and role ID

## Log Entry Details

Each audit trail entry includes:

- **Type**: Always "user_roles"
- **Operation**: The specific operation performed (role_added, role_removed, role_created, etc.)
- **Description**: A human-readable message describing the event
- **Reference (numeric)**: User ID for user role changes; 0 for role management operations
- **Reference (char)**: User/role identifier for easy searching and filtering


## Usage

All user role events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "User Roles" log type to view only role-related events
3. View detailed information about each role operation