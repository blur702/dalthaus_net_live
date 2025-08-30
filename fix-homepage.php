<?php
/**
 * Fix Homepage 500 Error
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

echo "<h1>Fixing Homepage 500 Error</h1><pre>";

// Step 1: Check index.php
echo "1. Checking index.php...\n";
$index_check = callAgent($agent_url, $token, 'read', ['path' => 'index.php']);
if ($index_check['success']) {
    $content = $index_check['content'];
    
    // Check for common issues
    if (strpos($content, '#!/usr/bin/env php') !== false) {
        echo "   Found shebang line - removing...\n";
        $content = str_replace("#!/usr/bin/env php\n", '', $content);
        $content = str_replace("#!/usr/bin/env php\r\n", '', $content);
        
        $write_result = callAgent($agent_url, $token, 'write', [
            'path' => 'index.php',
            'content' => $content
        ]);
        
        if ($write_result['success']) {
            echo "   ✅ Fixed shebang issue\n";
        }
    }
    
    // Check if it's trying to include router.php incorrectly
    if (strpos($content, "require_once 'router.php'") !== false) {
        echo "   Found router.php reference - this should not be in index.php\n";
    }
    
    echo "   File size: " . strlen($content) . " bytes\n";
} else {
    echo "   ❌ Could not read index.php\n";
}

// Step 2: Check config files
echo "\n2. Checking configuration...\n";
$config_local = callAgent($agent_url, $token, 'exists', ['path' => 'includes/config.local.php']);
if ($config_local['success'] && $config_local['exists']) {
    echo "   ✅ config.local.php exists\n";
    
    // Read and check for issues
    $config_content = callAgent($agent_url, $token, 'read', ['path' => 'includes/config.local.php']);
    if ($config_content['success']) {
        if (strpos($config_content['content'], 'localhost') !== false) {
            echo "   Found 'localhost' in config - this is correct for shared hosting\n";
        }
        if (strpos($config_content['content'], '127.0.0.1') !== false) {
            echo "   ⚠️  Found '127.0.0.1' - changing to 'localhost'...\n";
            $fixed_config = str_replace("'127.0.0.1'", "'localhost'", $config_content['content']);
            callAgent($agent_url, $token, 'write', [
                'path' => 'includes/config.local.php',
                'content' => $fixed_config
            ]);
            echo "   ✅ Updated to use 'localhost'\n";
        }
    }
}

// Step 3: Test with a simple index
echo "\n3. Creating test index to verify server works...\n";
$test_index = '<?php
// Simple test index
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h1>Test Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server is working!</p>";

// Try to include config
if (file_exists("includes/config.local.php")) {
    require_once "includes/config.local.php";
    echo "<p>Config loaded successfully</p>";
    
    // Test database
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn) {
        echo "<p>✅ Database connection successful</p>";
        mysqli_close($conn);
    } else {
        echo "<p>❌ Database connection failed: " . mysqli_connect_error() . "</p>";
    }
} else {
    echo "<p>Config file not found</p>";
}
?>';

$write_test = callAgent($agent_url, $token, 'write', [
    'path' => 'test-index.php',
    'content' => $test_index
]);

if ($write_test['success']) {
    echo "   ✅ Created test-index.php\n";
    echo "   Visit: https://dalthaus.net/test-index.php\n";
}

// Step 4: Check actual error
echo "\n4. Checking for actual error...\n";

// Create error display script
$error_script = '<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h2>Attempting to load index.php</h2><pre>";

// Include the actual index.php and catch errors
try {
    ob_start();
    require_once "index.php";
    $output = ob_get_clean();
    echo "Success! Output:\n";
    echo htmlspecialchars(substr($output, 0, 500));
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";

// Show last error
$error = error_get_last();
if ($error) {
    echo "<h3>Last Error:</h3><pre>";
    print_r($error);
    echo "</pre>";
}
?>';

$write_error = callAgent($agent_url, $token, 'write', [
    'path' => 'show-error.php',
    'content' => $error_script
]);

if ($write_error['success']) {
    echo "   ✅ Created show-error.php\n";
    echo "   Visit: https://dalthaus.net/show-error.php to see the actual error\n";
}

echo "\n5. Quick fixes applied. Check these URLs:\n";
echo "   - https://dalthaus.net/test-index.php (should work)\n";
echo "   - https://dalthaus.net/show-error.php (shows actual error)\n";
echo "   - https://dalthaus.net/ (main site)\n";

echo "</pre>";
?>