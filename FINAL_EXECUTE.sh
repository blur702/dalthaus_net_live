#!/bin/bash
# Final execution script to fix everything

cd /home/dalthaus/public_html

echo "=== Forcing Git Update ==="
git stash --include-untracked
git fetch origin
git reset --hard origin/main

echo "=== Running MASTER FIX ==="
if [ -f "MASTER_FIX.php" ]; then
    echo "MASTER_FIX.php found"
    # Create auto-run version
    echo '<?php $_GET["action"] = "fix"; include "MASTER_FIX.php"; ?>' > RUN_NOW.php
    echo "Visit: https://dalthaus.net/RUN_NOW.php"
else
    echo "Downloading MASTER_FIX.php from GitHub..."
    curl -o MASTER_FIX.php https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/MASTER_FIX.php
    echo '<?php $_GET["action"] = "fix"; include "MASTER_FIX.php"; ?>' > RUN_NOW.php
    echo "Visit: https://dalthaus.net/RUN_NOW.php"
fi

echo "=== Complete ==="
echo "The fixes are ready at: https://dalthaus.net/RUN_NOW.php"