# Admin Audit Trail File

Admin Audit Trail File is a Drupal module that extends the Admin Audit Trail module by logging all file entity-related activities.

Provides audit tracking for file upload, modification, and deletion operations, helping administrators maintain detailed records of file management and access changes.

## Features

* **File Upload Tracking**: Logs when new files are uploaded/created via `hook_file_insert()`
* **File Modification Tracking**: Logs when file entities are modified via `hook_file_update()`
* **File Deletion Tracking**: Logs when files are deleted via `hook_file_delete()`
* **File URI Tracking**: Records the complete file URI path for file identification
* **File Name Tracking**: Logs the original filename for easy reference
* **File ID Tracking**: Records the unique file entity ID for searching
* **Detailed Event Descriptions**: Provides human-readable descriptions of all file operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_file
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all file entity events through the Admin Audit Trail system.

## Logged Events

### File Entity Operations

The module logs when file entities are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new file is uploaded/created via `hook_file_insert()`
  * Logs the file URI and filename
  * Example: "public://2024-11/document.pdf"

* **update**: Triggered when a file entity is modified via `hook_file_update()`
  * Logs the file URI and filename
  * Captures all file entity modification events

* **delete**: Triggered when a file is deleted via `hook_file_delete()`
  * Logs the file URI and filename
  * Provides permanent record of deleted files for compliance and recovery purposes

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "file"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: The complete file URI path (e.g., "public://2024-11/document.pdf")
* **Reference (numeric)**: File entity ID for easy reference and filtering
* **Reference (char)**: Original filename for easy searching and identification

## Usage

All file entity events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "File" log type to view only file-related events
3. View detailed information about each file operation

## Use Cases

* **File Management**: Track upload, modification, and deletion of all file entities
* **Media Library Tracking**: Monitor file additions and removals from the media library
* **Security Auditing**: Track file access and modifications for security compliance
* **Accountability**: Monitor which users uploaded or deleted specific files
* **Compliance Requirements**: Maintain detailed records of file activity for regulatory compliance (HIPAA, GDPR, etc.)
* **File Recovery**: Review file deletion history for recovery assistance
* **Data Governance**: Maintain historical records of file lifecycle for archival and audit purposes
* **Storage Management**: Track file uploads to understand storage usage and changes
