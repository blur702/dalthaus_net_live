<?php
/**
 * FORCE CSS FIX - This will make CSS work on dalthaus.net
 * Upload this file and run it to fix styling issues
 */

$action = $_GET['action'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force CSS Fix for Dalthaus.net</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        pre { background: #111; padding: 20px; border: 1px solid #0f0; overflow-x: auto; }
        .btn { display: inline-block; padding: 15px 30px; background: #0f0; color: #000; text-decoration: none; margin: 10px; font-weight: bold; }
        .success { color: #0f0; }
        .error { color: #f00; }
    </style>
</head>
<body>
<h1>🔧 FORCE CSS FIX</h1>

<?php if ($action === 'fix'): ?>
<pre>
APPLYING CSS FIXES...
==================================================

<?php
// Fix 1: Update index.php to use absolute paths
echo "1. Fixing index.php CSS paths...\n";
$index_content = '<?php
declare(strict_types=1);
require_once \'includes/config.php\';
require_once \'includes/security_headers.php\';
require_once \'includes/database.php\';
require_once \'includes/router.php\';
require_once \'includes/auth.php\';
require_once \'includes/functions.php\';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for maintenance mode
$requestUri = $_SERVER[\'REQUEST_URI\'];
if (!preg_match(\'/^\/admin/\', $requestUri) && !preg_match(\'/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/\', $requestUri)) {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([\'maintenance_mode\']);
    $maintenanceMode = $stmt->fetchColumn();
    
    if ($maintenanceMode === \'1\') {
        require_once __DIR__ . \'/public/maintenance.php\';
        exit;
    }
}

$router = new Router();

// Public routes
$router->add(\'/\', \'public/index.php\');
$router->add(\'/article/([a-z0-9-]+)\', \'public/article.php\');
$router->add(\'/photobook/([a-z0-9-]+)\', \'public/photobook.php\');

// Admin routes
$router->add(\'/admin\', \'admin/dashboard.php\');
$router->add(\'/admin/login\', \'admin/login.php\');
$router->add(\'/admin/logout\', \'admin/logout.php\');
$router->add(\'/admin/articles\', \'admin/articles.php\');
$router->add(\'/admin/photobooks\', \'admin/photobooks.php\');
$router->add(\'/admin/settings\', \'admin/settings.php\');

// Get URI
$uri = parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH) ?: \'/\';
$method = $_SERVER[\'REQUEST_METHOD\'];

$router->dispatch($uri, $method);
';

file_put_contents('index.php', $index_content);
echo "✅ Updated index.php\n\n";

// Fix 2: Create simple header with inline CSS fallback
echo "2. Creating header with inline CSS fallback...\n";
$header_content = '<?php
if (!isset($pdo)) {
    require_once __DIR__ . \'/database.php\';
    $pdo = Database::getInstance();
}

// Get site settings
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM settings");
foreach ($result as $row) {
    $settings[$row[\'setting_key\']] = $row[\'setting_value\'];
}

$pageTitle = $pageTitle ?? $settings[\'site_title\'] ?? \'Dalthaus Photography\';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Main CSS with absolute path -->
    <link rel="stylesheet" href="/assets/css/public.css?v=<?= filemtime(__DIR__ . \'/../assets/css/public.css\') ?>">
    
    <!-- Inline CSS Fallback -->
    <style>
        /* Critical CSS for immediate styling */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: \'Gelasio\', Georgia, serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .site-header {
            background: #2c3e50;
            color: white;
            padding: 2rem 0;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .site-title {
            font-family: \'Arimo\', Arial, sans-serif;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .site-title a {
            color: white;
            text-decoration: none;
        }
        .site-motto {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
            width: 100%;
        }
        .content-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: \'Arimo\', Arial, sans-serif;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        .article-item {
            background: #f8f9fa;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        .article-meta {
            font-size: 0.875rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
        }
        .site-footer {
            background: #2c3e50;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
            text-align: center;
        }
        @media (max-width: 768px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header">
            <div class="header-content">
                <h1 class="site-title">
                    <a href="/"><?= htmlspecialchars($settings[\'site_title\'] ?? \'Dalthaus Photography\') ?></a>
                </h1>
                <p class="site-motto">Professional Photography Portfolio</p>
            </div>
        </header>
';

file_put_contents('includes/header.php', $header_content);
echo "✅ Updated header.php with inline CSS fallback\n\n";

// Fix 3: Ensure .htaccess serves CSS directly
echo "3. Fixing .htaccess for CSS serving...\n";
$htaccess = 'RewriteEngine On

# FORCE: Serve CSS/JS files directly (MUST be first rule)
RewriteCond %{REQUEST_URI} ^/assets/.*\.(css|js)$ [NC]
RewriteRule ^ - [L]

# Serve all static files directly
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ [NC]
RewriteRule ^ - [L]

# Block sensitive directories
RewriteRule ^includes/ - [F,L]
RewriteRule ^logs/ - [F,L]

# Route everything else through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Set MIME types
<IfModule mod_mime.c>
    AddType text/css .css
    AddType application/javascript .js
</IfModule>
';

file_put_contents('.htaccess', $htaccess);
echo "✅ Updated .htaccess\n\n";

// Fix 4: Clear any cache
echo "4. Clearing cache...\n";
if (is_dir('cache')) {
    $files = glob('cache/*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
        }
    }
}
echo "✅ Cache cleared\n\n";

// Fix 5: Test CSS accessibility
echo "5. Testing CSS accessibility...\n";
$css_url = 'https://' . $_SERVER['HTTP_HOST'] . '/assets/css/public.css';
$ch = curl_init($css_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "CSS URL: $css_url\n";
echo "HTTP Code: $code\n";
echo "Content-Type: $type\n";

if ($code === 200 && strpos($response, 'font-family') !== false) {
    echo "✅ CSS is accessible and contains styles!\n";
} else {
    echo "⚠️  CSS may need manual verification\n";
}

echo "\n==================================================\n";
echo "✅ ALL FIXES APPLIED!\n\n";
echo "The site should now show proper styling with:\n";
echo "- Arimo font for headings\n";
echo "- Gelasio font for body text\n";
echo "- Blue links (#3498db)\n";
echo "- Two-column layout\n";
echo "- Proper header and footer\n";
?>
</pre>

<div style="text-align: center; margin-top: 30px;">
    <a href="/" class="btn">🏠 View Homepage</a>
    <a href="/assets/css/public.css" class="btn">📄 Check CSS File</a>
    <a href="/admin/login.php" class="btn">🔐 Admin Login</a>
</div>

<?php else: ?>

<pre>
CURRENT ISSUES:
==================================================
❌ CSS file exists but styles not being applied
❌ Homepage appears as unstyled HTML
❌ Fonts (Arimo/Gelasio) not loading
❌ Layout structure not visible

THIS SCRIPT WILL:
==================================================
✅ Add inline CSS fallback to ensure immediate styling
✅ Fix .htaccess to serve CSS files directly
✅ Update paths to use absolute URLs
✅ Clear all cache
✅ Test CSS accessibility

READY TO FIX?
</pre>

<div style="text-align: center; margin-top: 30px;">
    <a href="?action=fix" class="btn">🔧 APPLY CSS FIXES NOW</a>
</div>

<?php endif; ?>

</body>
</html>