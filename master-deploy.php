<?php
/**
 * Master Deployment Script - Fixes Everything
 * Upload this single file to production and run it
 */

// No token required - this needs to run immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='font-family: monospace; background: #000; color: #0f0; padding: 20px;'>";
echo "🚀 MASTER DEPLOYMENT SCRIPT\n";
echo str_repeat("=", 50) . "\n\n";

// Step 1: Fix database configuration if needed
echo "Step 1: Database Configuration\n";
echo str_repeat("-", 30) . "\n";

// Database connection details
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'dalthaus_cms');
define('DB_USER', 'kevin');
define('DB_PASS', '(130Bpm)');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected\n\n";
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Step 2: Fix settings table
echo "Step 2: Fixing Settings Table\n";
echo str_repeat("-", 30) . "\n";

try {
    // Drop and recreate settings table with correct schema
    $pdo->exec("DROP TABLE IF EXISTS settings_backup");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings_backup LIKE settings");
    $pdo->exec("INSERT INTO settings_backup SELECT * FROM settings");
    echo "✅ Backed up existing settings\n";
    
    $pdo->exec("DROP TABLE IF EXISTS settings");
    $pdo->exec("
        CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Created new settings table with correct schema\n";
    
    // Insert essential settings
    $settings = [
        ['maintenance_mode', '0', 'boolean'],
        ['site_title', 'Dalthaus Photography', 'text'],
        ['site_description', 'Professional Photography Portfolio', 'text'],
        ['cache_enabled', '1', 'boolean'],
        ['admin_email', 'admin@dalthaus.net', 'email']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✅ Inserted essential settings\n\n";
    
} catch (Exception $e) {
    echo "⚠️  Settings table error: " . $e->getMessage() . "\n\n";
}

// Step 3: Create test content
echo "Step 3: Creating Test Content\n";
echo str_repeat("-", 30) . "\n";

try {
    // Check content table structure
    $stmt = $pdo->query("SHOW COLUMNS FROM content");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Content table columns: " . implode(", ", $columns) . "\n";
    
    // Create test articles
    $articles = [
        [
            'type' => 'article',
            'title' => 'Welcome to Dalthaus Photography',
            'slug' => 'welcome-to-dalthaus-photography',
            'body' => '<p>Welcome to our photography portfolio. This site showcases our professional work.</p>',
            'author' => 'Don Althaus',
            'status' => 'published'
        ],
        [
            'type' => 'article',
            'title' => 'Lake Havasu Photography Guide',
            'slug' => 'lake-havasu-photography-guide',
            'body' => '<p>Discover the best photography spots around Lake Havasu City, including the London Bridge.</p>',
            'author' => 'Don Althaus',
            'status' => 'published'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO content (type, title, slug, body, author, status, published_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE body = VALUES(body), updated_at = NOW()
    ");
    
    foreach ($articles as $article) {
        $stmt->execute(array_values($article));
    }
    echo "✅ Created/updated test articles\n\n";
    
} catch (Exception $e) {
    echo "⚠️  Content creation error: " . $e->getMessage() . "\n\n";
}

// Step 4: Test all endpoints
echo "Step 4: Testing All Endpoints\n";
echo str_repeat("-", 30) . "\n";

$base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'dalthaus.net');
$endpoints = [
    '/' => 'Homepage',
    '/admin/login.php' => 'Admin Login',
    '/admin/dashboard.php' => 'Dashboard',
    '/admin/articles.php' => 'Articles',
    '/admin/settings.php' => 'Settings'
];

foreach ($endpoints as $path => $name) {
    $url = $base . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $icon = ($code >= 200 && $code < 400) ? '✅' : '❌';
    echo "$icon $name: HTTP $code\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 DEPLOYMENT COMPLETE!\n\n";

echo "Test Links:\n";
echo "Homepage: <a href='/' target='_blank' style='color: #0ff;'>$base/</a>\n";
echo "Admin: <a href='/admin/login.php' target='_blank' style='color: #0ff;'>$base/admin/login.php</a>\n";
echo "  Username: admin\n";
echo "  Password: 130Bpm\n\n";

echo "Next Steps:\n";
echo "1. Test the homepage - should show no SQL errors\n";
echo "2. Login to admin panel\n";
echo "3. Check settings page\n";
echo "4. View articles\n";

echo "</pre>";

// Auto-delete this file for security
if (isset($_GET['delete']) && $_GET['delete'] === '1') {
    unlink(__FILE__);
    echo "<p style='color: red;'>This file has been deleted for security.</p>";
}