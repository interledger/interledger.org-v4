# Admin Audit Trail Group

Admin Audit Trail Group is a Drupal module that extends the Admin Audit Trail module by logging all group-related activities.

Provides audit tracking for group creation, updates, and deletion operations, helping administrators maintain detailed records of group management changes.

## Features

* **Group Creation Tracking**: Logs when new groups are created in the system
* **Group Update Tracking**: Logs when group configurations and properties are modified
* **Group Deletion Tracking**: Logs when groups are removed from the system
* **Detailed Event Descriptions**: Provides human-readable descriptions of all group operations with bundle type and group title
* **Group Identification**: Records both group ID and title for easy reference and tracking
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Group module (`group`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_group
```

3. Ensure the Admin Audit Trail and Group modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all group events through the Admin Audit Trail system.

## Logged Events

### Group Operations

The module logs when groups are created, modified, or deleted through the following events:

* **group_created**: Triggered when a new group is created
  * Logs the group bundle type, group title, and group ID
  * Records who created the group and when

* **group_updated**: Triggered when a group configuration is changed
  * Logs the group bundle type, group title, and group ID
  * Captures all group modification events

* **group_deleted**: Triggered when a group is deleted
  * Logs the group bundle type, group title, and group ID
  * Provides permanent record of deleted groups for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "group"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: A human-readable message describing the event (e.g., "Department: Engineering")
* **Reference (numeric)**: Group entity ID for easy reference and filtering
* **Reference (char)**: Group title for easy searching and identification

## Usage

All group events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Group" log type to view only group-related events
3. View detailed information about each group operation

## Use Cases

* **Organizational Compliance**: Maintain detailed records of all group management changes for regulatory compliance and audit purposes
* **Accountability**: Track which administrator created, modified, or deleted specific groups
* **Organizational Structure Changes**: Monitor group creation and deletion to maintain awareness of organizational structure evolution
* **Troubleshooting**: Identify when organizational groups were changed to help resolve structural or permission issues
* **Incident Investigation**: Review group change history during security or access control incident investigations
* **Data Governance**: Maintain historical records of group lifecycle for archival and audit purposes
