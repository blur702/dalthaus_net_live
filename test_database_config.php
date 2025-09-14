<?php

/**
 * Comprehensive Database Configuration Test Suite
 * Tests configuration loading, database connectivity, and identifies root cause of config issues
 */

declare(strict_types=1);

// Test 1: Check environment variables
echo "=== TEST 1: Environment Variables Check ===\n";
$envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? getenv($var);
    if ($value) {
        echo "✓ {$var}: " . ($var === 'DB_PASSWORD' ? '***hidden***' : $value) . "\n";
    } else {
        echo "✗ {$var}: NOT SET\n";
    }
}
echo "\n";

// Test 2: Check which config file is being loaded
echo "=== TEST 2: Configuration File Loading ===\n";
$configPath = __DIR__ . '/config/config.php';
$productionConfigPath = __DIR__ . '/config/config.production.php';

if (file_exists($configPath)) {
    echo "✓ config.php exists\n";
    $config = require $configPath;
    echo "  Database: {$config['database']['dbname']}\n";
    echo "  Username: {$config['database']['username']}\n";
    echo "  Host: {$config['database']['host']}\n";
} else {
    echo "✗ config.php NOT FOUND\n";
}

if (file_exists($productionConfigPath)) {
    echo "✓ config.production.php exists\n";
    $prodConfig = require $productionConfigPath;
    echo "  Database: {$prodConfig['database']['dbname']}\n";
    echo "  Username: {$prodConfig['database']['username']}\n";
    echo "  Host: {$prodConfig['database']['host']}\n";
} else {
    echo "✗ config.production.php NOT FOUND\n";
}
echo "\n";

// Test 3: Check if config files are being overwritten
echo "=== TEST 3: Config File Integrity Check ===\n";
$configContent = file_get_contents($configPath);
$expectedCredentials = [
    'dalthaus_maincms' => 'Database name',
    'f4!,Wpds=w6*=~+1' => 'Database password'
];

foreach ($expectedCredentials as $credential => $description) {
    if (strpos($configContent, $credential) !== false) {
        echo "✓ {$description} found in config.php\n";
    } else {
        echo "✗ {$description} NOT found in config.php\n";
    }
}
echo "\n";

// Test 4: Test actual database connection
echo "=== TEST 4: Database Connection Test ===\n";
require_once __DIR__ . '/vendor/autoload.php';

// Test with config.php settings
try {
    $config = require $configPath;
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
        $config['database']['options'] ?? []
    );
    
    echo "✓ Connection successful with config.php settings\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT DATABASE() as db_name, USER() as db_user");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Connected to: {$result['db_name']}\n";
    echo "  User: {$result['db_user']}\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed with config.php settings\n";
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Check if production config with env vars would work
echo "=== TEST 5: Production Config with Environment Variables ===\n";
if (file_exists($productionConfigPath)) {
    // Temporarily set env vars to test
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'dalthaus_maincms';
    $_ENV['DB_USER'] = 'dalthaus_maincms';
    $_ENV['DB_PASSWORD'] = 'f4!,Wpds=w6*=~+1';
    
    $prodConfig = require $productionConfigPath;
    echo "With correct env vars set:\n";
    echo "  Database: {$prodConfig['database']['dbname']}\n";
    echo "  Username: {$prodConfig['database']['username']}\n";
    
    // Clear env vars
    unset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    
    $prodConfig = require $productionConfigPath;
    echo "Without env vars (fallback values):\n";
    echo "  Database: {$prodConfig['database']['dbname']}\n";
    echo "  Username: {$prodConfig['database']['username']}\n";
}
echo "\n";

// Test 6: Check BaseModel configuration loading
echo "=== TEST 6: BaseModel Configuration Loading ===\n";
use CMS\Models\BaseModel;

class TestModel extends BaseModel {
    protected string $table = 'users';
    
    public function getDbConfig(): array {
        $config = require __DIR__ . '/config/config.php';
        return $config['database'];
    }
}

try {
    $model = new TestModel();
    $dbConfig = $model->getDbConfig();
    echo "BaseModel loads config with:\n";
    echo "  Database: {$dbConfig['dbname']}\n";
    echo "  Username: {$dbConfig['username']}\n";
} catch (Exception $e) {
    echo "✗ Failed to load BaseModel: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Check for any scripts that might be modifying config
echo "=== TEST 7: Potential Config Modification Scripts ===\n";
$suspiciousFiles = [
    'fix_config.php',
    'fix_config_complete.php',
    'restore_config.php',
    'fix_config_server.php'
];

foreach ($suspiciousFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        echo "⚠ Found: {$file}\n";
        $content = file_get_contents($filePath);
        if (strpos($content, 'cms_db') !== false || strpos($content, 'cms_user') !== false) {
            echo "  ✗ Contains old credentials (cms_db/cms_user)\n";
        }
        $modTime = date('Y-m-d H:i:s', filemtime($filePath));
        echo "  Last modified: {$modTime}\n";
    }
}
echo "\n";

// Test 8: Check file permissions
echo "=== TEST 8: File Permissions Check ===\n";
$configPerms = fileperms($configPath);
$configOwner = fileowner($configPath);
$configGroup = filegroup($configPath);

echo "config.php permissions:\n";
echo "  Permissions: " . substr(sprintf('%o', $configPerms), -4) . "\n";
echo "  Owner UID: {$configOwner}\n";
echo "  Group GID: {$configGroup}\n";
echo "  Writable: " . (is_writable($configPath) ? 'Yes' : 'No') . "\n";
echo "\n";

// Test 9: Check for deployment scripts or CI/CD
echo "=== TEST 9: Deployment/CI Scripts Check ===\n";
$deploymentFiles = glob(__DIR__ . '/{deploy*,*.sh,Makefile,.github/workflows/*}', GLOB_BRACE);
foreach ($deploymentFiles as $file) {
    if (is_file($file)) {
        echo "Found: " . basename($file) . "\n";
        $content = file_get_contents($file);
        if (strpos($content, 'config') !== false || strpos($content, 'cms_db') !== false) {
            echo "  ⚠ May modify configuration\n";
        }
    }
}
echo "\n";

// Summary and Root Cause Analysis
echo "=== ROOT CAUSE ANALYSIS ===\n";
$issues = [];

// Check if env vars are set
if (!getenv('DB_NAME') && !isset($_ENV['DB_NAME'])) {
    $issues[] = "Environment variables not set - production config will use fallback values";
}

// Check if production config is being loaded instead
if (file_exists($productionConfigPath)) {
    $prodConfig = require $productionConfigPath;
    if ($prodConfig['database']['dbname'] === 'cms_db') {
        $issues[] = "Production config is using fallback values (cms_db/cms_user)";
    }
}

// Check if config has wrong values
$config = require $configPath;
if ($config['database']['dbname'] !== 'dalthaus_maincms') {
    $issues[] = "config.php has incorrect database name: {$config['database']['dbname']}";
}

if (empty($issues)) {
    echo "✓ Configuration appears correct\n";
} else {
    echo "✗ Issues identified:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";