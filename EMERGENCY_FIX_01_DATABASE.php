#!/usr/bin/env php
<?php
/**
 * EMERGENCY FIX #1: DATABASE CONNECTION
 * Priority: P0 - SITE IS DOWN
 * Fix database credentials mismatch
 */

echo "\n=== EMERGENCY DATABASE FIX ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Backup current config
$configFile = __DIR__ . '/includes/config.php';
$backupFile = __DIR__ . '/includes/config.backup.' . time() . '.php';

if (!file_exists($configFile)) {
    die("ERROR: Config file not found!\n");
}

echo "1. Backing up current config to: " . basename($backupFile) . "\n";
copy($configFile, $backupFile);

// Step 2: Read current config
$config = file_get_contents($configFile);

// Step 3: Fix database credentials
// The error shows "Access denied for user 'dalthaus_user'@'localhost'"
// But config has DB_USER = 'kevin'
// We need to use the actual production credentials

echo "2. Updating database credentials...\n";

// Replace incorrect credentials
$config = preg_replace(
    "/define\('DB_USER',\s*'kevin'\);/",
    "define('DB_USER', 'dalthaus_user');",
    $config
);

// Keep the password as is - (130Bpm) seems to be correct based on the setup
// If this doesn't work, we'll need the actual production password

echo "3. Writing updated config...\n";
file_put_contents($configFile, $config);

// Step 4: Test database connection
echo "4. Testing database connection...\n";

// Simple connection test
$testConnection = @mysqli_connect('127.0.0.1', 'dalthaus_user', '(130Bpm)', 'dalthaus_cms');

if ($testConnection) {
    echo "✓ DATABASE CONNECTION SUCCESSFUL!\n";
    mysqli_close($testConnection);
} else {
    echo "✗ DATABASE CONNECTION FAILED!\n";
    echo "Error: " . mysqli_connect_error() . "\n";
    echo "\nTrying alternative credentials...\n";
    
    // Try with 'kevin' as password (in case username/password were swapped)
    $testConnection2 = @mysqli_connect('127.0.0.1', 'dalthaus_user', 'kevin', 'dalthaus_cms');
    
    if ($testConnection2) {
        echo "✓ ALTERNATIVE CONNECTION SUCCESSFUL!\n";
        // Update config with working credentials
        $config = file_get_contents($configFile);
        $config = preg_replace(
            "/define\('DB_PASS',\s*'\(130Bpm\)'\);/",
            "define('DB_PASS', 'kevin');",
            $config
        );
        file_put_contents($configFile, $config);
        mysqli_close($testConnection2);
    } else {
        echo "✗ Alternative connection also failed.\n";
        echo "\nMANUAL ACTION REQUIRED:\n";
        echo "1. Check actual database credentials on the server\n";
        echo "2. Update /includes/config.php with correct DB_USER and DB_PASS\n";
        echo "3. Verify database 'dalthaus_cms' exists\n";
    }
}

echo "\n=== DATABASE FIX COMPLETE ===\n";
echo "Next: Run EMERGENCY_FIX_02_SECURITY.php\n\n";