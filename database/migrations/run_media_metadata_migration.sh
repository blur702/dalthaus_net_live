#!/bin/bash
# Run media metadata migration
# Usage: ./run_media_metadata_migration.sh

echo "Running media metadata migration..."

# Get database credentials from config
DB_NAME="dalthaus_maincms"
DB_USER="dalthaus_maincms"
DB_PASS="f4!,Wpds=w6*=~+1"

# Run migration
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < add_media_metadata.sql

if [ $? -eq 0 ]; then
    echo "✓ Migration completed successfully"
else
    echo "✗ Migration failed"
    exit 1
fi
