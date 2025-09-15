<?php
/**
 * Live Authentication Debug Tool
 * Tests each step of the authentication process
 */

// Start session
session_start();

// Load configuration
$config = require __DIR__ . '/config/config.php';

echo "<!DOCTYPE html><html><head><title>Live Auth Debug</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";
echo "</head><body>";

echo "<h1>Live Authentication Debug</h1>";

try {
    // Step 1: Test database connection
    echo "<h2>Step 1: Database Connection</h2>";
    
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['dbname'],
        $config['database']['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );
    
    echo "<p class='success'>✓ Database connection successful</p>";
    echo "<p class='info'>Database: {$config['database']['dbname']}</p>";
    echo "<p class='info'>Username: {$config['database']['username']}</p>";
    
    // Step 2: Test user lookup
    echo "<h2>Step 2: User Lookup</h2>";
    
    $userQuery = "SELECT user_id, username, email, password_hash, created_at FROM users WHERE username = ? OR email = ?";
    $stmt = $pdo->prepare($userQuery);
    $stmt->execute(['kevin', 'kevin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p class='success'>✓ User 'kevin' found in database</p>";
        echo "<pre>User Data:\n";
        echo "ID: " . $user['user_id'] . "\n";
        echo "Username: " . htmlspecialchars($user['username']) . "\n";
        echo "Email: " . htmlspecialchars($user['email']) . "\n";
        echo "Password Hash: " . substr($user['password_hash'], 0, 30) . "...\n";
        echo "Created: " . $user['created_at'] . "\n";
        echo "</pre>";
        
        // Step 3: Test password verification
        echo "<h2>Step 3: Password Verification</h2>";
        
        $testPassword = '(130Bpm)';
        $verified = password_verify($testPassword, $user['password_hash']);
        
        if ($verified) {
            echo "<p class='success'>✓ Password verification successful</p>";
            echo "<p class='info'>Password: " . htmlspecialchars($testPassword) . " ✓</p>";
        } else {
            echo "<p class='error'>❌ Password verification failed</p>";
            echo "<p class='info'>Test Password: " . htmlspecialchars($testPassword) . "</p>";
            echo "<p class='info'>Stored Hash: " . htmlspecialchars($user['password_hash']) . "</p>";
            
            // Test with common variations
            $variations = [
                '(130Bpm)',
                '130Bpm',
                'kevin',
                'admin',
                'password',
                'admin123'
            ];
            
            echo "<h3>Testing Password Variations:</h3>";
            foreach ($variations as $variation) {
                $testResult = password_verify($variation, $user['password_hash']);
                $status = $testResult ? "<span class='success'>✓</span>" : "<span class='error'>❌</span>";
                echo "<p>{$status} '" . htmlspecialchars($variation) . "'</p>";
            }
        }
        
    } else {
        echo "<p class='error'>❌ User 'kevin' not found in database</p>";
        
        // List all users
        echo "<h3>All Users in Database:</h3>";
        $allUsersStmt = $pdo->query("SELECT user_id, username, email, created_at FROM users ORDER BY user_id");
        $allUsers = $allUsersStmt->fetchAll();
        
        if (empty($allUsers)) {
            echo "<p class='error'>❌ No users found in database at all!</p>";
        } else {
            echo "<pre>";
            foreach ($allUsers as $u) {
                echo "ID: {$u['user_id']} | Username: " . htmlspecialchars($u['username']) . " | Email: " . htmlspecialchars($u['email']) . " | Created: {$u['created_at']}\n";
            }
            echo "</pre>";
        }
    }
    
    // Step 4: Test session state
    echo "<h2>Step 4: Session State</h2>";
    echo "<pre>Current Session:\n";
    print_r($_SESSION);
    echo "</pre>";
    
    // Step 5: Test manual authentication simulation
    if ($user && $verified) {
        echo "<h2>Step 5: Manual Authentication Simulation</h2>";
        
        // Simulate what Auth::startSession() does
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        echo "<p class='success'>✓ Session variables manually set</p>";
        echo "<pre>Session after manual setup:\n";
        print_r($_SESSION);
        echo "</pre>";
        
        // Test if this would pass authentication
        $wouldPass = !empty($_SESSION['logged_in']);
        if ($wouldPass) {
            echo "<p class='success'>✓ Manual session would pass authentication check</p>";
            echo "<p class='info'><a href='/admin/dashboard'>Test Dashboard Access</a></p>";
        } else {
            echo "<p class='error'>❌ Manual session would still fail authentication</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ General Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='/admin/login'>Back to Login</a> | <a href='/debug_dashboard.php'>Dashboard Debug</a></p>";
echo "</body></html>";
?>