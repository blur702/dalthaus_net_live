<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Homepage Debug Script</h1>";
echo "<pre>";

echo "1. Testing config.php include...\n";
try {
    require_once 'includes/config.php';
    echo "✓ Config loaded successfully\n";
    echo "ENV: " . ENV . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "✗ Config failed: " . $e->getMessage() . "\n";
    exit;
}

echo "\n2. Testing security headers include...\n";
try {
    require_once 'includes/security_headers.php';
    echo "✓ Security headers loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Security headers failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing database connection...\n";
try {
    require_once 'includes/database.php';
    $pdo = Database::getInstance();
    echo "✓ Database connected successfully\n";
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "\n";
    
    if (!in_array('content', $tables)) {
        echo "⚠ Content table missing - running setup...\n";
        Database::setup();
        echo "✓ Database setup complete\n";
    }
} catch (Exception $e) {
    echo "✗ Database failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing router include...\n";
try {
    require_once 'includes/router.php';
    echo "✓ Router loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Router failed: " . $e->getMessage() . "\n";
}

echo "\n5. Testing other includes...\n";
try {
    require_once 'includes/auth.php';
    echo "✓ Auth loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Auth failed: " . $e->getMessage() . "\n";
}

try {
    require_once 'includes/functions.php';
    echo "✓ Functions loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Functions failed: " . $e->getMessage() . "\n";
}

echo "\n6. Testing session start...\n";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✓ Session started successfully\n";
    } else {
        echo "✓ Session already active\n";
    }
} catch (Exception $e) {
    echo "✗ Session failed: " . $e->getMessage() . "\n";
}

echo "\n7. Testing public/index.php...\n";
try {
    ob_start();
    include 'public/index.php';
    $output = ob_get_clean();
    echo "✓ Public index executed successfully\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    if (strlen($output) < 100) {
        echo "Output preview: " . substr($output, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ Public index failed: " . $e->getMessage() . "\n";
    echo "Error on line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n8. PHP Session Configuration Check...\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "\n";

echo "</pre>";
echo "<p><strong>Debug completed. Check above for any errors.</strong></p>";
?>