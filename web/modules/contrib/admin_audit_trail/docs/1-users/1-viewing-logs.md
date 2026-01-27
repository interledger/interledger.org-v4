# Viewing Audit Logs

Learn how to access, filter, and understand the audit trail logs in your Drupal site.

## Accessing the Audit Trail

### Via the Admin Menu

1. Navigate to **Administration > Reports > Audit Trail**
2. Or directly visit: `/admin/reports/audit-trail`

### Required Permission

You must have the **"Access Admin Audit Trail"** permission to view audit logs.

## Understanding the Audit Trail Interface

The audit trail page displays all logged events in a table format with the following columns:

| Column | Description |
|--------|-------------|
| **Type** | The entity type (node, user, taxonomy term, etc.) |
| **Operation** | The action performed (insert, update, delete, login, etc.) |
| **Description** | Human-readable description of what happened |
| **User** | The user who performed the action |
| **Timestamp** | When the action occurred |
| **IP Address** | The IP address of the user |
| **Path** | The page/URL where the action was performed |

## Filtering Audit Logs

The audit trail includes powerful filtering capabilities to help you find specific events.

### Available Filters

#### 1. Filter by Type
Filter events by entity type:
- Node (Content)
- User
- Taxonomy term
- Media
- Menu
- Comment
- And more...

**Example Use Case**: Find all changes to taxonomy terms

#### 2. Filter by Operation
Filter by the type of action:
- **Insert** - New entities created
- **Update** - Existing entities modified
- **Delete** - Entities removed
- **Login** - User login events
- **Logout** - User logout events
- **Password Reset** - Password reset requests
- And more...

**Example Use Case**: Find all deleted content

#### 3. Filter by User
Filter events by the user who performed the action:
- Select from a dropdown of all users
- See only actions by specific team members

**Example Use Case**: Review all changes made by a specific editor

#### 4. Filter by Date Range
Filter events within a specific time period:
- **From date** - Start of date range
- **To date** - End of date range

**Example Use Case**: Review all changes made last week

### Using Filters

1. **Expand the Filter Section** (if collapsed)
   - Click on the "Filters" heading to expand the filter form

2. **Select Your Filter Criteria**
   - Choose one or more filter options
   - You can combine multiple filters

3. **Apply Filters**
   - Click the "Apply" or "Filter" button
   - The audit trail table will update to show only matching results

4. **Reset Filters**
   - Click "Reset" to clear all filters and view all logs

### Example Filter Scenarios

#### Find Who Deleted a Specific Article

1. Filter by **Type**: Node
2. Filter by **Operation**: Delete
3. Filter by **Date Range**: When you think it was deleted
4. Review the results to find the deleted article and who removed it

#### Track All Login Attempts Today

1. Filter by **Operation**: Login
2. Filter by **Date Range**: Today's date
3. Review all login events from today

#### Audit a Specific User's Actions

1. Filter by **User**: Select the specific user
2. Review all actions performed by that user
3. Optionally add a date range to narrow results

#### Monitor Workflow Changes

1. Filter by **Type**: Workflow (if Admin Audit Trail Workflows is enabled)
2. Review all content workflow state transitions
3. See who approved or rejected content

## Understanding Log Descriptions

Each log entry includes a human-readable description that explains what happened:

### Content (Node) Examples

- **Insert**: `Created article "How to Use Drupal"`
- **Update**: `Updated page "About Us" - Changed title and body`
- **Delete**: `Deleted article "Old News Story"`

### User Examples

- **Insert**: `Created user account: john.doe`
- **Update**: `Updated user account: jane.smith - Changed email address`
- **Delete**: `Deleted user account: old.user`

### Authentication Examples

- **Login**: `User admin logged in successfully`
- **Logout**: `User editor logged out`
- **Password Reset**: `Password reset requested for user: john.doe`

### Taxonomy Examples

- **Insert**: `Created taxonomy term "Marketing" in Tags vocabulary`
- **Update**: `Updated taxonomy term "Technology" - Changed description`
- **Delete**: `Deleted taxonomy term "Obsolete Tag"`

## Sorting Audit Logs

Click on column headers to sort the audit trail:

- **Timestamp** - Sort by date (newest first or oldest first)
- **Type** - Sort alphabetically by entity type
- **Operation** - Sort by operation type
- **User** - Sort alphabetically by username

## Pagination

If you have many log entries:

- Use the pagination controls at the bottom of the table
- Default page size is typically 50 entries
- Navigate between pages using Next/Previous buttons
- Jump to a specific page number

## Exporting Audit Logs

While the base module doesn't include export functionality, you can:

1. **Use Views Integration** (if available)
   - Create a custom View of the audit trail data
   - Export using Views Data Export module

2. **Database Export**
   - Export the `admin_audit_trail` table directly from your database
   - Use tools like phpMyAdmin or Drush

3. **Copy and Paste**
   - Select relevant rows from the table
   - Copy to a spreadsheet for analysis

## Best Practices for Reviewing Logs

### Regular Review Schedule

- **Daily**: Review authentication events (logins, failed attempts)
- **Weekly**: Review content changes and deletions
- **Monthly**: Comprehensive audit of all activities
- **After incidents**: Investigate specific events immediately

### What to Look For

1. **Unauthorized Access**
   - Failed login attempts
   - Logins from unusual IP addresses
   - Login attempts outside business hours

2. **Unexpected Changes**
   - Content deleted without authorization
   - User role changes
   - Configuration modifications

3. **Compliance Verification**
   - Ensure proper approval workflows are followed
   - Verify content is updated within required timeframes
   - Track data access for regulatory compliance

4. **User Activity Patterns**
   - Identify highly active users
   - Detect unusual activity patterns
   - Monitor during onboarding/offboarding periods

### Using Filters Effectively

1. **Start Broad, Then Narrow**
   - Begin with general filters (e.g., "last 7 days")
   - Add more specific filters as needed

2. **Combine Filters**
   - Use multiple filters together for precise results
   - Example: Type=Node + Operation=Delete + Date Range=Last Month

3. **Save Common Filter Combinations**
   - Document frequently used filter combinations
   - Share with team members for consistency

## Keyboard Shortcuts

(If implemented in your version)

- **Ctrl+F** / **Cmd+F** - Browser find within page
- **Tab** - Navigate between filter fields
- **Enter** - Apply filters

## Mobile Viewing

The audit trail interface is responsive and works on mobile devices:

- Scroll horizontally to see all columns
- Tap to expand filter section
- Use date pickers for date range selection

## Privacy Considerations

When reviewing audit logs, remember:

- Logs contain personally identifiable information (PII)
- User IP addresses and actions are tracked
- Follow your organization's privacy policies
- Limit access to audit logs to authorized personnel only
- Consider data retention policies and regulations (GDPR, CCPA, etc.)

## Next Steps

- [Learn what information is captured in logs](2-understanding-logs.md)
- [Explore common use cases](3-use-cases.md)
- [Configure log retention settings](../2-admins/0-configuration.md)
