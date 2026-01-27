# Admin Audit Trail Redirect

Admin Audit Trail Redirect is a Drupal module that extends the Admin Audit Trail module by logging all redirect-related activities.

Provides audit tracking for redirect creation, updates, and deletion operations, helping administrators maintain detailed records of URL redirect management and site navigation changes.

## Features

* **Redirect Creation Tracking**: Logs when new redirects are created via `hook_redirect_insert()`
* **Redirect Modification Tracking**: Logs when redirects are modified via `hook_redirect_update()`
* **Redirect Deletion Tracking**: Logs when redirects are deleted via `hook_redirect_delete()`
* **Source URL Tracking**: Records the source (from) URL for identification
* **Destination URL Tracking**: Records the destination (to) URL for context
* **HTTP Status Code Tracking**: Logs the redirect HTTP status code (301, 302, 303, 307, etc.)
* **Redirect ID Tracking**: Records the unique redirect entity ID for searching
* **Detailed Event Descriptions**: Provides human-readable descriptions of all redirect operations
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Redirect module (`redirect:redirect`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_redirect
```

3. Ensure the Admin Audit Trail and Redirect modules are enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all redirect events through the Admin Audit Trail system.

## Logged Events

### Redirect Operations

The module logs when redirects are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new redirect is created via `hook_redirect_insert()`
  * Logs source and destination URLs with HTTP status code
  * Example: "src: /old-page (301) to: /new-page"

* **update**: Triggered when a redirect is modified via `hook_redirect_update()`
  * Logs source and destination URLs with HTTP status code
  * Captures all redirect modification events

* **delete**: Triggered when a redirect is deleted via `hook_redirect_delete()`
  * Logs source and destination URLs with HTTP status code
  * Provides permanent record of deleted redirects for compliance

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "redirect"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "src: %source (%status) to: %dest" (e.g., "src: /old-page (301) to: /new-page")
* **Reference (numeric)**: Redirect entity ID for easy reference and filtering
* **Reference (char)**: Source URL for easy searching and identification

## Usage

All redirect events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Redirect" log type to view only redirect-related events
3. View detailed information about each redirect operation

## Use Cases

* **URL Redirect Management**: Track creation, modification, and deletion of all site redirects
* **Site Structure Changes**: Monitor redirects created during site restructuring and URL changes
* **SEO Tracking**: Review redirect modifications to understand SEO implications
* **Accountability**: Track which administrators created, modified, or deleted specific redirects
* **Broken Link Resolution**: Review redirect history to understand how broken links were resolved
* **Compliance Requirements**: Maintain detailed records of redirect activity for regulatory compliance
* **Content Migration**: Track redirects created during content migration projects
* **Data Governance**: Maintain historical records of redirect lifecycle for archival and audit purposes
