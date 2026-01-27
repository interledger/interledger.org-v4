# Admin Audit Trail Block Content

Admin Audit Trail Block Content is a Drupal module that extends the Admin Audit Trail module by logging all block content-related activities.

Provides audit tracking for block content creation, updates, and deletion operations, helping administrators maintain detailed records of block management changes.

## Features

* **Block Content Creation Tracking**: Logs when new blocks are created via `hook_block_content_insert()`
* **Block Content Update Tracking**: Logs when block content and properties are modified via `hook_block_content_update()`
* **Block Content Deletion Tracking**: Logs when blocks are removed via `hook_block_content_delete()`
* **Bundle Type Tracking**: Records the block bundle type (e.g., "Basic Block", "Hero Block")
* **Detailed Event Descriptions**: Provides human-readable descriptions with bundle type and block title
* **Block Identification**: Records block entity ID and title for easy reference and tracking
* **Entity Reference**: Stores the full block entity object for advanced audit purposes
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_block_content
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all block content events through the Admin Audit Trail system.

## Logged Events

### Block Content Operations

The module logs when blocks are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new block is created via `hook_block_content_insert()`
  * Logs the block bundle type and block title
  * Example: "Basic Block: Homepage Hero"

* **update**: Triggered when a block content is changed via `hook_block_content_update()`
  * Logs the block bundle type and block title
  * Captures all block modification events

* **delete**: Triggered when a block is deleted via `hook_block_content_delete()`
  * Logs the block bundle type and block title
  * Provides permanent record of deleted blocks for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "block_content"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%bundle: %title" (e.g., "Basic Block: Homepage Hero")
* **Reference (numeric)**: Block entity ID for easy reference and filtering
* **Reference (char)**: Block title for easy searching and identification
* **Entity Reference**: Full block_content entity object reference

## Usage

All block content events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Block Content" log type to view only block-related events
3. View detailed information about each block operation

## Use Cases

* **Content Management Compliance**: Maintain detailed records of all block edits for regulatory compliance and audit purposes
* **Accountability**: Track which administrator created, modified, or deleted specific blocks
* **Block Management Changes**: Monitor block modifications to maintain awareness of page layout evolution
* **Troubleshooting**: Identify when blocks were changed to help resolve display or functionality issues
* **Incident Investigation**: Review block change history during content quality or access control incident investigations
* **Data Governance**: Maintain historical records of block lifecycle for archival and audit purposes
