# Admin Audit Trail User Authentication

Admin Audit Trail User Authentication is a Drupal module that extends the Admin Audit Trail module by logging all user authentication activities.

Provides comprehensive audit tracking for user login, logout, password reset requests, and failed login attempts, helping administrators maintain detailed security records of user authentication events.

## Features

* **User Login Tracking**: Logs successful user logins via `hook_user_login()`
* **User Logout Tracking**: Logs user logouts via `hook_user_logout()`
* **Password Reset Request Tracking**: Logs password reset requests submitted via the user password form
* **Failed Login Attempt Tracking**: Logs failed login attempts with username for security monitoring
* **Form-Based Tracking**: Integrates with user login and password forms via `hook_form_alter()`
* **Detailed Event Descriptions**: Provides human-readable descriptions of all authentication operations
* **User Identification**: Records user display name and user ID for easy reference and tracking
* **Integration with Admin Audit Trail**: Seamlessly integrates with the Admin Audit Trail module for centralized audit logging

## Requirements

* Drupal
* Admin Audit Trail module (`admin_audit_trail`)

## Installation

1. Download or clone this module into your Drupal `modules` directory

2. Enable the module via the Drupal admin interface or using Drush:

```bash
drush en admin_audit_trail_auth
```

3. Ensure the Admin Audit Trail module is enabled

4. Clear the Drupal cache

## Configuration

This module requires no additional configuration. Once enabled, it automatically begins logging all user authentication events through the Admin Audit Trail system.

## Logged Events

### User Authentication Operations

The module logs user authentication activities through the following events:

* **login**: Triggered when a user successfully logs into the system via `hook_user_login()`
  * Logs the user display name and user ID
  * Captured after successful form submission

* **logout**: Triggered when a user logs out from the system via `hook_user_logout()`
  * Logs the user display name and user ID
  * Captured at logout time

* **request password**: Triggered when a user submits the password reset form (`user_pass`) via form submit callback
  * Logs the username (if account found)
  * Records user ID if available, otherwise 0

* **fail**: Triggered when a user login attempt fails due to validation errors
  * Logs the attempted username
  * Captured via form validation hook for security monitoring

## Log Entry Details

Each audit trail entry includes:

* **Type**: Always "authentication"
* **Operation**: The specific operation performed (login, logout, request password, fail)
* **Description**: A human-readable message describing the event (e.g., "Admin (uid 1)")
* **Reference (numeric)**: User ID for easy reference and filtering
* **Reference (char)**: User display name or username for easy searching and identification

## Usage

All user authentication events are automatically logged. To view the audit trail:

1. Navigate to Administration > Reports > Audit Trail (or your configured audit trail location)
2. Filter by the "Authentication" log type to view only authentication-related events
3. View detailed information about each authentication operation

## Use Cases

* **Security Monitoring**: Track user login and logout activities to detect unauthorized access attempts
* **Compliance Requirements**: Maintain detailed records of user authentication for regulatory compliance and audit purposes
* **User Activity Tracking**: Monitor when users access the system to identify usage patterns
* **Incident Investigation**: Review authentication history during security incident investigations
* **Access Control**: Identify suspicious login patterns or multiple failed login attempts
* **Accountability**: Track which users accessed the system and when
