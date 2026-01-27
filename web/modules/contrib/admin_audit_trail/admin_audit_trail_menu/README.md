# Admin Audit Trail Menu

Admin Audit Trail Menu is a Drupal module that extends the Admin Audit Trail module by logging all menu-related activities.

Provides comprehensive audit tracking for menu creation, updates, deletion, and menu link management operations, helping administrators maintain detailed records of navigation structure changes.

## Features

* **Menu Creation Tracking**: Logs when new menus are created via `hook_menu_insert()`
* **Menu Update Tracking**: Logs when menu configurations are modified via `hook_menu_update()`
* **Menu Deletion Tracking**: Logs when menus are removed via `hook_menu_delete()`
* **Menu Link Creation Tracking**: Logs when menu links are created via `hook_menu_link_content_insert()`
* **Menu Link Update Tracking**: Logs when menu links are modified via `hook_menu_link_content_update()`
* **Menu Link Deletion Tracking**: Logs when menu links are removed via `hook_menu_link_content_delete()`
* **Link URL Tracking**: Records the link destination URL for context
* **Menu Identification**: Records menu ID and title for easy reference
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_menu
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all menu and menu link events through the Admin Audit Trail system.

## Logged Events

### Menu Operations

The module logs when menus are created, modified, or deleted through entity hooks:

* **insert**: Triggered when a new menu is created via `hook_menu_insert()`
  * Logs the menu title and menu machine name
  * Example: "Main Menu (main)"

* **update**: Triggered when a menu is modified via `hook_menu_update()`
  * Logs the menu title and menu machine name
  * Captures all menu configuration changes

* **delete**: Triggered when a menu is deleted via `hook_menu_delete()`
  * Logs the menu title and menu machine name
  * Provides permanent record of deleted menus for compliance

### Menu Link Operations

The module tracks all menu link management:

* **link insert**: Triggered when a menu link is created via `hook_menu_link_content_insert()`
  * Logs the link title, link ID, and destination URL
  * Example: "About Us (123), /about"

* **link update**: Triggered when a menu link is modified via `hook_menu_link_content_update()`
  * Logs the link title, link ID, and destination URL
  * Captures all menu link configuration changes

* **link delete**: Triggered when a menu link is deleted via `hook_menu_link_content_delete()`
  * Logs the link title, link ID, and destination URL
  * Provides permanent record of deleted links for compliance

## Log Entry Details

### Menu Entries

Each audit trail entry includes:

* **Type**: Always "menu"
* **Operation**: The specific operation performed (insert, update, delete)
* **Description**: Format "%title (%name)" (e.g., "Main Menu (main)")
* **Reference (char)**: Menu machine name for easy searching

### Menu Link Entries

Each audit trail entry includes:

* **Type**: Always "menu"
* **Operation**: The specific operation performed (link insert, link update, link delete)
* **Description**: Format "%title (%id), %path" (e.g., "About Us (123), /about")
* **Reference (numeric)**: Menu link entity ID for easy reference and filtering
* **Reference (char)**: Menu name for easy searching and identification

## Usage

All menu and menu link events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Menu" log type to view only menu-related events
3. View detailed information about each menu operation

## Use Cases

* **Navigation Management**: Track changes to site navigation structure and menu links
* **Accountability**: Monitor which administrators created, modified, or deleted specific menus and links
* **Change Tracking**: Review menu modification history to understand navigation evolution
* **Compliance Requirements**: Maintain detailed records of menu activity for regulatory compliance
* **Incident Investigation**: Review menu change history during navigation issue investigations
* **Data Governance**: Maintain historical records of menu and link lifecycle for archival purposes
