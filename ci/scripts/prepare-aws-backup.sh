#!/bin/bash
set -e

# Configuration
SOURCE_DIR="website-assets"
WORK_DIR="backup-staging-aws-import"
ARCHIVE_NAME="backup_staging-aws-import.tar.gz"
OLD_DB_NAME="ilwebsitev4db"
NEW_DB_NAME="interledger_org_staging"

echo "=== Preparing AWS backup for import ==="

# Clean up any previous work
rm -rf "$WORK_DIR" "$ARCHIVE_NAME"

# Create work directory
mkdir -p "$WORK_DIR/files"

echo "Step 1: Processing SQL dump..."
# Replace database name in SQL file
sed "s/\`$OLD_DB_NAME\`/\`$NEW_DB_NAME\`/g" "$SOURCE_DIR/backupdb.sql" > "$WORK_DIR/db_dump.sql"
echo "  ✓ Database name replaced: $OLD_DB_NAME -> $NEW_DB_NAME"

echo "Step 2: Copying files..."
# Copy all files except js and css directories
rsync -av --exclude='js' --exclude='css' "$SOURCE_DIR/files/" "$WORK_DIR/files/"
echo "  ✓ Files copied (js and css excluded)"

echo "Step 3: Creating tar.gz archive..."
# Create archive specifying files explicitly to avoid directory entries
# The backup manager expects: db_dump.sql (root) and files/* (all files recursively)
cd "$WORK_DIR"
tar -czf "../$ARCHIVE_NAME" db_dump.sql files
cd ..
echo "  ✓ Archive created: $ARCHIVE_NAME"

echo ""
echo "=== Summary ==="
echo "Archive: $ARCHIVE_NAME"
echo "Size: $(du -h "$ARCHIVE_NAME" | cut -f1)"
echo "Contents:"
tar -tzf "$ARCHIVE_NAME" | head -20
echo "..."
echo ""
echo "=== Next Steps ==="
echo "Upload to GCS:"
echo "  gsutil cp $ARCHIVE_NAME gs://interledeger-org-website-backups/backups/staging/backup_staging-aws-import.tar.gz"
echo ""
echo "Then restore with:"
echo "  cd ci"
echo "  make restore ENV=staging FROM-RUN=staging-aws-import TARGETENV=staging"

