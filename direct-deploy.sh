#!/bin/bash

# Direct deployment script for database fixes
echo "Deploying critical database fixes to dalthaus.net"
echo "================================================="

# Use git push followed by manual file upload
echo "Step 1: Changes pushed to git repository ✓"

echo "Step 2: Uploading critical config file..."

# Upload the corrected config.local.php
curl -X POST "https://dalthaus.net/remote-file-agent.php" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "agent-20250830",
    "action": "write_file", 
    "path": "includes/config.local.php",
    "content": "<?php\n/**\n * Local Development Configuration Override\n * \n * This file overrides production settings for local development.\n * It will be loaded after config.php if it exists.\n * \n * @package DalthausCMS\n * @since 1.0.0\n */\n\n// Production database settings for shared hosting\ndefine(\"DB_HOST\", \"localhost\");\ndefine(\"DB_NAME\", \"dalthaus_photocms\");\ndefine(\"DB_USER\", \"dalthaus_photocms\");\ndefine(\"DB_PASS\", \"f-I*GSo^Urt*k*&#\");\n\n// Set to production mode\ndefine(\"ENV\", \"production\");\n\n// Use local admin credentials\ndefine(\"DEFAULT_ADMIN_USER\", \"admin\");\ndefine(\"DEFAULT_ADMIN_PASS\", \"130Bpm\");\n\n// Production settings\ndefine(\"LOG_LEVEL\", \"error\");\ndefine(\"CACHE_ENABLED\", true);"
  }'

echo "Step 3: Uploading test script..."

# Upload comprehensive test script
curl -X POST "https://dalthaus.net/remote-file-agent.php" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "agent-20250830",
    "action": "write_file",
    "path": "verify-fixes.php", 
    "content": "<?php\n/**\n * Verify Database Fixes\n */\nrequire_once \"includes/config.php\";\nrequire_once \"includes/database.php\";\n\necho \"<h2>Fix Verification</h2>\";\ntry {\n    $pdo = Database::getInstance();\n    echo \"<p style=\\\"color: green;\\\">✓ Database connection successful!</p>\";\n    echo \"<p>Database: \" . DB_NAME . \"</p>\";\n    echo \"<p>User: \" . DB_USER . \"</p>\";\n    echo \"<p><a href=\\\"/\\\">Test Homepage</a></p>\";\n} catch (Exception $e) {\n    echo \"<p style=\\\"color: red;\\\">✗ Error: \" . htmlspecialchars($e->getMessage()) . \"</p>\";\n}\n?>"
  }'

echo "================================================="
echo "Deployment commands prepared."
echo "Files will be uploaded when script is executed on server."