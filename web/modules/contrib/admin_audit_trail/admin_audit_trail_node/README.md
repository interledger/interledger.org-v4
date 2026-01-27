# Admin Audit Trail Node

Admin Audit Trail Node is a Drupal module that extends the Admin Audit Trail module by logging all node-related activities.

Provides comprehensive audit tracking for node creation, updates, deletion, and multilingual translation operations, helping administrators maintain detailed records of content management changes.

## Features

* **Node Creation Tracking**: Logs when new nodes (content) are created via `hook_node_insert()`
* **Node Update Tracking**: Logs when nodes are modified via `hook_node_update()`
* **Node Deletion Tracking**: Logs when nodes are removed via `hook_node_delete()`
* **Node Translation Creation Tracking**: Logs when node translations are created via `hook_entity_translation_insert()`
* **Node Translation Deletion Tracking**: Logs when node translations are removed via `hook_entity_translation_delete()`
* **Content Type Tracking**: Records the node content type for categorization
* **Node Title Tracking**: Logs the node title for easy identification
* **Detailed Event Descriptions**: Provides human-readable descriptions of all node operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_node
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all node events through the Admin Audit Trail system.

## Logged Events

### Node Content Operations

The module logs when nodes are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new node is created via `hook_node_insert()`
  * Logs the node content type and node title
  * Example: "Article: New Product Launch Announcement"

* **update**: Triggered when a node is modified via `hook_node_update()`
  * Logs the node content type and node title
  * Captures all node modification events

* **delete**: Triggered when a node is deleted via `hook_node_delete()`
  * Logs the node content type and node title
  * Provides permanent record of deleted nodes for compliance

### Node Translation Operations

The module tracks multilingual node translations:

* **translation insert**: Triggered when a new translation of a node is created via `hook_entity_translation_insert()`
  * Logs the node content type and node title
  * Example: "Article: New Product Launch Announcement (Spanish translation)"

* **translation delete**: Triggered when a node translation is removed via `hook_entity_translation_delete()`
  * Logs the node content type and node title
  * Provides record of deleted translations for multilingual content management

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "node"
* **Operation**: The specific operation performed (insert, update, delete, translation insert, translation delete)
* **Description**: Format "%type: %title" (e.g., "Article: New Product Launch Announcement")
* **Reference (numeric)**: Node ID (nid) for easy reference and filtering
* **Reference (char)**: Node title for easy searching and identification

## Usage

All node events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Node" log type to view only content-related events
3. View detailed information about each node operation

## Use Cases

* **Content Management**: Track creation, modification, and deletion of all website content
* **Accountability**: Monitor which editors and administrators created, modified, or deleted specific content
* **Content Quality**: Review node modification history to understand content evolution and edits
* **Multilingual Content**: Track translation creation and deletion for multilingual sites
* **Compliance Requirements**: Maintain detailed records of content activity for regulatory compliance (HIPAA, GDPR, etc.)
* **Incident Investigation**: Review node change history during content-related investigations
* **Data Governance**: Maintain historical records of content lifecycle for archival and audit purposes
