# Admin Audit Trail User

Admin Audit Trail User is a Drupal module that extends the Admin Audit Trail module by logging all user account-related activities.

Provides audit tracking for user account creation, updates, and deletion operations, helping administrators maintain detailed security records of user account management changes.

## Features

* **User Account Creation Tracking**: Logs when new user accounts are created via `hook_user_insert()`
* **User Account Update Tracking**: Logs when user accounts are modified via `hook_user_update()`
* **User Account Deletion Tracking**: Logs when user accounts are removed via `hook_user_delete()`
* **User Identification**: Records user display name and user ID for easy reference
* **Original User Tracking**: For updates, records the original user information before changes
* **Detailed Event Descriptions**: Provides human-readable descriptions of all user account operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_user
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all user account events through the Admin Audit Trail system.

## Logged Events

### User Account Operations

The module logs when user accounts are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new user account is created via `hook_user_insert()`
  * Logs the user display name and user ID
  * Example: "John Smith (uid 42)"

* **update**: Triggered when a user account is modified via `hook_user_update()`
  * Logs the original user display name and user ID (before update)
  * Captures all user account modification events (email, name, status, roles via other modules, etc.)

* **delete**: Triggered when a user account is deleted via `hook_user_delete()`
  * Logs the user display name and user ID
  * Provides permanent record of deleted user accounts for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "user"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%name (uid %uid)" (e.g., "John Smith (uid 42)")
* **Reference (numeric)**: User ID for easy reference and filtering
* **Reference (char)**: User display name for easy searching and identification

## Usage

All user account events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "User" log type to view only user account-related events
3. View detailed information about each user account operation

## Use Cases

* **User Account Management**: Track creation, modification, and deletion of all user accounts
* **Security Monitoring**: Monitor user account changes for unauthorized modifications
* **Accountability**: Track which administrators created, modified, or deleted user accounts
* **Compliance Requirements**: Maintain detailed records of user account activity for regulatory compliance (SOC 2, HIPAA, GDPR, etc.)
* **Access Control**: Review account modifications to understand access control changes
* **Incident Investigation**: Review user account change history during security incident investigations
* **Data Governance**: Maintain historical records of user account lifecycle for archival and audit purposes
* **Offboarding**: Track user account deletion records for employee offboarding verification
