# Understanding Log Entries

This guide explains what information is captured in audit trail logs and how to interpret the data.

## Anatomy of a Log Entry

Each audit trail record contains the following information:

### Core Fields

| Field | Description | Example |
|-------|-------------|---------|
| **ID** | Unique log entry identifier | 12345 |
| **Type** | Entity or event type being logged | node, user, taxonomy_term |
| **Operation** | Action performed | insert, update, delete, login |
| **Description** | Human-readable event description | "Updated article 'Getting Started'" |
| **User ID** | ID of user who performed the action | 3 |
| **User Name** | Username of the person | admin, editor |
| **Timestamp** | When the event occurred | 2024-01-15 14:30:45 |
| **IP Address** | IP address of the user | 192.168.1.100 |
| **Path** | URL where action was performed | /node/123/edit |
| **Reference ID** | ID of the affected entity | 123 (node ID) |

## Event Types

### Content Operations (Node)

**Enabled by**: Admin Audit Trail Node module

#### Insert (Create)
```
Type: node
Operation: insert
Description: Created article "New Product Launch"
```

**What this means**: A new content item was created
**Common triggers**: Publishing new articles, pages, blog posts

#### Update (Modify)
```
Type: node
Operation: update
Description: Updated page "About Us" - Changed body field
```

**What this means**: Existing content was modified
**Common triggers**: Editing content, changing publication status, updating fields

#### Delete (Remove)
```
Type: node
Operation: delete
Description: Deleted article "Outdated Information"
```

**What this means**: Content was permanently removed
**Common triggers**: Deleting old content, removing spam, cleaning up drafts

### User Account Operations

**Enabled by**: Admin Audit Trail User module

#### User Creation
```
Type: user
Operation: insert
Description: Created user account: john.doe
```

**What this means**: A new user account was created
**Who can do this**: Administrators, users with "Administer users" permission

#### User Updates
```
Type: user
Operation: update
Description: Updated user account: jane.smith - Changed email to jane.smith@example.com
```

**What this means**: User account details were modified
**Common changes**: Email, name, roles, password, account status

#### User Deletion
```
Type: user
Operation: delete
Description: Deleted user account: old.account (UID: 456)
```

**What this means**: User account was permanently removed
**Impact**: User can no longer log in, content remains unless explicitly deleted

### Authentication Events

**Enabled by**: Admin Audit Trail User Authentication module

#### Successful Login
```
Type: user
Operation: login
Description: User admin logged in successfully
IP: 192.168.1.50
```

**What this means**: User successfully authenticated
**Why track this**: Security monitoring, access patterns, compliance

#### Failed Login Attempt
```
Type: user
Operation: login_failed
Description: Failed login attempt for user: admin
IP: 10.0.0.15
```

**What this means**: Someone tried to log in but failed
**Red flags**: Multiple failed attempts, unusual IPs, non-existent usernames

#### Logout
```
Type: user
Operation: logout
Description: User editor logged out
```

**What this means**: User ended their session
**Use cases**: Session duration tracking, audit compliance

#### Password Reset Request
```
Type: user
Operation: password_reset
Description: Password reset requested for user: john.doe
```

**What this means**: User requested a password reset link
**Security note**: Monitor for suspicious reset requests

### Taxonomy Operations

**Enabled by**: Admin Audit Trail Taxonomy module

#### Term Creation
```
Type: taxonomy_term
Operation: insert
Description: Created taxonomy term "Marketing" in Tags vocabulary
```

#### Term Updates
```
Type: taxonomy_term
Operation: update
Description: Updated taxonomy term "Sales" - Changed description and parent
```

#### Term Deletion
```
Type: taxonomy_term
Operation: delete
Description: Deleted taxonomy term "Obsolete Category"
```

### Media Operations

**Enabled by**: Admin Audit Trail Media module

#### Media Upload
```
Type: media
Operation: insert
Description: Created media item "company-logo.png" (Image)
```

#### Media Updates
```
Type: media
Operation: update
Description: Updated media "Product Photo" - Changed alt text
```

#### Media Deletion
```
Type: media
Operation: delete
Description: Deleted media item "old-banner.jpg"
```

### Menu Operations

**Enabled by**: Admin Audit Trail Menu module

#### Menu Link Creation
```
Type: menu_link
Operation: insert
Description: Created menu link "Contact Us" in Main navigation
```

#### Menu Link Updates
```
Type: menu_link
Operation: update
Description: Updated menu link "About" - Changed parent and weight
```

### Workflow State Changes

**Enabled by**: Admin Audit Trail Workflows module

#### State Transition
```
Type: workflow
Operation: state_change
Description: Node "Article Title" changed from Draft to Published
User: editor
```

**What this means**: Content moved through an editorial workflow
**Use cases**: Editorial accountability, publication tracking

### User Role Changes

**Enabled by**: Admin Audit Trail User Roles module

#### Role Assignment
```
Type: user_role
Operation: role_add
Description: Added role "Editor" to user john.doe
```

#### Role Removal
```
Type: user_role
Operation: role_remove
Description: Removed role "Content Creator" from user jane.smith
```

## Interpreting IP Addresses

### Internal vs External Access

- **192.168.x.x** - Local network (internal office)
- **10.x.x.x** - Private network (VPN, internal)
- **Public IPs** - External access (remote users, public internet)

### What IP Addresses Tell You

1. **Location**: Where the user was when they performed the action
2. **Access Method**: Office network, home, VPN, public WiFi
3. **Security**: Unusual IPs might indicate unauthorized access

### Multiple IPs for Same User

**Normal scenarios**:
- User switches between office and home
- User accesses via VPN vs direct connection
- Mobile device vs desktop

**Concerning scenarios**:
- Simultaneous logins from different locations
- Access from unexpected countries
- Many different IPs in short time period

## Understanding Timestamps

### Timezone Considerations

Timestamps are typically stored in:
- **UTC** (Coordinated Universal Time) in the database
- **Site timezone** when displayed to users

### Reading Timestamps

```
2024-01-15 14:30:45
```

Breaking this down:
- **2024-01-15** - Date (Year-Month-Day)
- **14:30:45** - Time (Hour:Minute:Second in 24-hour format)

### Time-based Analysis

Look for patterns:
- **Off-hours activity** - Actions outside business hours
- **Rapid succession** - Many actions in short time (automation or suspicious activity)
- **Delayed actions** - Time between related events

## Common Log Patterns

### Normal Activity Patterns

1. **Content Creation Workflow**
   ```
   14:00 - User editor: Insert node "New Article"
   14:05 - User editor: Update node "New Article" (typo fix)
   14:10 - User manager: Update node "New Article" (workflow: Draft -> Published)
   ```

2. **User Onboarding**
   ```
   09:00 - User admin: Insert user "new.employee"
   09:01 - User admin: Add role "Content Editor" to new.employee
   09:30 - User new.employee: Login (first login)
   ```

### Suspicious Activity Patterns

1. **Brute Force Attack**
   ```
   10:00 - Failed login for admin from IP 123.45.67.89
   10:00 - Failed login for admin from IP 123.45.67.89
   10:00 - Failed login for admin from IP 123.45.67.89
   [repeated many times]
   ```

2. **Unauthorized Deletion**
   ```
   02:00 - User unknown: Delete node "Important Content"
   02:01 - User unknown: Delete node "Critical Page"
   [Unusual time, multiple deletions]
   ```

3. **Privilege Escalation**
   ```
   11:00 - User editor: Add role "Administrator" to editor
   [User giving themselves admin rights]
   ```

## Using Reference IDs

The Reference ID field links the log entry to the actual entity:

```
Type: node
Operation: update
Reference ID: 123
```

This means:
- The updated content has node ID 123
- You can visit `/node/123` to view it (if still exists)
- Useful for finding the specific content that was changed

### For Deleted Entities

When an entity is deleted:
- The reference ID remains in the log
- You can't navigate to the entity (it's gone)
- But you can see what was deleted and when

## Log Entry Lifecycle

### Creation
- Event occurs (user creates content, logs in, etc.)
- Module captures event details
- Log entry inserted into database
- Timestamp and user info automatically recorded

### Storage
- Logs stored in `admin_audit_trail` database table
- Retention based on configuration settings
- No automatic expiration by default

### Cleanup
- Manual deletion possible (with permissions)
- Automatic cleanup via cron (if configured)
- Oldest entries removed first when limit reached

## Data Privacy and Logs

### Personal Information in Logs

Audit logs may contain:
- **Usernames** - Who performed actions
- **IP addresses** - Where they accessed from
- **Email addresses** - In user update descriptions
- **Content data** - Titles, descriptions in log messages

### Compliance Considerations

- **GDPR**: May require anonymization or deletion upon user request
- **HIPAA**: Protected health information must be secured
- **SOC 2**: Audit logs are required for compliance
- **PCI DSS**: Track access to cardholder data

### Best Practices

1. **Limit Access**: Only authorized personnel should view logs
2. **Retention Policies**: Keep logs only as long as needed
3. **Anonymization**: Consider anonymizing old logs
4. **Encryption**: Ensure database encryption for sensitive sites
5. **Regular Reviews**: Audit who accesses the audit logs

## Next Steps

- [Learn about common use cases](3-use-cases.md)
- [Configure log retention](../2-admins/0-configuration.md)
- [Set up proper permissions](../2-admins/1-permissions.md)
