# Admin Audit Trail Documentation

A comprehensive Drupal audit logging module that tracks and records all content management and administrative actions within your website.

## What is Admin Audit Trail?

Admin Audit Trail is a powerful auditing solution for Drupal that automatically logs events performed by users through the administrative interface and system operations. The module provides complete visibility into who did what, when, and where on your Drupal site.

### Key Features

- **Comprehensive Logging**: Tracks entity operations (insert, update, delete) and user actions (login, logout, password resets)
- **Easy Audit Trail Review**: View all logged events on a dedicated audit trail page with filtering capabilities
- **User Identification**: Every log entry records which user performed the action
- **Detailed Descriptions**: Human-readable descriptions with entity details and context
- **Extensible Architecture**: Easy to extend with custom events through the audit trail API
- **Zero Configuration**: Sub-modules automatically begin logging once enabled
- **Compliance Ready**: Maintains permanent records for regulatory compliance (HIPAA, GDPR, SOC 2)

## Getting Started

### For Site Builders and Content Editors

If you're responsible for managing content or reviewing audit trails:

- [Installation and Setup](1-users/0-installation.md) - Get started with Admin Audit Trail
- [Viewing Audit Logs](1-users/1-viewing-logs.md) - Learn how to view and filter audit trail records
- [Understanding Log Entries](1-users/2-understanding-logs.md) - Understand what information is captured
- [Common Use Cases](1-users/3-use-cases.md) - Real-world scenarios and examples

### For Site Administrators

If you're responsible for configuring and maintaining the audit system:

- [Configuration](2-admins/0-configuration.md) - Configure audit trail settings and retention policies
- [Permissions](2-admins/1-permissions.md) - Manage who can access and configure audit trails
- [Sub-modules Guide](2-admins/2-submodules.md) - Learn about available sub-modules and what they track
- [Performance and Maintenance](2-admins/3-performance.md) - Best practices for maintaining audit logs

### For Developers

If you're extending or integrating with Admin Audit Trail:

- [Creating Custom Event Handlers](3-developers/0-custom-handlers.md) - Add custom audit trail events
- [API Reference](3-developers/1-api-reference.md) - Complete API documentation
- [Database Schema](3-developers/2-database-schema.md) - Understanding the audit trail database structure
- [Testing](3-developers/3-testing.md) - Testing your custom implementations

## Available Sub-modules

Admin Audit Trail includes specialized sub-modules for tracking different types of content and actions:

### Authentication & User Management
- **Admin Audit Trail User Authentication** - Login, logout, password resets, failed attempts
- **Admin Audit Trail User** - User account creation, updates, deletion
- **Admin Audit Trail User Roles** - Role assignments and management

### Content Management
- **Admin Audit Trail Node** - Content creation, updates, deletion, translations
- **Admin Audit Trail Comment** - Comment operations
- **Admin Audit Trail Block Content** - Custom block operations
- **Admin Audit Trail Media** - Media asset operations
- **Admin Audit Trail File** - File entity operations

### Site Structure & Organization
- **Admin Audit Trail Menu** - Menu and menu link operations
- **Admin Audit Trail Taxonomy** - Vocabulary and term operations
- **Admin Audit Trail Redirect** - URL redirect operations

### Advanced Features
- **Admin Audit Trail Workflows** - Content workflow state transitions
- **Admin Audit Trail Entityqueue** - Entity queue operations
- **Admin Audit Trail Paragraphs** - Paragraph entity operations
- **Admin Audit Trail Group** - Group entity operations

## Quick Links

- [FAQ](faq.md) - Frequently asked questions
- [Project Page](https://www.drupal.org/project/admin_audit_trail) - Drupal.org project page
- [Issue Queue](https://www.drupal.org/project/issues/admin_audit_trail) - Report bugs and request features

## Need Help?

- Check the [FAQ](faq.md) for common questions
- Review the relevant documentation section based on your role
- Visit the [issue queue](https://www.drupal.org/project/issues/admin_audit_trail) for support
