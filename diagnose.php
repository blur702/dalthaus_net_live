<?php
// Simple diagnostic script to check server status
header('Content-Type: text/plain');

echo "=== Dalthaus.net Server Diagnostics ===\n\n";

// 1. PHP Version
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "   SAPI: " . PHP_SAPI . "\n\n";

// 2. Check if config exists and is readable
$configFile = __DIR__ . '/config/config.php';
echo "2. Config File:\n";
if (file_exists($configFile)) {
    echo "   ✓ File exists\n";
    if (is_readable($configFile)) {
        echo "   ✓ File is readable\n";
        
        // Try to load config
        try {
            $config = require $configFile;
            echo "   ✓ Config loads successfully\n";
            echo "   Database: " . ($config['database']['dbname'] ?? 'NOT SET') . "\n";
        } catch (Exception $e) {
            echo "   ✗ Error loading config: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ File is not readable\n";
    }
} else {
    echo "   ✗ File does not exist\n";
}

// 3. Check database connection
echo "\n3. Database Connection:\n";
if (isset($config) && isset($config['database'])) {
    try {
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
        $pdo = new PDO(
            $dsn,
            $config['database']['username'],
            $config['database']['password'],
            $config['database']['options'] ?? []
        );
        echo "   ✓ Database connection successful!\n";
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Tables found: " . count($tables) . "\n";
        echo "   - " . implode("\n   - ", $tables) . "\n";
    } catch (PDOException $e) {
        echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ Config not loaded\n";
}

// 4. Check autoloader
echo "\n4. Composer Autoloader:\n";
$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "   ✓ Autoloader exists\n";
    require_once $autoloadFile;
    
    // Test a class
    if (class_exists('CMS\Utils\Router')) {
        echo "   ✓ CMS\Utils\Router class found\n";
    } else {
        echo "   ✗ CMS\Utils\Router class not found\n";
    }
    
    if (class_exists('CMS\Controllers\Public\Home')) {
        echo "   ✓ CMS\Controllers\Public\Home class found\n";
    } else {
        echo "   ✗ CMS\Controllers\Public\Home class not found\n";
    }
} else {
    echo "   ✗ Autoloader not found\n";
}

// 5. Check file permissions
echo "\n5. File Permissions:\n";
$checkDirs = [
    'uploads' => is_writable(__DIR__ . '/uploads'),
    'logs' => is_writable(__DIR__ . '/logs'),
    'cache' => is_writable(__DIR__ . '/cache')
];
foreach ($checkDirs as $dir => $writable) {
    echo "   $dir: " . ($writable ? '✓ Writable' : '✗ Not writable') . "\n";
}

// 6. Check .htaccess
echo "\n6. .htaccess:\n";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "   ✓ .htaccess exists\n";
    $htaccess = file_get_contents(__DIR__ . '/.htaccess');
    if (strpos($htaccess, 'RewriteEngine On') !== false) {
        echo "   ✓ RewriteEngine is On\n";
    }
} else {
    echo "   ✗ .htaccess not found\n";
}

// 7. Check for maintenance mode
echo "\n7. Maintenance Mode:\n";
if (file_exists(__DIR__ . '/maintenance.html')) {
    echo "   ⚠ maintenance.html exists (site in maintenance mode)\n";
} else {
    echo "   ✓ Not in maintenance mode\n";
}

// 8. Git status
echo "\n8. Git Status:\n";
if (is_dir(__DIR__ . '/.git')) {
    echo "   ✓ Git repository found\n";
    exec('cd ' . __DIR__ . ' && git branch --show-current 2>&1', $branch);
    echo "   Current branch: " . ($branch[0] ?? 'unknown') . "\n";
    exec('cd ' . __DIR__ . ' && git status --short 2>&1', $status);
    echo "   Modified files: " . count($status) . "\n";
} else {
    echo "   ✗ Not a git repository\n";
}

echo "\n=== Diagnostics Complete ===\n";
echo "\nTo fix issues:\n";
echo "1. Ensure database is running and accessible\n";
echo "2. Run: composer dump-autoload\n";
echo "3. Check file permissions\n";
echo "4. Review error logs in /logs directory\n";
?>