# Admin Audit Trail Media

Admin Audit Trail Media is a Drupal module that extends the Admin Audit Trail module by logging all media entity-related activities.

Provides audit tracking for media (images, videos, audio, documents) creation, updates, and deletion operations, helping administrators maintain detailed records of media asset management changes.

## Features

* **Media Creation Tracking**: Logs when new media assets are created via `hook_media_insert()`
* **Media Modification Tracking**: Logs when media assets are modified via `hook_media_update()`
* **Media Deletion Tracking**: Logs when media assets are deleted via `hook_media_delete()`
* **Media Type Tracking**: Records the media bundle type (image, video, audio, document, etc.)
* **Media Name Tracking**: Logs the media entity name/label for identification
* **Revision Log Tracking**: Captures revision log messages when provided
* **Media ID Tracking**: Records the unique media entity ID for searching
* **Detailed Event Descriptions**: Provides human-readable descriptions of all media operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Media module (`drupal:media`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_media
```

3. Ensure the Admin Audit Trail and Media modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all media entity events through the Admin Audit Trail system.

## Logged Events

### Media Operations

The module logs when media assets are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new media asset is created via `hook_media_insert()`
  * Logs the media type and media name
  * Includes revision log message if provided
  * Example: "Image: Company Logo: Updated branding"

* **update**: Triggered when a media asset is modified via `hook_media_update()`
  * Logs the media type and media name
  * Includes revision log message if provided
  * Captures all media modification events

* **delete**: Triggered when a media asset is deleted via `hook_media_delete()`
  * Logs the media type and media name
  * Includes revision log message if provided
  * Provides permanent record of deleted media for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "media"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%title (%type)%revision_log" (e.g., "Company Logo (Image): Updated branding")
* **Reference (numeric)**: Media entity ID for easy reference and filtering
* **Reference (char)**: Media label for easy searching and identification

## Usage

All media entity events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Media" log type to view only media-related events
3. View detailed information about each media operation

## Use Cases

* **Media Asset Management**: Track creation, modification, and deletion of all media assets
* **Digital Asset Library**: Monitor changes to branded images, videos, and documents
* **Accountability**: Track which users uploaded or deleted specific media assets
* **Version Control**: Review revision logs to understand media asset evolution
* **Copyright Management**: Maintain records of media additions for rights and licensing tracking
* **Compliance Requirements**: Maintain detailed records of media activity for regulatory compliance
* **Data Governance**: Maintain historical records of media asset lifecycle for archival and audit purposes
* **Content Organization**: Review media modifications to understand asset management changes
