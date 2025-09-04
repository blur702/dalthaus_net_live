<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== PHP Error Diagnostic ===\n\n";

// Test basic PHP
echo "1. Basic PHP: Working\n";

// Test includes
echo "\n2. Testing includes:\n";

// Test config
if (file_exists('includes/config.php')) {
    echo "  - config.php exists\n";
    require_once 'includes/config.php';
    echo "  - config.php loaded\n";
} else {
    echo "  - ERROR: config.php not found\n";
}

// Test database
if (file_exists('includes/database.php')) {
    echo "  - database.php exists\n";
    require_once 'includes/database.php';
    echo "  - database.php loaded\n";
    
    // Check PDO
    if (isset($pdo)) {
        echo "  - Database connection exists\n";
    } else {
        echo "  - ERROR: No database connection\n";
    }
} else {
    echo "  - ERROR: database.php not found\n";
}

// Test functions
if (file_exists('includes/functions.php')) {
    echo "  - functions.php exists\n";
    require_once 'includes/functions.php';
    echo "  - functions.php loaded\n";
    
    // Check if getSetting exists
    if (function_exists('getSetting')) {
        echo "  - getSetting function exists\n";
        
        // Try to use it
        try {
            $testValue = getSetting('site_title', 'Default');
            echo "  - getSetting works: " . $testValue . "\n";
        } catch (Exception $e) {
            echo "  - ERROR calling getSetting: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  - ERROR: getSetting function not found\n";
    }
} else {
    echo "  - ERROR: functions.php not found\n";
}

echo "\n3. PHP Version: " . phpversion() . "\n";
echo "\nDiagnostic complete.\n";
?>