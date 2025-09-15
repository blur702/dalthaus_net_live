<?php
session_start();

// Include the autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/config.php';

use CMS\Utils\Database;
use CMS\Utils\Auth;

echo "<h1>Auth Failure Debug</h1>\n";

try {
    // Initialize database
    $db = Database::getInstance($config['database']);
    echo "<p>✓ Database connection successful</p>\n";
    
    // Initialize Auth
    $auth = new Auth($db, $config['security']);
    echo "<p>✓ Auth class initialized</p>\n";
    
    // Test user lookup
    echo "<h2>User Lookup Test</h2>\n";
    $userQuery = "SELECT user_id, username, email, password_hash, created_at FROM users WHERE username = ? OR email = ?";
    $user = $db->fetchRow($userQuery, ['kevin', 'kevin']);
    
    if ($user) {
        echo "<p>✓ User 'kevin' found in database</p>\n";
        echo "<pre>User data: " . print_r([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'password_hash' => substr($user['password_hash'], 0, 20) . '...',
            'created_at' => $user['created_at']
        ], true) . "</pre>\n";
    } else {
        echo "<p>❌ User 'kevin' NOT found in database</p>\n";
        
        // List all users
        $allUsers = $db->fetchAll("SELECT user_id, username, email FROM users");
        echo "<p>All users in database:</p>\n";
        echo "<pre>" . print_r($allUsers, true) . "</pre>\n";
    }
    
    // Test password verification if user exists
    if ($user) {
        echo "<h2>Password Verification Test</h2>\n";
        $testPassword = '(130Bpm)';
        $verified = password_verify($testPassword, $user['password_hash']);
        
        if ($verified) {
            echo "<p>✓ Password verification successful</p>\n";
        } else {
            echo "<p>❌ Password verification failed</p>\n";
            echo "<p>Stored hash: " . $user['password_hash'] . "</p>\n";
            echo "<p>Test password: " . htmlspecialchars($testPassword) . "</p>\n";
            
            // Try generating a new hash to compare
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "<p>New hash for same password: " . $newHash . "</p>\n";
            echo "<p>New hash verifies: " . (password_verify($testPassword, $newHash) ? 'YES' : 'NO') . "</p>\n";
        }
    }
    
    // Test lockout status
    echo "<h2>Lockout Status Test</h2>\n";
    $remainingLockout = $auth->getRemainingLockoutTime('kevin');
    if ($remainingLockout > 0) {
        echo "<p>⚠️ User 'kevin' is locked out for " . $remainingLockout . " seconds</p>\n";
        echo "<p>Clearing lockout...</p>\n";
        $auth->clearFailedLoginAttempts('kevin');
        $remainingLockout = $auth->getRemainingLockoutTime('kevin');
        echo "<p>Lockout after clearing: " . $remainingLockout . " seconds</p>\n";
    } else {
        echo "<p>✓ User 'kevin' is not locked out</p>\n";
    }
    
    // Test session state before attempt
    echo "<h2>Session State Before Auth Attempt</h2>\n";
    echo "<pre>Session before: " . print_r($_SESSION, true) . "</pre>\n";
    
    // Test full authentication attempt
    echo "<h2>Full Authentication Attempt</h2>\n";
    $authResult = $auth->attempt('kevin', '(130Bpm)');
    
    if ($authResult) {
        echo "<p>✓ Authentication attempt successful</p>\n";
    } else {
        echo "<p>❌ Authentication attempt failed</p>\n";
    }
    
    // Test session state after attempt
    echo "<h2>Session State After Auth Attempt</h2>\n";
    echo "<pre>Session after: " . print_r($_SESSION, true) . "</pre>\n";
    
    // Test Auth::check() method
    echo "<h2>Auth Check Test</h2>\n";
    $checkResult = $auth->check();
    echo "<p>Auth::check() result: " . ($checkResult ? 'TRUE' : 'FALSE') . "</p>\n";
    
    if (!$checkResult) {
        echo "<p>Debugging Auth::check() failure:</p>\n";
        echo "<ul>\n";
        echo "<li>logged_in isset: " . (isset($_SESSION['logged_in']) ? 'YES' : 'NO') . "</li>\n";
        echo "<li>logged_in value: " . ($_SESSION['logged_in'] ?? 'NOT SET') . "</li>\n";
        echo "<li>logged_in empty(): " . (empty($_SESSION['logged_in']) ? 'YES (FAILS)' : 'NO (PASSES)') . "</li>\n";
        echo "</ul>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exception occurred: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>