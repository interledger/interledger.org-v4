# Admin Audit Trail Workflows

Admin Audit Trail Workflows is a Drupal module that extends the Admin Audit Trail module by logging all workflow state change activities.

Provides audit tracking for content moderation and workflow state transitions on nodes with workflow (moderation state) fields enabled, helping administrators maintain detailed records of content publication and approval workflow changes.

## Features

* **Workflow State Creation Tracking**: Logs when new nodes with workflow states are created via `hook_node_insert()`
* **Workflow State Transition Tracking**: Logs when nodes transition between workflow states via `hook_node_update()`
* **State Change Capture**: Records both old and new workflow states for change tracking
* **Content Type Tracking**: Records the node content type for categorization
* **Node Identification**: Logs the node title and ID for easy reference
* **Detailed State Descriptions**: Provides human-readable descriptions of workflow state changes
* **Conditional Tracking**: Only logs nodes that have moderation_state fields enabled
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)
* Drupal Workflows module (`drupal:workflows`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_workflows
```

3. Ensure the Admin Audit Trail and Workflows modules are enabled

4. Enable moderation (workflow) on the desired content types

5. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all workflow state changes on nodes with moderation_state fields through the Admin Audit Trail system.

### Content Types with Workflows

This module only logs activity on content types that have the moderation_state field enabled. To enable workflows:

1. Navigate to Administration > Structure > Content Types
2. Edit the desired content type
3. Enable "Enable moderation state" in the Publishing options

## Logged Events

### Workflow State Operations

The module logs when nodes with workflow states are created or have their states changed:

* **insert**: Triggered when a new node with a workflow state is created via `hook_node_insert()`
  * Logs the node content type and node title
  * Records the initial workflow state
  * Example: "Article: Product Announcement - New node created with workflow state draft"

* **update**: Triggered when a node's workflow state changes via `hook_node_update()` (only if state actually changed)
  * Logs the node content type and node title
  * Records both the old and new workflow states
  * Only logged if the moderation_state value differs from the original
  * Example: "Article: Product Announcement - Workflow state changed from draft to published"

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "workflows"
* **Operation**: The specific operation performed (insert, update)
* **Description**: Human-readable message including content type, title, and state information
  * Insert: "%type: %title - New node created with workflow state %new_state"
  * Update: "%type: %title - Workflow state changed from %old_state to %new_state"
* **Reference (numeric)**: Node ID for easy reference and filtering
* **Reference (char)**: Node title for easy searching and identification

## Usage

All workflow state changes are automatically logged on nodes with moderation_state fields. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Workflows" log type to view only workflow-related events
3. View detailed information about each workflow state change

## Use Cases

* **Content Moderation Tracking**: Track content publication workflows and approval processes
* **Publishing Workflow**: Monitor editorial workflow states (draft, review, published, archived)
* **Accountability**: Track which editors and reviewers moved content through workflow states
* **Compliance Requirements**: Maintain detailed records of content publication workflow for regulatory compliance and audit trails
* **Editorial Process**: Review workflow history to understand content editorial evolution
* **Publication Audit**: Audit content publication decisions and approvals
* **Incident Investigation**: Review workflow change history during content-related incident investigations
* **Data Governance**: Maintain historical records of content workflow lifecycle for archival and audit purposes
