# Admin Audit Trail Entityqueue

Admin Audit Trail Entityqueue is a Drupal module that extends the Admin Audit Trail module by logging all entityqueue-related activities.

Provides comprehensive audit tracking for entityqueue (entity queues) creation, updates, deletion, and entity subqueue management operations, helping administrators maintain detailed records of queue structure and content ordering changes.

## Features

* **Entity Queue Creation Tracking**: Logs when new entity queues are created via `hook_entity_queue_insert()`
* **Entity Queue Modification Tracking**: Logs when entity queues are modified via `hook_entity_queue_update()`
* **Entity Queue Deletion Tracking**: Logs when entity queues are deleted via `hook_entity_queue_predelete()`
* **Entity Subqueue Creation Tracking**: Logs when subqueues are created via `hook_entity_subqueue_insert()`
* **Entity Subqueue Modification Tracking**: Logs when subqueues are modified via `hook_entity_subqueue_update()`
* **Entity Subqueue Deletion Tracking**: Logs when subqueues are deleted via `hook_entity_subqueue_predelete()`
* **Queue Type Tracking**: Records the entityqueue bundle type
* **Queue Title Tracking**: Logs the queue/subqueue title for identification
* **Detailed Event Descriptions**: Provides human-readable descriptions of all entityqueue operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Entityqueue module (`entityqueue:entityqueue`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_entityqueue
```

3. Ensure the Admin Audit Trail and Entityqueue modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all entityqueue and entity subqueue events through the Admin Audit Trail system.

## Logged Events

### Entity Queue Operations

The module logs when entity queues are created, modified, or deleted:

* **insert**: Triggered when a new entity queue is created via `hook_entity_queue_insert()`
  * Logs the queue bundle type and queue title
  * Example: "Featured Content: Featured Articles - inserted"

* **update**: Triggered when an entity queue is modified via `hook_entity_queue_update()`
  * Logs the queue bundle type and queue title
  * Captures all entity queue modification events

* **delete**: Triggered when an entity queue is deleted via `hook_entity_queue_predelete()`
  * Logs the queue bundle type and queue title
  * Provides permanent record of deleted queues for compliance

### Entity Subqueue Operations

The module tracks all entity subqueue management:

* **insert**: Triggered when a new subqueue is created via `hook_entity_subqueue_insert()`
  * Logs the subqueue bundle type and subqueue title
  * Example: "Featured Articles: Seasonal Picks - inserted"

* **update**: Triggered when a subqueue is modified via `hook_entity_subqueue_update()`
  * Logs the subqueue bundle type and subqueue title
  * Captures all subqueue modification events

* **delete**: Triggered when a subqueue is deleted via `hook_entity_subqueue_predelete()`
  * Logs the subqueue bundle type and subqueue title
  * Provides permanent record of deleted subqueues for compliance

## Log Entry Details

### Entity Queue Entries

Each audit trail entry includes:

* **Type**: Always "entity_queue"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%type: %title - [operation]" (e.g., "Featured Content: Featured Articles - inserted")
* **Reference (char)**: Queue title for easy searching and identification

### Entity Subqueue Entries

Each audit trail entry includes:

* **Type**: Always "entity_subqueue"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%type: %title - [operation]" (e.g., "Featured Articles: Seasonal Picks - inserted")
* **Reference (char)**: Subqueue title for easy searching and identification

## Usage

All entityqueue and entity subqueue events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Entityqueue" or "Entity Subqueue" log type to view only queue-related events
3. View detailed information about each queue operation

## Use Cases

* **Queue Management**: Track creation, modification, and deletion of all entity queues and subqueues
* **Content Curation**: Monitor changes to featured content queues and content ordering
* **Accountability**: Track which administrators created, modified, or deleted specific queues
* **Content Organization**: Review queue modifications to understand featured content evolution
* **Promotion Tracking**: Monitor featured content queue changes for marketing and promotional purposes
* **Compliance Requirements**: Maintain detailed records of queue activity for regulatory compliance
* **Content Audit**: Review queue change history during content management and curation reviews
* **Data Governance**: Maintain historical records of queue lifecycle for archival and audit purposes
