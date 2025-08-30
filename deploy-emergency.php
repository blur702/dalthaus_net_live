<?php
/**
 * MINIMAL EMERGENCY DEPLOYMENT SCRIPT
 * Upload this file to the server and run it to deploy emergency fixes
 * 
 * Instructions:
 * 1. Upload this file to the server root
 * 2. Visit: https://dalthaus.net/deploy-emergency.php?action=deploy&token=fix2025
 * 3. Check the output and follow next steps
 */

// Security token
if ($_GET['token'] !== 'fix2025') {
    http_response_code(401);
    die("Access denied");
}

header('Content-Type: text/plain; charset=utf-8');
echo "EMERGENCY FIX DEPLOYMENT\n";
echo str_repeat("=", 40) . "\n";

$action = $_GET['action'] ?? 'info';

if ($action === 'info') {
    echo "Available actions:\n";
    echo "- deploy: Download and run emergency fixes\n";
    echo "- test: Test site functionality\n";
    echo "\nUsage: ?action=deploy&token=fix2025\n";
    exit;
}

if ($action === 'deploy') {
    echo "Starting emergency fix deployment...\n\n";
    
    // Download emergency fix files from GitHub
    $files = [
        'EMERGENCY_MASTER_FIX.php' => 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_MASTER_FIX.php',
        'EMERGENCY_FIX_01_DATABASE.php' => 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_01_DATABASE.php',
        'EMERGENCY_FIX_02_SECURITY.php' => 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_02_SECURITY.php',
        'EMERGENCY_FIX_03_CLEANUP.php' => 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_03_CLEANUP.php',
        'EMERGENCY_FIX_04_VERIFY.php' => 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_04_VERIFY.php'
    ];
    
    echo "Downloading emergency fix files...\n";
    foreach ($files as $filename => $url) {
        echo "Downloading $filename... ";
        $content = @file_get_contents($url);
        if ($content === false) {
            echo "FAILED\n";
            die("ERROR: Could not download $filename from GitHub");
        }
        
        if (file_put_contents($filename, $content) === false) {
            echo "WRITE FAILED\n";
            die("ERROR: Could not write $filename to server");
        }
        
        echo "OK\n";
    }
    
    echo "\nAll files downloaded successfully!\n\n";
    
    // Execute the master fix
    echo "Executing emergency fixes...\n";
    echo str_repeat("-", 40) . "\n";
    
    $output = [];
    $return = 0;
    exec('php EMERGENCY_MASTER_FIX.php 2>&1', $output, $return);
    
    echo implode("\n", $output) . "\n";
    
    echo "\n" . str_repeat("=", 40) . "\n";
    if ($return === 0) {
        echo "✓ EMERGENCY FIXES COMPLETED SUCCESSFULLY!\n\n";
        echo "NEXT STEPS:\n";
        echo "1. Test: https://dalthaus.net/\n";
        echo "2. Admin: https://dalthaus.net/admin/\n";
        echo "3. Verify setup blocked: https://dalthaus.net/setup.php\n\n";
        echo "To clean up:\n";
        echo "- Delete EMERGENCY_*.php files\n";
        echo "- Delete this deploy-emergency.php file\n";
    } else {
        echo "✗ EMERGENCY FIXES FAILED\n";
        echo "Exit code: $return\n";
        echo "Check output above for errors\n";
    }
}

if ($action === 'test') {
    echo "Testing site functionality...\n\n";
    
    // Test database connection
    echo "Testing database connection... ";
    if (file_exists('includes/config.php')) {
        include_once 'includes/config.php';
        if (defined('DB_NAME')) {
            echo "Config loaded ✓\n";
        } else {
            echo "Config missing ✗\n";
        }
    } else {
        echo "Config not found ✗\n";
    }
    
    // Test key files
    $critical_files = [
        'index.php' => 'Main site file',
        'admin/login.php' => 'Admin login',
        '.htaccess' => 'URL rewriting'
    ];
    
    echo "\nTesting critical files:\n";
    foreach ($critical_files as $file => $desc) {
        echo "- $desc ($file): ";
        echo file_exists($file) ? "✓" : "✗";
        echo "\n";
    }
    
    echo "\nSite status check complete.\n";
}

echo "\nTime: " . date('Y-m-d H:i:s') . "\n";