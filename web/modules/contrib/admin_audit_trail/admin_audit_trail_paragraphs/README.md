# Admin Audit Trail Paragraphs

Admin Audit Trail Paragraphs is a Drupal module that extends the Admin Audit Trail module by logging all paragraph-related activities.

Provides audit tracking for paragraph creation, updates, and deletion operations, helping administrators maintain detailed records of paragraph management changes.

## Features

* **Paragraph Creation Tracking**: Logs when new paragraphs are created in the system
* **Paragraph Update Tracking**: Logs when paragraph content and properties are modified
* **Paragraph Deletion Tracking**: Logs when paragraphs are removed from the system
* **Detailed Event Descriptions**: Provides human-readable descriptions of all paragraph operations with bundle type and parent entity information
* **Paragraph Identification**: Records paragraph ID for easy reference and tracking
* **Parent Entity Tracking**: Records parent entity type and ID for context (e.g., node-123)
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Paragraphs module (`paragraphs`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_paragraphs
```

3. Ensure the Admin Audit Trail and Paragraphs modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all paragraph events through the Admin Audit Trail system.

## Logged Events

### Paragraph Operations

The module logs when paragraphs are created, modified, or deleted through the following events:

* **paragraph_created**: Triggered when a new paragraph is created
  * Logs the paragraph bundle type, parent entity reference, and paragraph ID
  * Records who created the paragraph and when

* **paragraph_updated**: Triggered when a paragraph content is changed
  * Logs the paragraph bundle type, parent entity reference, and paragraph ID
  * Captures all paragraph modification events

* **paragraph_deleted**: Triggered when a paragraph is deleted
  * Logs the paragraph bundle type, parent entity reference, and paragraph ID
  * Provides permanent record of deleted paragraphs for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "paragraph"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: A human-readable message describing the event (e.g., "Hero: (parent: node-42)")
* **Reference (numeric)**: Paragraph entity ID for easy reference and filtering
* **Reference (char)**: Empty for paragraphs (they have no direct title, parent info is in description)

## Usage

All paragraph events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Paragraph" log type to view only paragraph-related events
3. View detailed information about each paragraph operation

## Use Cases

* **Content Management Compliance**: Maintain detailed records of all paragraph edits for regulatory compliance and audit purposes
* **Accountability**: Track which administrator created, modified, or deleted specific paragraphs
* **Content Structure Changes**: Monitor paragraph modifications to maintain awareness of content structure evolution
* **Troubleshooting**: Identify when content paragraphs were changed to help resolve structural or display issues
* **Incident Investigation**: Review paragraph change history during content quality or access control incident investigations
* **Data Governance**: Maintain historical records of paragraph lifecycle for archival and audit purposes
