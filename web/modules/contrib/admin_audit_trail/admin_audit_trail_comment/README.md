# Admin Audit Trail Comment

Admin Audit Trail Comment is a Drupal module that extends the Admin Audit Trail module by logging all comment-related activities.

Provides audit tracking for comment creation, updates, and deletion operations, helping administrators maintain detailed records of comment management and moderation changes.

## Features

* **Comment Creation Tracking**: Logs when new comments are created via `hook_comment_insert()`
* **Comment Update Tracking**: Logs when comments are modified via `hook_comment_update()`
* **Comment Deletion Tracking**: Logs when comments are removed via `hook_comment_delete()`
* **Comment Type Tracking**: Records the comment type ID for categorization
* **Comment Subject Tracking**: Logs the comment subject for easy identification
* **Detailed Event Descriptions**: Provides human-readable descriptions of all comment operations
* **Comment Identification**: Records comment entity ID and subject for easy reference and tracking
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Drupal Comment module (`drupal:comment`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_comment
```

3. Ensure the Admin Audit Trail and Comment modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all comment events through the Admin Audit Trail system.

## Logged Events

### Comment Operations

The module logs when comments are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new comment is created via `hook_comment_insert()`
  * Logs the comment type ID and comment subject
  * Example: "comment: This is a helpful comment"

* **update**: Triggered when a comment is modified via `hook_comment_update()`
  * Logs the comment type ID and comment subject
  * Captures all comment modification events

* **delete**: Triggered when a comment is deleted via `hook_comment_delete()`
  * Logs the comment type ID and comment subject
  * Provides permanent record of deleted comments for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "comment"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%type: %title" (e.g., "comment: This is a helpful comment")
* **Reference (numeric)**: Comment entity ID for easy reference and filtering
* **Reference (char)**: Comment subject for easy searching and identification

## Usage

All comment events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Comment" log type to view only comment-related events
3. View detailed information about each comment operation

## Use Cases

* **Moderation Tracking**: Track comment creation and modification for moderation purposes
* **Accountability**: Monitor which users created, modified, or deleted specific comments
* **Content Quality**: Review comment history to identify quality issues or spam
* **Compliance Requirements**: Maintain detailed records of comment activity for regulatory compliance
* **Incident Investigation**: Review comment change history during content quality investigations
* **Data Governance**: Maintain historical records of comment lifecycle for archival purposes
