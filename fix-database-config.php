<?php
/**
 * Fix Database Configuration
 */

$token = 'agent-' . date('Ymd');
$agent_url = 'https://dalthaus.net/remote-file-agent.php';

function callAgent($url, $token, $action, $params = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge(
        ['action' => $action, 'token' => $token],
        $params
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

echo "<h1>Fixing Database Configuration</h1><pre>";

// The correct credentials based on our previous work
$correct_config = "<?php
// Local configuration overrides
// This file is not tracked in git

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dalthaus_cms');
define('DB_USER', 'kevin');
define('DB_PASS', '(130Bpm)');

// Environment
define('ENV', 'production');

// Admin defaults (for setup)
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Logging
define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR
define('CACHE_ENABLED', true);
";

echo "1. Writing correct database configuration...\n";

// Write the correct config
$write_result = callAgent($agent_url, $token, 'write', [
    'path' => 'includes/config.local.php',
    'content' => $correct_config
]);

if ($write_result['success']) {
    echo "   ✅ Updated config.local.php with correct credentials\n";
    echo "   - Host: localhost\n";
    echo "   - User: kevin\n";
    echo "   - Pass: (130Bpm)\n";
    echo "   - Database: dalthaus_cms\n";
} else {
    echo "   ❌ Failed to write config\n";
}

// Also update main config.php as backup
echo "\n2. Updating main config.php as backup...\n";

$main_config = callAgent($agent_url, $token, 'read', ['path' => 'includes/config.php']);
if ($main_config['success']) {
    $content = $main_config['content'];
    
    // Update the credentials
    $content = preg_replace("/define\('DB_HOST',\s*'[^']+'\)/", "define('DB_HOST', 'localhost')", $content);
    $content = preg_replace("/define\('DB_USER',\s*'[^']+'\)/", "define('DB_USER', 'kevin')", $content);
    $content = preg_replace("/define\('DB_PASS',\s*'[^']+'\)/", "define('DB_PASS', '(130Bpm)')", $content);
    $content = preg_replace("/define\('DB_NAME',\s*'[^']+'\)/", "define('DB_NAME', 'dalthaus_cms')", $content);
    
    $write_main = callAgent($agent_url, $token, 'write', [
        'path' => 'includes/config.php',
        'content' => $content
    ]);
    
    if ($write_main['success']) {
        echo "   ✅ Updated main config.php\n";
    }
}

// Test the connection
echo "\n3. Testing database connection...\n";

$test_script = '<?php
require_once "includes/config.local.php";
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn) {
    echo "SUCCESS|";
    
    // Count tables
    $result = mysqli_query($conn, "SHOW TABLES");
    $count = mysqli_num_rows($result);
    echo $count . " tables|";
    
    // Check for content
    $content = mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE status = \'published\'");
    if ($content) {
        $row = mysqli_fetch_assoc($content);
        echo $row["count"] . " published items";
    }
    
    mysqli_close($conn);
} else {
    echo "FAILED|" . mysqli_connect_error();
}
?>';

$write_test = callAgent($agent_url, $token, 'write', [
    'path' => 'test-db-final.php',
    'content' => $test_script
]);

if ($write_test['success']) {
    // Run the test
    $result = file_get_contents('https://dalthaus.net/test-db-final.php');
    
    if (strpos($result, 'SUCCESS') === 0) {
        $parts = explode('|', $result);
        echo "   ✅ Database connection SUCCESSFUL!\n";
        echo "   - " . ($parts[1] ?? 'Unknown tables') . "\n";
        echo "   - " . ($parts[2] ?? 'Unknown content') . "\n";
    } else {
        echo "   ❌ Database connection failed: " . str_replace('FAILED|', '', $result) . "\n";
        echo "\n   Trying alternative credentials...\n";
        
        // Try dalthaus_user
        $alt_config = str_replace("define('DB_USER', 'kevin')", "define('DB_USER', 'dalthaus_user')", $correct_config);
        $write_alt = callAgent($agent_url, $token, 'write', [
            'path' => 'includes/config.local.php',
            'content' => $alt_config
        ]);
        
        if ($write_alt['success']) {
            $result2 = file_get_contents('https://dalthaus.net/test-db-final.php');
            if (strpos($result2, 'SUCCESS') === 0) {
                echo "   ✅ Success with dalthaus_user!\n";
            } else {
                echo "   ❌ Also failed with dalthaus_user\n";
            }
        }
    }
    
    // Clean up
    callAgent($agent_url, $token, 'delete', ['path' => 'test-db-final.php']);
}

echo "\n4. Testing homepage...\n";

// Check if homepage works now
$homepage_test = @file_get_contents('https://dalthaus.net/');
if ($homepage_test && strpos($homepage_test, '500 Internal Server Error') === false) {
    echo "   ✅ Homepage is loading!\n";
} else {
    echo "   ⚠️  Homepage still showing error\n";
    echo "   Check: https://dalthaus.net/show-error.php for details\n";
}

echo "\n5. Final URLs to check:\n";
echo "   - https://dalthaus.net/ (main site)\n";
echo "   - https://dalthaus.net/test-index.php (test page)\n";
echo "   - https://dalthaus.net/admin/login.php (admin panel)\n";
echo "   - https://dalthaus.net/debug-production.php (debug tool)\n";

echo "</pre>";

// Clean up test files
echo "<h2>Cleanup</h2><pre>";
$test_files = ['test-index.php', 'show-error.php'];
foreach ($test_files as $file) {
    $delete = callAgent($agent_url, $token, 'delete', ['path' => $file]);
    if ($delete['success']) {
        echo "Deleted $file\n";
    }
}
echo "</pre>";
?>