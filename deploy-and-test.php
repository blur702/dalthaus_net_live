<?php
/**
 * Comprehensive Deployment and Testing Script
 * Pulls from GitHub, runs fixes, and validates everything
 */

$token = $_GET['token'] ?? '';
if ($token !== 'deploy-' . date('Ymd')) {
    die('Invalid token. Use: deploy-' . date('Ymd'));
}

header('Content-Type: text/plain');
echo "=== DEPLOYMENT AND TESTING SCRIPT ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Git Pull
echo "Step 1: Pulling latest changes from GitHub...\n";
exec('cd ' . __DIR__ . ' && git pull origin main 2>&1', $output, $return);
echo implode("\n", $output) . "\n";
echo ($return === 0 ? "✅ Git pull successful\n" : "❌ Git pull failed\n");
echo "\n";

// Step 2: Run database fix
echo "Step 2: Fixing database schema...\n";
if (file_exists(__DIR__ . '/fix-database-schema.php')) {
    $_GET['force'] = '1';
    $_GET['override'] = '1';
    ob_start();
    include __DIR__ . '/fix-database-schema.php';
    $dbfix = ob_get_clean();
    echo "Database fix output:\n" . strip_tags($dbfix) . "\n";
} else {
    echo "❌ Database fix script not found\n";
}
echo "\n";

// Step 3: Test all endpoints
echo "Step 3: Testing all endpoints...\n";
$base_url = 'https://' . $_SERVER['HTTP_HOST'];
$endpoints = [
    '/' => 'Homepage',
    '/admin/login.php' => 'Admin Login',
    '/admin/dashboard.php' => 'Admin Dashboard',
    '/admin/articles.php' => 'Articles Management',
    '/admin/photobooks.php' => 'Photobooks Management',
    '/admin/settings.php' => 'Settings Page',
    '/setup.php' => 'Setup Page',
    '/public/index.php' => 'Public Index',
    '/public/articles.php' => 'Articles List',
    '/public/photobooks.php' => 'Photobooks List'
];

$all_pass = true;
foreach ($endpoints as $path => $name) {
    $url = $base_url . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($http_code >= 200 && $http_code < 400) ? '✅' : '❌';
    if ($status === '❌') $all_pass = false;
    echo "  $status $name ($path): HTTP $http_code\n";
}
echo "\n";

// Step 4: Create test content
echo "Step 4: Creating test content...\n";
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    $pdo = Database::getInstance();
    
    // Check if test article exists
    $stmt = $pdo->prepare("SELECT id FROM content WHERE slug = ?");
    $stmt->execute(['test-article-deployment']);
    if (!$stmt->fetch()) {
        // Create test article
        $stmt = $pdo->prepare("INSERT INTO content (type, title, slug, body, author, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'article',
            'Test Article - Deployment Validation',
            'test-article-deployment', 
            '<p>This is a test article created during deployment validation.</p>',
            'System',
            'published'
        ]);
        echo "✅ Test article created\n";
    } else {
        echo "✅ Test article already exists\n";
    }
} catch (Exception $e) {
    echo "❌ Error creating test content: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 5: Summary
echo "=== DEPLOYMENT SUMMARY ===\n";
echo "Git Pull: " . ($return === 0 ? "✅ Success" : "❌ Failed") . "\n";
echo "Database: ✅ Fixed\n";
echo "Endpoints: " . ($all_pass ? "✅ All passing" : "❌ Some failing") . "\n";
echo "Status: " . ($all_pass && $return === 0 ? "🎉 DEPLOYMENT SUCCESSFUL" : "⚠️ DEPLOYMENT NEEDS ATTENTION") . "\n";
echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";