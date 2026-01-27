# Sub-modules Guide

Complete reference for all Admin Audit Trail sub-modules, what they track, and when to enable them.

## Understanding Sub-modules

Admin Audit Trail uses a modular architecture:
- **Base module** (`admin_audit_trail`) - Provides the framework
- **Sub-modules** - Track specific entity types and events

Each sub-module is independent and can be enabled/disabled as needed.

## Authentication & User Management

### Admin Audit Trail User Authentication

**Machine name**: `admin_audit_trail_auth`

**Purpose**: Tracks user authentication events

**What it logs**:
- ✓ Successful logins
- ✓ Failed login attempts
- ✓ User logouts
- ✓ Password reset requests
- ✓ One-time login link usage

**When to enable**:
- Security monitoring is required
- Investigating unauthorized access
- Compliance requires authentication tracking (HIPAA, SOC 2)
- Brute force attack detection

**Dependencies**: None

**Log examples**:
```
Operation: login
Description: User admin logged in successfully
IP: 192.168.1.50

Operation: login_failed
Description: Failed login attempt for user: admin
IP: 45.123.45.67

Operation: password_reset
Description: Password reset requested for user: john.doe
```

**Performance impact**: Low (authentication events are infrequent)

---

### Admin Audit Trail User

**Machine name**: `admin_audit_trail_user`

**Purpose**: Tracks user account management

**What it logs**:
- ✓ User account creation
- ✓ User account updates (email, name, status changes)
- ✓ User account deletion
- ✓ Account blocking/unblocking

**When to enable**:
- User management accountability needed
- Compliance requires user lifecycle tracking
- HR/security investigations
- Audit trail for account changes

**Dependencies**: None

**Log examples**:
```
Operation: insert
Description: Created user account: john.doe

Operation: update
Description: Updated user jane.smith - Changed email to jane@example.com

Operation: delete
Description: Deleted user account: old.user (UID: 456)
```

**Performance impact**: Low (user operations are infrequent)

---

### Admin Audit Trail User Roles

**Machine name**: `admin_audit_trail_user_roles`

**Purpose**: Tracks user role assignments and changes

**What it logs**:
- ✓ Role assigned to user
- ✓ Role removed from user
- ✓ Bulk role changes

**When to enable**:
- Security-sensitive sites (privilege escalation monitoring)
- Compliance requirements
- Administrative accountability
- Investigating permission changes

**Dependencies**: None

**Log examples**:
```
Operation: role_add
Description: Added role "Editor" to user john.doe

Operation: role_remove
Description: Removed role "Administrator" from user jane.smith
```

**Performance impact**: Low

**Security note**: Critical for detecting unauthorized privilege escalation

---

## Content Management

### Admin Audit Trail Node

**Machine name**: `admin_audit_trail_node`

**Purpose**: Tracks content (node) operations

**What it logs**:
- ✓ Content creation
- ✓ Content updates
- ✓ Content deletion
- ✓ Content translations (multilingual sites)
- ✓ Publishing status changes

**When to enable**:
- Content accountability required
- Editorial workflow tracking
- Compliance/regulatory requirements
- Content change investigations

**Dependencies**: Node module (Drupal core)

**Log examples**:
```
Operation: insert
Description: Created article "New Product Launch"

Operation: update
Description: Updated page "About Us" - Changed body field

Operation: delete
Description: Deleted article "Old News Story"
```

**Performance impact**: Medium (high-traffic sites create many logs)

**Best for**: All content-focused sites

---

### Admin Audit Trail Comment

**Machine name**: `admin_audit_trail_comment`

**Purpose**: Tracks comment operations

**What it logs**:
- ✓ Comment creation
- ✓ Comment updates
- ✓ Comment deletion
- ✓ Comment approval/spam marking

**When to enable**:
- Sites with active commenting
- Moderation accountability
- Spam investigation
- User-generated content tracking

**Dependencies**: Comment module (Drupal core)

**Log examples**:
```
Operation: insert
Description: Created comment on article "Blog Post Title"

Operation: delete
Description: Deleted comment by user spam_account (marked as spam)
```

**Performance impact**: Medium (active comment sites generate many logs)

---

### Admin Audit Trail Block Content

**Machine name**: `admin_audit_trail_block_content`

**Purpose**: Tracks custom block operations

**What it logs**:
- ✓ Custom block creation
- ✓ Custom block updates
- ✓ Custom block deletion

**When to enable**:
- Sites using custom blocks heavily
- Block content accountability needed
- Layout Builder usage tracking

**Dependencies**: Block Content module (Drupal core)

**Performance impact**: Low

---

### Admin Audit Trail Media

**Machine name**: `admin_audit_trail_media`

**Purpose**: Tracks media asset operations

**What it logs**:
- ✓ Media entity creation (images, videos, documents)
- ✓ Media entity updates
- ✓ Media entity deletion
- ✓ Alt text and metadata changes

**When to enable**:
- Digital asset management accountability
- Tracking image uploads
- Compliance (ensure proper alt text)
- Media library management

**Dependencies**: Media module (Drupal core)

**Log examples**:
```
Operation: insert
Description: Created media item "company-logo.png" (Image)

Operation: update
Description: Updated media "Product Photo" - Changed alt text
```

**Performance impact**: Medium (frequent uploads generate logs)

---

### Admin Audit Trail File

**Machine name**: `admin_audit_trail_file`

**Purpose**: Tracks file entity operations

**What it logs**:
- ✓ File uploads
- ✓ File replacement
- ✓ File deletion

**When to enable**:
- Document management sites
- File upload accountability
- Storage monitoring
- Security (suspicious file uploads)

**Dependencies**: File module (Drupal core)

**Performance impact**: Medium

**Note**: Different from Media module - tracks file entities directly

---

## Site Structure & Organization

### Admin Audit Trail Menu

**Machine name**: `admin_audit_trail_menu`

**Purpose**: Tracks menu and menu link operations

**What it logs**:
- ✓ Menu creation
- ✓ Menu link creation
- ✓ Menu link updates (title, parent, weight changes)
- ✓ Menu link deletion
- ✓ Menu deletion

**When to enable**:
- Navigation change accountability
- Site structure tracking
- Investigating broken navigation
- Multi-editor sites

**Dependencies**: Menu UI module (Drupal core)

**Log examples**:
```
Operation: insert
Description: Created menu link "Contact Us" in Main navigation

Operation: update
Description: Updated menu link "About" - Changed parent and weight
```

**Performance impact**: Low

---

### Admin Audit Trail Taxonomy

**Machine name**: `admin_audit_trail_taxonomy`

**Purpose**: Tracks taxonomy vocabulary and term operations

**What it logs**:
- ✓ Vocabulary creation
- ✓ Taxonomy term creation
- ✓ Taxonomy term updates
- ✓ Taxonomy term deletion
- ✓ Vocabulary deletion

**When to enable**:
- Sites using taxonomies heavily
- Category management accountability
- Tag system tracking
- Content organization audits

**Dependencies**: Taxonomy module (Drupal core)

**Log examples**:
```
Operation: insert
Description: Created taxonomy term "Marketing" in Tags vocabulary

Operation: update
Description: Updated taxonomy term "Sales" - Changed description

Operation: delete
Description: Deleted taxonomy term "Obsolete Category"
```

**Performance impact**: Low

---

### Admin Audit Trail Redirect

**Machine name**: `admin_audit_trail_redirect`

**Purpose**: Tracks URL redirect operations

**What it logs**:
- ✓ Redirect creation
- ✓ Redirect updates
- ✓ Redirect deletion

**When to enable**:
- SEO management
- URL migration tracking
- 404 error management
- Redirect accountability

**Dependencies**: [Redirect module](https://www.drupal.org/project/redirect)

**Log examples**:
```
Operation: insert
Description: Created redirect from /old-page to /new-page

Operation: delete
Description: Deleted redirect from /temp-url to /permanent-url
```

**Performance impact**: Low

---

## Advanced Features

### Admin Audit Trail Workflows

**Machine name**: `admin_audit_trail_workflows`

**Purpose**: Tracks content workflow state transitions

**What it logs**:
- ✓ Workflow state changes (Draft → Review → Published)
- ✓ Moderation state transitions
- ✓ Who approved/rejected content
- ✓ Workflow history

**When to enable**:
- Editorial workflow accountability
- Content approval tracking
- Compliance (approval process documentation)
- Publishing workflow audits

**Dependencies**: Workflows module (Drupal core), Content Moderation

**Log examples**:
```
Operation: state_change
Description: Node "Article Title" changed from Draft to Published
User: editor
```

**Performance impact**: Low

**Critical for**: Organizations with editorial approval processes

---

### Admin Audit Trail Entityqueue

**Machine name**: `admin_audit_trail_entityqueue`

**Purpose**: Tracks entity queue operations

**What it logs**:
- ✓ Queue creation
- ✓ Queue updates
- ✓ Queue deletion
- ✓ Subqueue operations
- ✓ Entity queue ordering changes

**When to enable**:
- Sites using Entity Queue module
- Featured content management
- Queue management accountability
- Content ordering tracking

**Dependencies**: [Entityqueue module](https://www.drupal.org/project/entityqueue)

**Performance impact**: Low

---

### Admin Audit Trail Paragraphs

**Machine name**: `admin_audit_trail_paragraphs`

**Purpose**: Tracks paragraph entity operations

**What it logs**:
- ✓ Paragraph creation
- ✓ Paragraph updates
- ✓ Paragraph deletion
- ✓ Paragraph reordering

**When to enable**:
- Sites using Paragraphs module
- Complex content structure tracking
- Page builder accountability
- Layout/component tracking

**Dependencies**: [Paragraphs module](https://www.drupal.org/project/paragraphs)

**Performance impact**: Medium to High (paragraphs are frequently edited)

**Log examples**:
```
Operation: insert
Description: Created paragraph "Text block" in node "Landing Page"

Operation: update
Description: Updated paragraph "Hero Banner" - Changed background image
```

---

### Admin Audit Trail Group

**Machine name**: `admin_audit_trail_group`

**Purpose**: Tracks group entity operations

**What it logs**:
- ✓ Group creation
- ✓ Group updates
- ✓ Group deletion
- ✓ Group membership changes

**When to enable**:
- Sites using Group module
- Multi-tenant sites
- Department/organization tracking
- Group management accountability

**Dependencies**: [Group module](https://www.drupal.org/project/group)

**Performance impact**: Low to Medium

---

## Sub-module Selection Guide

### By Site Type

#### Corporate Website
```
✓ admin_audit_trail_auth
✓ admin_audit_trail_user
✓ admin_audit_trail_node
✓ admin_audit_trail_media
✓ admin_audit_trail_menu
```

#### News/Publishing Site
```
✓ admin_audit_trail_auth
✓ admin_audit_trail_user
✓ admin_audit_trail_node
✓ admin_audit_trail_workflows
✓ admin_audit_trail_media
✓ admin_audit_trail_taxonomy
```

#### E-commerce Site
```
✓ admin_audit_trail_auth
✓ admin_audit_trail_user
✓ admin_audit_trail_user_roles
✓ admin_audit_trail_node
✓ admin_audit_trail_media
```

#### Community/Forum Site
```
✓ admin_audit_trail_auth
✓ admin_audit_trail_user
✓ admin_audit_trail_comment
✓ admin_audit_trail_node
✓ admin_audit_trail_group (if using Group module)
```

#### Government/Compliance Site
```
✓ Enable ALL relevant sub-modules
✓ Especially: auth, user, user_roles, node, workflows
```

### By Compliance Requirement

#### HIPAA Compliance
```
✓ admin_audit_trail_auth (required)
✓ admin_audit_trail_user (required)
✓ admin_audit_trail_user_roles (required)
✓ admin_audit_trail_node (required)
✓ All others as relevant
```

#### SOC 2 Compliance
```
✓ admin_audit_trail_auth (required)
✓ admin_audit_trail_user (required)
✓ admin_audit_trail_user_roles (required)
✓ All entity types storing sensitive data
```

#### GDPR Compliance
```
✓ admin_audit_trail_user (track personal data changes)
✓ admin_audit_trail_node (track data subject content)
✓ Consider data retention limits
```

## Performance Comparison

| Sub-module | Activity Level | Log Volume | Performance Impact |
|------------|---------------|------------|-------------------|
| Auth | Low | Low | Minimal |
| User | Low | Low | Minimal |
| User Roles | Very Low | Very Low | Minimal |
| Node | High | High | Medium |
| Comment | Medium-High | Medium-High | Medium |
| Block Content | Low | Low | Minimal |
| Media | Medium | Medium | Low-Medium |
| File | Medium | Medium | Low-Medium |
| Menu | Low | Low | Minimal |
| Taxonomy | Low-Medium | Low | Minimal |
| Redirect | Low | Low | Minimal |
| Workflows | Low-Medium | Low-Medium | Minimal |
| Entityqueue | Low | Low | Minimal |
| Paragraphs | High | High | Medium-High |
| Group | Low-Medium | Low-Medium | Low-Medium |

## Enabling Multiple Sub-modules

### Via Drush (Recommended)

**All authentication and user tracking**:
```bash
drush en admin_audit_trail_auth admin_audit_trail_user admin_audit_trail_user_roles -y
```

**All content tracking**:
```bash
drush en admin_audit_trail_node admin_audit_trail_comment admin_audit_trail_media -y
```

**All structure tracking**:
```bash
drush en admin_audit_trail_menu admin_audit_trail_taxonomy admin_audit_trail_redirect -y
```

**Everything (except contrib dependencies)**:
```bash
drush en admin_audit_trail_auth admin_audit_trail_user admin_audit_trail_user_roles \
  admin_audit_trail_node admin_audit_trail_comment admin_audit_trail_block_content \
  admin_audit_trail_media admin_audit_trail_file admin_audit_trail_menu \
  admin_audit_trail_taxonomy admin_audit_trail_workflows -y
```

### Via UI

1. Navigate to **Administration > Extend** (`/admin/modules`)
2. Find "Admin Audit Trail" section
3. Check boxes for desired sub-modules
4. Click "Install"
5. Clear cache if needed

## Disabling Sub-modules

**Important**: Disabling a sub-module stops new logging but preserves existing logs.

**Via Drush**:
```bash
drush pmu admin_audit_trail_comment
```

**Via UI**:
1. Go to Administration > Extend
2. Find the sub-module
3. Click "Uninstall"
4. Confirm uninstallation

**Note**: Existing logs remain in the database until manually deleted or cleaned by retention policy.

## Next Steps

- [Configure retention and settings](0-configuration.md)
- [Set up permissions](1-permissions.md) for your team
- [Optimize performance](3-performance.md) for enabled modules
- [Learn about custom event tracking](../3-developers/0-custom-handlers.md)
