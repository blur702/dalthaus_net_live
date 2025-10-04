<?php
// Debug script to test Remember Me functionality
require_once __DIR__ . '/vendor/autoload.php';

echo "<h1>Remember Me Functionality Debug</h1>\n";
echo "<pre>\n";

// Load configuration
$config = require __DIR__ . '/config/config.php';

// Check database connection
try {
    $db = \CMS\Utils\Database::getInstance($config['database']);
    echo "✅ Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Check if remember_tokens table exists
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'remember_tokens'");
    if ($tableCheck->rowCount() > 0) {
        echo "✅ remember_tokens table exists\n";
        
        // Check table structure
        $columns = $db->query("DESCRIBE remember_tokens")->fetchAll();
        echo "\nTable structure:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
        
        // Check for any existing tokens
        $tokenCount = $db->query("SELECT COUNT(*) as count FROM remember_tokens")->fetch();
        echo "Current remember tokens in database: {$tokenCount['count']}\n\n";
    } else {
        echo "❌ remember_tokens table does NOT exist\n";
        echo "Creating remember_tokens table...\n";
        
        $createTable = "
        CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_token_hash (token_hash),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $db->exec($createTable);
            echo "✅ Table created successfully\n\n";
        } catch (Exception $e) {
            echo "❌ Failed to create table: " . $e->getMessage() . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking remember_tokens table: " . $e->getMessage() . "\n\n";
}

// Check current session
session_start();
echo "Session Information:\n";
echo "  Session ID: " . session_id() . "\n";
echo "  Session Name: " . session_name() . "\n";
if (isset($_SESSION['user_id'])) {
    echo "  ✅ User logged in: ID=" . $_SESSION['user_id'] . ", Username=" . ($_SESSION['username'] ?? 'N/A') . "\n";
    echo "  Login time: " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'N/A') . "\n";
    echo "  Remembered login: " . (isset($_SESSION['remembered']) && $_SESSION['remembered'] ? 'Yes' : 'No') . "\n";
} else {
    echo "  ❌ No active session\n";
}
echo "\n";

// Check cookies
echo "Cookie Information:\n";
if (isset($_COOKIE['remember_token'])) {
    echo "  ✅ Remember token cookie exists\n";
    $parts = explode(':', $_COOKIE['remember_token'], 2);
    if (count($parts) === 2) {
        echo "  Cookie format: Valid (user_id:token)\n";
        echo "  User ID in cookie: {$parts[0]}\n";
        
        // Check if token exists in database
        $hashedToken = hash('sha256', $parts[1]);
        $tokenCheck = $db->fetchRow(
            "SELECT * FROM remember_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()",
            [(int)$parts[0], $hashedToken]
        );
        
        if ($tokenCheck) {
            echo "  ✅ Token is valid in database\n";
            echo "  Token expires: {$tokenCheck['expires_at']}\n";
        } else {
            echo "  ❌ Token NOT found or expired in database\n";
        }
    } else {
        echo "  ❌ Cookie format invalid\n";
    }
} else {
    echo "  ❌ No remember token cookie\n";
}
echo "\n";

// Check server configuration
echo "Server Configuration:\n";
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
echo "  Protocol: " . ($isHttps ? 'HTTPS' : 'HTTP') . "\n";
echo "  secure_cookies setting: " . ($config['security']['secure_cookies'] ? 'true' : 'false') . "\n";
if (!$isHttps && $config['security']['secure_cookies']) {
    echo "  ⚠️  WARNING: secure_cookies is TRUE but site is using HTTP - cookies won't be set!\n";
}
echo "\n";

// Test Auth utility methods
echo "Testing Auth Utility:\n";
$auth = new \CMS\Utils\Auth($db, $config['security']);

// Test check() method
$isAuthenticated = $auth->check();
echo "  auth->check() result: " . ($isAuthenticated ? '✅ Authenticated' : '❌ Not authenticated') . "\n";

// Test user() method
$user = $auth->user();
if ($user) {
    echo "  auth->user() result: User ID={$user['user_id']}, Username={$user['username']}\n";
} else {
    echo "  auth->user() result: null\n";
}
echo "\n";

// Test BaseController isAuthenticated
echo "Testing BaseController:\n";
// We can't instantiate BaseController directly as it's abstract, but we can check its method logic
echo "  BaseController checks both Auth->check() and session variables\n";
echo "  Current BaseController would return: ";
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
    echo "✅ Authenticated (via session)\n";
} else {
    echo "❌ Not authenticated (session check failed)\n";
}

echo "\n";
echo "DIAGNOSIS:\n";
echo "===========\n";

// Identify the issue
$issues = [];

if (!$isHttps && $config['security']['secure_cookies']) {
    $issues[] = "secure_cookies is TRUE but site uses HTTP - cookies cannot be set";
}

if (!isset($_COOKIE['remember_token']) && isset($_SESSION['user_id'])) {
    $issues[] = "User is logged in but no Remember Me cookie exists";
}

if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $issues[] = "Remember Me cookie exists but user is not logged in - auto-login may be failing";
}

// Check if BaseController is using Auth::check()
$baseControllerPath = __DIR__ . '/src/Controllers/BaseController.php';
$baseControllerContent = file_get_contents($baseControllerPath);
if (!str_contains($baseControllerContent, '$this->auth->check()')) {
    $issues[] = "BaseController may not be calling Auth::check() method which handles Remember Me";
}

if (empty($issues)) {
    echo "✅ No issues detected - Remember Me should be working correctly\n";
} else {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "  ❌ $issue\n";
    }
}

echo "\n</pre>";

// Add test login form
?>
<hr>
<h2>Test Login with Remember Me</h2>
<form action="/admin/login" method="POST">
    <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?? '' ?>">
    <p>
        <label>Username: <input type="text" name="username" value="kevin"></label>
    </p>
    <p>
        <label>Password: <input type="password" name="password"></label>
    </p>
    <p>
        <label><input type="checkbox" name="remember_me" value="1" checked> Remember me for 30 days</label>
    </p>
    <p>
        <button type="submit">Test Login</button>
    </p>
</form>

<hr>
<h3>Test Actions:</h3>
<p>
    <a href="/admin/dashboard">Go to Admin Dashboard</a> (tests if Remember Me auto-login works)<br>
    <a href="/admin/logout">Logout</a> (clears session and Remember Me cookie)<br>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Refresh this page</a>
</p>