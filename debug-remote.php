<?php
/**
 * Remote Debugging Script for Shared Hosting
 * Upload this to your shared hosting root to diagnose 500 errors
 * Access via: https://yourdomain.com/debug-remote.php
 * DELETE AFTER DEBUGGING!
 */

// Security - Add your IP or remove in production
$allowed_ips = ['YOUR_IP_HERE']; // Add your IP address
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !in_array('YOUR_IP_HERE', $allowed_ips)) {
    die('Access denied');
}

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<pre>";
echo "==============================================\n";
echo "DALTHAUS.NET CMS - REMOTE DEBUGGING TOOL\n";
echo "==============================================\n\n";

// 1. PHP Version and Extensions
echo "1. PHP ENVIRONMENT\n";
echo "-------------------\n";
echo "PHP Version: " . phpversion() . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n\n";

// 2. Required Extensions Check
echo "2. REQUIRED EXTENSIONS\n";
echo "----------------------\n";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'curl', 'gd', 'json'];
foreach ($required_extensions as $ext) {
    echo sprintf("%-15s: %s\n", $ext, extension_loaded($ext) ? '✓ Loaded' : '✗ MISSING');
}
echo "\n";

// 3. File Permissions Check
echo "3. FILE PERMISSIONS\n";
echo "-------------------\n";
$dirs_to_check = ['uploads', 'cache', 'logs', 'temp', 'includes'];
foreach ($dirs_to_check as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? 'Writable' : 'NOT WRITABLE';
        echo sprintf("%-15s: %s (%s)\n", $dir, $perms, $writable);
    } else {
        echo sprintf("%-15s: MISSING\n", $dir);
    }
}
echo "\n";

// 4. Database Connection Test
echo "4. DATABASE CONNECTION\n";
echo "----------------------\n";
if (file_exists('includes/config.php')) {
    // Suppress errors temporarily
    $old_error = error_reporting(0);
    include_once 'includes/config.php';
    error_reporting($old_error);
    
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
    
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connection: ✓ SUCCESS\n";
            
            // Check tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Tables found: " . count($tables) . "\n";
            if (count($tables) > 0) {
                echo "Tables: " . implode(', ', array_slice($tables, 0, 5)) . "...\n";
            }
        } catch (PDOException $e) {
            echo "Connection: ✗ FAILED\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Connection: ✗ Config constants missing\n";
    }
} else {
    echo "config.php: NOT FOUND\n";
}
echo "\n";

// 5. .htaccess Check
echo "5. HTACCESS CHECK\n";
echo "-----------------\n";
if (file_exists('.htaccess')) {
    echo ".htaccess: EXISTS\n";
    $htaccess = file_get_contents('.htaccess');
    if (strpos($htaccess, 'RewriteEngine') !== false) {
        echo "RewriteEngine: Found\n";
    } else {
        echo "RewriteEngine: NOT FOUND (may cause routing issues)\n";
    }
} else {
    echo ".htaccess: MISSING (routing won't work)\n";
}
echo "\n";

// 6. Recent Error Logs
echo "6. RECENT ERROR LOGS\n";
echo "--------------------\n";
$log_file = 'logs/app.log';
if (file_exists($log_file) && is_readable($log_file)) {
    $logs = file($log_file);
    $recent = array_slice($logs, -10);
    foreach ($recent as $log) {
        echo trim($log) . "\n";
    }
} else {
    echo "No application logs found\n";
}

// Check PHP error log
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log) && is_readable($error_log)) {
    echo "\nPHP Error Log (last 5 entries):\n";
    $errors = file($error_log);
    $recent_errors = array_slice($errors, -5);
    foreach ($recent_errors as $error) {
        echo trim($error) . "\n";
    }
}
echo "\n";

// 7. Session Check
echo "7. SESSION CHECK\n";
echo "----------------\n";
echo "Session Save Path: " . session_save_path() . "\n";
$session_path = session_save_path();
if ($session_path) {
    echo "Session Path Writable: " . (is_writable($session_path) ? 'YES' : 'NO') . "\n";
}
@session_start();
echo "Session ID: " . session_id() . "\n";
echo "\n";

// 8. Include Path
echo "8. INCLUDE PATHS\n";
echo "----------------\n";
echo get_include_path() . "\n\n";

// 9. Server Variables
echo "9. SERVER INFO\n";
echo "--------------\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";

echo "\n==============================================\n";
echo "Remember to DELETE this file after debugging!\n";
echo "==============================================\n";
echo "</pre>";

// Test a specific file if requested
if (isset($_GET['test'])) {
    echo "<hr><h3>Testing specific file: " . htmlspecialchars($_GET['test']) . "</h3><pre>";
    $test_file = basename($_GET['test']) . '.php';
    if (file_exists($test_file)) {
        echo "Attempting to include $test_file...\n";
        try {
            ob_start();
            include $test_file;
            $output = ob_get_clean();
            echo "SUCCESS - Output:\n" . htmlspecialchars($output);
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage();
        }
    } else {
        echo "File not found: $test_file";
    }
    echo "</pre>";
}