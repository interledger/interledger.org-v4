# Admin Audit Trail Taxonomy

Admin Audit Trail Taxonomy is a Drupal module that extends the Admin Audit Trail module by logging all taxonomy-related activities.

Provides comprehensive audit tracking for taxonomy vocabulary creation, updates, deletion, and taxonomy term management operations, helping administrators maintain detailed records of taxonomy structure changes.

## Features

* **Vocabulary Creation Tracking**: Logs when new taxonomies (vocabularies) are created via `hook_taxonomy_vocabulary_insert()`
* **Vocabulary Update Tracking**: Logs when vocabulary configurations are modified via `hook_taxonomy_vocabulary_update()`
* **Vocabulary Deletion Tracking**: Logs when vocabularies are removed via `hook_taxonomy_vocabulary_delete()`
* **Term Creation Tracking**: Logs when new terms are created via `hook_taxonomy_term_insert()`
* **Term Update Tracking**: Logs when terms are modified via `hook_taxonomy_term_update()`
* **Term Deletion Tracking**: Logs when terms are removed via `hook_taxonomy_term_delete()`
* **Vocabulary Identification**: Records vocabulary machine name and human-readable name
* **Term Identification**: Records term name and term ID for easy reference
* **Vocabulary Reference**: Records which vocabulary a term belongs to for context
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_taxonomy
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all taxonomy events through the Admin Audit Trail system.

## Logged Events

### Vocabulary Operations

The module logs when vocabularies (taxonomies) are created, modified, or deleted:

* **vocabulary insert**: Triggered when a new vocabulary is created via `hook_taxonomy_vocabulary_insert()`
  * Logs the vocabulary name and machine name
  * Example: "Categories (categories)"

* **vocabulary update**: Triggered when a vocabulary is modified via `hook_taxonomy_vocabulary_update()`
  * Logs the vocabulary label and machine name
  * Captures all vocabulary configuration changes

* **vocabulary delete**: Triggered when a vocabulary is deleted via `hook_taxonomy_vocabulary_delete()`
  * Logs the vocabulary label and machine name
  * Provides permanent record of deleted vocabularies for compliance

### Term Operations

The module tracks all taxonomy term management:

* **term insert**: Triggered when a new term is created via `hook_taxonomy_term_insert()`
  * Logs the term name and term ID
  * Records the vocabulary it belongs to
  * Example: "Product (tid 42)"

* **term update**: Triggered when a term is modified via `hook_taxonomy_term_update()`
  * Logs the term name and term ID
  * Captures all term modification events

* **term delete**: Triggered when a term is deleted via `hook_taxonomy_term_delete()`
  * Logs the term name and term ID
  * Provides permanent record of deleted terms for compliance

## Log Entry Details

### Vocabulary Entries

Each audit trail entry includes:

* **Type**: Always "taxonomy"
* **Operation**: The specific operation performed (vocabulary insert, vocabulary update, vocabulary delete)
* **Description**: Format "%title (%name)" (e.g., "Categories (categories)")
* **Reference (char)**: Vocabulary machine name for easy searching

### Term Entries

Each audit trail entry includes:

* **Type**: Always "taxonomy"
* **Operation**: The specific operation performed (term insert, term update, term delete)
* **Description**: Format "%name (%tid)" (e.g., "Product (42)")
* **Reference (numeric)**: Term ID for easy reference and filtering
* **Reference (char)**: Vocabulary machine name for easy searching and categorization

## Usage

All taxonomy events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Taxonomy" log type to view only taxonomy-related events
3. View detailed information about each taxonomy operation

## Use Cases

* **Taxonomy Management**: Track creation, modification, and deletion of all taxonomy vocabularies and terms
* **Accountability**: Monitor which administrators created, modified, or deleted specific vocabularies or terms
* **Taxonomy Structure**: Review taxonomy modification history to understand categorization evolution
* **SEO Impact**: Track changes to taxonomy structure that may impact site organization and SEO
* **Compliance Requirements**: Maintain detailed records of taxonomy activity for regulatory compliance
* **Incident Investigation**: Review taxonomy change history during categorization-related investigations
* **Data Governance**: Maintain historical records of taxonomy lifecycle for archival and audit purposes
