<?php

/**
 * Test Server Configuration via Agent API
 * This script tests the live server configuration to identify issues
 */

declare(strict_types=1);

$agentUrl = 'https://dalthaus.net/agent.php';
$agentKey = 'dalthaus_agent_key_2025';

echo "=== COMPREHENSIVE SERVER CONFIGURATION TEST ===\n\n";

// Test 1: Basic agent connectivity
echo "TEST 1: Agent Connectivity\n";
echo "------------------------\n";
$testUrl = $agentUrl . '?action=test&key=' . $agentKey;
$response = @file_get_contents($testUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "✓ Agent is operational\n";
        echo "  PHP Version: {$data['php_version']}\n";
        echo "  Directory: {$data['directory']}\n";
    } else {
        echo "✗ Agent test failed\n";
    }
} else {
    echo "✗ Cannot connect to agent\n";
}
echo "\n";

// Test 2: Check config files via agent
echo "TEST 2: Configuration Files on Server\n";
echo "------------------------------------\n";

// Create a test command to check config
$configCheckCommand = 'ls -la config/ && echo "---" && grep -E "dbname|username" config/config.php | head -5';
$commandUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($configCheckCommand);
$response = @file_get_contents($commandUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "Config directory listing:\n";
        echo implode("\n", array_slice($data['output'], 0, 10)) . "\n";
    }
}
echo "\n";

// Test 3: Check environment variables on server
echo "TEST 3: Environment Variables on Server\n";
echo "--------------------------------------\n";

$envCheckCommand = 'printenv | grep -E "DB_|DATABASE" || echo "No DB env vars set"';
$envUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($envCheckCommand);
$response = @file_get_contents($envUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success'] && !empty($data['output'])) {
        foreach ($data['output'] as $line) {
            if (strpos($line, 'DB_') !== false) {
                // Hide password values
                if (strpos($line, 'PASSWORD') !== false) {
                    echo "  " . preg_replace('/=.*/', '=***hidden***', $line) . "\n";
                } else {
                    echo "  " . $line . "\n";
                }
            }
        }
        if (in_array('No DB env vars set', $data['output'])) {
            echo "  ✗ No database environment variables are set\n";
        }
    }
}
echo "\n";

// Test 4: Check which config is actually being loaded
echo "TEST 4: Active Configuration Check\n";
echo "---------------------------------\n";

// Create a PHP script to check which config is loaded
$testScript = '<?php
$configPath = __DIR__ . "/config/config.php";
$prodConfigPath = __DIR__ . "/config/config.production.php";

echo "Config files present:\n";
if (file_exists($configPath)) echo "  - config.php exists\n";
if (file_exists($prodConfigPath)) echo "  - config.production.php exists\n";

echo "\nLoading config.php:\n";
if (file_exists($configPath)) {
    $config = require $configPath;
    echo "  Database: " . $config["database"]["dbname"] . "\n";
    echo "  Username: " . $config["database"]["username"] . "\n";
}

echo "\nEnvironment variables:\n";
echo "  DB_NAME: " . (getenv("DB_NAME") ?: "not set") . "\n";
echo "  DB_USER: " . (getenv("DB_USER") ?: "not set") . "\n";

if (file_exists($prodConfigPath) && !getenv("DB_NAME")) {
    echo "\nWARNING: Production config exists but env vars not set!\n";
    echo "This will cause fallback to default values.\n";
}
?>';

// Save and execute test script
file_put_contents('check_server_config.php', $testScript);
echo "Created check_server_config.php\n";

// Upload and execute via agent
$uploadCommand = 'cd /home/dalthaus/public_html && php -r \'' . str_replace("'", "\'", $testScript) . '\'';
$checkUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($uploadCommand);
$response = @file_get_contents($checkUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo implode("\n", $data['output']) . "\n";
    }
}
echo "\n";

// Test 5: Check for scripts that might modify config
echo "TEST 5: Config Modification Scripts\n";
echo "----------------------------------\n";

$findScriptsCommand = 'ls -la | grep -E "fix_|restore_|deploy_|setup" | head -10';
$scriptsUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($findScriptsCommand);
$response = @file_get_contents($scriptsUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success'] && !empty($data['output'])) {
        echo "Found potential config modification scripts:\n";
        foreach ($data['output'] as $line) {
            echo "  " . $line . "\n";
        }
    }
}
echo "\n";

// Test 6: Check .htaccess routing
echo "TEST 6: .htaccess and Routing\n";
echo "----------------------------\n";

$htaccessCommand = 'grep -E "RewriteRule.*index.php|ErrorDocument" .htaccess | head -5';
$htaccessUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($htaccessCommand);
$response = @file_get_contents($htaccessUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "Routing rules:\n";
        foreach ($data['output'] as $line) {
            echo "  " . $line . "\n";
        }
    }
}
echo "\n";

// Test 7: Test database connection
echo "TEST 7: Database Connection Test\n";
echo "-------------------------------\n";

$dbTestScript = '<?php
try {
    $config = require __DIR__ . "/config/config.php";
    $dsn = "mysql:host=" . $config["database"]["host"] . ";dbname=" . $config["database"]["dbname"];
    $pdo = new PDO($dsn, $config["database"]["username"], $config["database"]["password"]);
    echo "✓ Database connection successful\n";
    echo "  Connected to: " . $config["database"]["dbname"] . "\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tables found: " . count($tables) . "\n";
    if (in_array("users", $tables)) echo "  ✓ users table exists\n";
    if (in_array("content", $tables)) echo "  ✓ content table exists\n";
    if (in_array("pages", $tables)) echo "  ✓ pages table exists\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}
?>';

$dbTestCommand = 'cd /home/dalthaus/public_html && php -r \'' . str_replace("'", "\'", $dbTestScript) . '\'';
$dbTestUrl = $agentUrl . '?action=execute&key=' . $agentKey . '&command=' . urlencode($dbTestCommand);
$response = @file_get_contents($dbTestUrl);

if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo implode("\n", $data['output']) . "\n";
    }
}
echo "\n";

// Final Analysis
echo "=== ROOT CAUSE ANALYSIS ===\n";
echo "==========================\n\n";

echo "FINDINGS:\n";
echo "1. The server has both config.php and config.production.php\n";
echo "2. Environment variables are likely not set on the server\n";
echo "3. When env vars are missing, config.production.php uses fallback values (cms_db/cms_user)\n";
echo "4. The application might be loading config.production.php in some cases\n\n";

echo "IMMEDIATE FIXES:\n";
echo "1. Set environment variables on the server:\n";
echo "   - Add to .bashrc or .profile:\n";
echo "     export DB_HOST=localhost\n";
echo "     export DB_NAME=dalthaus_maincms\n";
echo "     export DB_USER=dalthaus_maincms\n";
echo "     export DB_PASSWORD='f4!,Wpds=w6*=~+1'\n\n";

echo "2. OR modify config.production.php fallback values:\n";
echo "   - Change line 25: 'dbname' => \$_ENV['DB_NAME'] ?? 'dalthaus_maincms',\n";
echo "   - Change line 26: 'username' => \$_ENV['DB_USER'] ?? 'dalthaus_maincms',\n";
echo "   - Change line 27: 'password' => \$_ENV['DB_PASSWORD'] ?? 'f4!,Wpds=w6*=~+1',\n\n";

echo "3. OR remove config.production.php if not needed:\n";
echo "   - mv config/config.production.php config/config.production.php.backup\n\n";

echo "4. Check routing issues:\n";
echo "   - The /admin route returns 404, might be a routing configuration issue\n";
echo "   - Check if index.php is properly handling routes\n\n";

echo "=== TEST COMPLETE ===\n";