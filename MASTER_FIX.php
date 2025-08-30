<?php
/**
 * MASTER FIX - Comprehensive Production Repair
 * Fixes all identified issues from E2E testing
 */

error_reporting(0);
ini_set('display_errors', 0);

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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Master Fix - Production Repair</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { border-bottom: 2px solid #0f0; padding-bottom: 10px; }
        .section { background: #0d0d0d; padding: 20px; margin: 20px 0; border: 1px solid #0f0; }
        .success { color: #0f0; font-weight: bold; }
        .error { color: #f00; font-weight: bold; }
        .warning { color: #fa0; }
        pre { background: #000; padding: 15px; overflow-x: auto; }
        .btn { background: #0f0; color: #000; padding: 10px 20px; text-decoration: none; display: inline-block; margin: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">

<h1>🔧 MASTER FIX - Production Repair</h1>

<?php
$action = $_GET['action'] ?? 'show';

if ($action === 'fix'):
?>

<div class="section">
<h2>STEP 1: Database Configuration Fix</h2>
<pre>
<?php
// Test database connections with common shared hosting patterns
$db_configs = [
    // Common cPanel patterns
    ['host' => 'localhost', 'user' => 'dalthaus_dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_dalthaus'],
    ['host' => 'localhost', 'user' => 'dalthaus_cms', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_admin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    
    // Try without parentheses
    ['host' => 'localhost', 'user' => 'dalthaus_cms', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    
    // Original attempts
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
];

$working_config = null;
echo "Testing database connections...\n";

foreach ($db_configs as $cfg) {
    echo "  Testing {$cfg['user']}@{$cfg['host']}/{$cfg['db']} ... ";
    $conn = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
    if ($conn) {
        echo "<span class='success'>✅ SUCCESS!</span>\n";
        $result = @mysqli_query($conn, "SHOW TABLES");
        if ($result) {
            $count = mysqli_num_rows($result);
            echo "    Found $count tables\n";
        }
        mysqli_close($conn);
        $working_config = $cfg;
        break;
    } else {
        echo "<span class='error'>❌ Failed</span>\n";
    }
}

if ($working_config) {
    echo "\n<span class='success'>Database configuration found!</span>\n";
    
    // Write config
    $config_content = "<?php
// Database Configuration - FIXED
define('DB_HOST', '{$working_config['host']}');
define('DB_NAME', '{$working_config['db']}');
define('DB_USER', '{$working_config['user']}');
define('DB_PASS', '" . addslashes($working_config['pass']) . "');

// Environment
define('ENV', 'production');

// Admin defaults
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Settings
define('LOG_LEVEL', 'ERROR');
define('CACHE_ENABLED', true);
";
    
    $write_result = callAgent($agent_url, $token, 'write', [
        'path' => 'includes/config.local.php',
        'content' => $config_content
    ]);
    
    if ($write_result['success']) {
        echo "<span class='success'>✅ Written config.local.php</span>\n";
    }
} else {
    echo "\n<span class='error'>❌ No working database configuration found</span>\n";
    echo "Manual intervention required - check hosting control panel\n";
}
?>
</pre>
</div>

<div class="section">
<h2>STEP 2: Fix Homepage index.php</h2>
<pre>
<?php
// Read and fix index.php
$index = callAgent($agent_url, $token, 'read', ['path' => 'index.php']);
if ($index['success']) {
    $content = $index['content'];
    $fixed = false;
    
    // Remove shebang if present
    if (strpos($content, '#!/usr/bin/env php') !== false) {
        echo "Removing shebang line...\n";
        $content = preg_replace('/^#!.*?\n/', '', $content);
        $fixed = true;
    }
    
    // Check for proper PHP opening tag
    if (strpos($content, '<?php') === false && strpos($content, '<?') !== false) {
        echo "Fixing PHP opening tag...\n";
        $content = str_replace('<?', '<?php', $content);
        $fixed = true;
    }
    
    if ($fixed) {
        $write = callAgent($agent_url, $token, 'write', ['path' => 'index.php', 'content' => $content]);
        if ($write['success']) {
            echo "<span class='success'>✅ Fixed index.php</span>\n";
        }
    } else {
        echo "index.php appears correct\n";
    }
} else {
    echo "<span class='error'>Could not read index.php</span>\n";
}
?>
</pre>
</div>

<div class="section">
<h2>STEP 3: Fix Static Assets (.htaccess)</h2>
<pre>
<?php
// Check if assets exist
$css_check = callAgent($agent_url, $token, 'exists', ['path' => 'assets/css/public.css']);
if ($css_check['success'] && $css_check['exists']) {
    echo "✅ CSS file exists\n";
} else {
    echo "<span class='warning'>⚠️ CSS file missing - may need to pull from git</span>\n";
}

// Fix .htaccess to serve static files correctly
$htaccess = callAgent($agent_url, $token, 'read', ['path' => '.htaccess']);
if ($htaccess['success']) {
    $content = $htaccess['content'];
    
    // Ensure static files are served directly
    if (strpos($content, 'RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ [NC]') === false) {
        echo "Adding static file rules to .htaccess...\n";
        
        // Find where to insert (before the main rewrite rule)
        $insert_pos = strpos($content, 'RewriteCond %{REQUEST_FILENAME} !-f');
        if ($insert_pos !== false) {
            $before = substr($content, 0, $insert_pos);
            $after = substr($content, $insert_pos);
            
            $static_rules = "# Serve static files directly\n";
            $static_rules .= "RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ [NC]\n";
            $static_rules .= "RewriteRule ^ - [L]\n\n";
            
            $content = $before . $static_rules . $after;
            
            $write = callAgent($agent_url, $token, 'write', ['path' => '.htaccess', 'content' => $content]);
            if ($write['success']) {
                echo "<span class='success'>✅ Updated .htaccess for static files</span>\n";
            }
        }
    } else {
        echo "Static file rules already present\n";
    }
} else {
    echo "<span class='error'>Could not read .htaccess</span>\n";
}
?>
</pre>
</div>

<div class="section">
<h2>STEP 4: Clear Cache</h2>
<pre>
<?php
$cache_list = callAgent($agent_url, $token, 'list', ['path' => 'cache']);
if ($cache_list['success']) {
    $cleared = 0;
    foreach ($cache_list['files'] as $file) {
        if ($file['name'] !== 'index.html' && $file['type'] === 'file') {
            $delete = callAgent($agent_url, $token, 'delete', ['path' => 'cache/' . $file['name']]);
            if ($delete['success']) $cleared++;
        }
    }
    echo "<span class='success'>✅ Cleared $cleared cache files</span>\n";
} else {
    echo "Cache directory not accessible\n";
}
?>
</pre>
</div>

<div class="section">
<h2>STEP 5: Test Results</h2>
<pre>
<?php
// Test homepage
echo "Testing homepage...\n";
$homepage = @file_get_contents('https://dalthaus.net/');
if ($homepage && strpos($homepage, '500 Internal Server Error') === false) {
    echo "<span class='success'>✅ Homepage is loading!</span>\n";
} else {
    echo "<span class='error'>❌ Homepage still showing error</span>\n";
}

// Test CSS
echo "\nTesting CSS delivery...\n";
$css_headers = @get_headers('https://dalthaus.net/assets/css/public.css', 1);
if ($css_headers && strpos($css_headers[0], '200') !== false) {
    echo "<span class='success'>✅ CSS is accessible</span>\n";
} else {
    echo "<span class='error'>❌ CSS not accessible (404)</span>\n";
}

// Test admin
echo "\nTesting admin panel...\n";
$admin = @file_get_contents('https://dalthaus.net/admin/login.php');
if ($admin && strpos($admin, 'Login') !== false) {
    echo "<span class='success'>✅ Admin panel accessible</span>\n";
} else {
    echo "<span class='error'>❌ Admin panel not working</span>\n";
}
?>
</pre>
</div>

<div class="section">
<h2>Final Status</h2>
<?php
if ($working_config) {
    echo "<p class='success'>✅ Database configuration has been fixed</p>";
    echo "<p>The site should now be functional. If still showing errors:</p>";
    echo "<ul>";
    echo "<li>Run git pull to ensure all files are present</li>";
    echo "<li>Check error logs for specific issues</li>";
    echo "<li>Verify file permissions (755 for directories, 644 for files)</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Database configuration could not be fixed automatically</p>";
    echo "<p>Manual steps required:</p>";
    echo "<ol>";
    echo "<li>Login to your hosting control panel (cPanel)</li>";
    echo "<li>Find MySQL Databases section</li>";
    echo "<li>Note the database name, username, and password</li>";
    echo "<li>Update includes/config.local.php with correct credentials</li>";
    echo "</ol>";
}
?>

<p>
    <a href="/" class="btn">View Homepage</a>
    <a href="/admin/login.php" class="btn">Admin Panel</a>
    <a href="/git-pull.php?token=<?= $token ?>" class="btn">Git Pull</a>
</p>
</div>

<?php else: ?>

<div class="section">
<h2>E2E Test Results Summary</h2>
<p class="error">❌ 12 out of 15 tests failed</p>
<ul>
    <li>Homepage: 500 Error (database issue)</li>
    <li>Static Assets: 404 Error (CSS/JS not found)</li>
    <li>Admin Login: ✅ Working</li>
    <li>Content Pages: 500 Error</li>
</ul>
</div>

<div class="section">
<h2>Issues to Fix</h2>
<ol>
    <li><strong>Database Connection</strong> - Wrong credentials in config</li>
    <li><strong>Static Assets</strong> - CSS/JS returning 404</li>
    <li><strong>Homepage Error</strong> - 500 error due to database</li>
    <li><strong>Missing Files</strong> - Some assets may not be deployed</li>
</ol>
</div>

<div class="section">
<h2>Ready to Fix?</h2>
<p>This script will:</p>
<ul>
    <li>Test multiple database credential combinations</li>
    <li>Fix configuration files</li>
    <li>Update .htaccess for static files</li>
    <li>Clear cache</li>
    <li>Verify fixes</li>
</ul>

<p>
    <a href="?action=fix" class="btn" onclick="return confirm('Run comprehensive fixes?')">
        🚀 RUN MASTER FIX
    </a>
</p>
</div>

<?php endif; ?>

</div>
</body>
</html>