<?php
/**
 * DEPLOY WITH CSS FIX SCRIPT
 * 
 * Quick deployment script that:
 * 1. Pulls latest code from GitHub
 * 2. Fixes CSS routing in .htaccess
 * 3. Clears cache
 * 4. Tests basic functionality
 * 
 * Access via: https://dalthaus.net/deploy-with-css-fix.php
 */

set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Deploy with CSS Fix</title>";
echo "<style>body{font-family:monospace;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>\n";

echo "<h1>🚀 Deploy with CSS Fix</h1>\n";
echo "<p class='info'>Started at " . date('Y-m-d H:i:s') . "</p>\n";

// Step 1: Git pull
echo "<h2>Step 1: Git Pull</h2>\n";
if (is_dir('.git')) {
    echo "<p>Pulling latest changes...</p>\n";
    exec('git pull origin main 2>&1', $gitOutput, $gitReturn);
    
    if ($gitReturn === 0) {
        echo "<p class='success'>✅ Git pull successful</p>\n";
        echo "<pre>" . implode("\n", $gitOutput) . "</pre>\n";
    } else {
        echo "<p class='error'>❌ Git pull failed</p>\n";
        echo "<pre>" . implode("\n", $gitOutput) . "</pre>\n";
    }
} else {
    echo "<p class='info'>ℹ️ Not a git repository, skipping pull</p>\n";
}

// Step 2: Fix .htaccess for CSS routing
echo "<h2>Step 2: Fix .htaccess</h2>\n";

$htaccessFile = '.htaccess';
if (file_exists($htaccessFile)) {
    $content = file_get_contents($htaccessFile);
    
    // Check if CSS fix is already applied
    if (strpos($content, '# CRITICAL: Serve static assets directly first') !== false) {
        echo "<p class='success'>✅ CSS routing fix already applied</p>\n";
    } else {
        echo "<p class='info'>Applying CSS routing fix...</p>\n";
        
        // Find the URL rewriting section and fix it
        $pattern = '/(# URL REWRITING FOR CLEAN URLS.*?# Allow direct access to admin PHP files)/s';
        $replacement = '# URL REWRITING FOR CLEAN URLS
# =====================================
# CRITICAL: Serve static assets directly first (before any other rules)
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map|xml|txt|pdf|doc|docx)$ [NC]
RewriteRule ^ - [L]

# Allow direct access to admin PHP files';
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            if (file_put_contents($htaccessFile, $newContent)) {
                echo "<p class='success'>✅ .htaccess updated with CSS routing fix</p>\n";
            } else {
                echo "<p class='error'>❌ Failed to write .htaccess file</p>\n";
            }
        } else {
            echo "<p class='info'>ℹ️ .htaccess already has correct structure</p>\n";
        }
    }
} else {
    echo "<p class='error'>❌ .htaccess file not found</p>\n";
}

// Step 3: Clear cache
echo "<h2>Step 3: Clear Cache</h2>\n";
$cacheDir = 'cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $cleared = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
            $cleared++;
        }
    }
    echo "<p class='success'>✅ Cleared $cleared cache files</p>\n";
} else {
    echo "<p class='info'>ℹ️ Cache directory not found</p>\n";
}

// Step 4: Quick tests
echo "<h2>Step 4: Quick Tests</h2>\n";

// Test CSS file exists
$cssFile = 'assets/css/public.css';
if (file_exists($cssFile)) {
    $cssSize = filesize($cssFile);
    echo "<p class='success'>✅ CSS file exists (" . number_format($cssSize) . " bytes)</p>\n";
} else {
    echo "<p class='error'>❌ CSS file missing</p>\n";
}

// Test index.php exists
if (file_exists('index.php')) {
    echo "<p class='success'>✅ index.php exists</p>\n";
} else {
    echo "<p class='error'>❌ index.php missing</p>\n";
}

// Test admin directory
if (is_dir('admin')) {
    echo "<p class='success'>✅ admin directory exists</p>\n";
} else {
    echo "<p class='error'>❌ admin directory missing</p>\n";
}

// Final message
echo "<h2>✨ Deployment Complete</h2>\n";
echo "<p>Visit these URLs to test:</p>\n";
echo "<ul>\n";
echo "<li><a href='/'>Homepage</a> - should show proper styling</li>\n";
echo "<li><a href='/assets/css/public.css'>CSS File</a> - should return CSS content</li>\n";
echo "<li><a href='/admin/login.php'>Admin Login</a> - should be accessible</li>\n";
echo "<li><a href='/FINAL_DEPLOYMENT_SCRIPT.php'>Full Test Suite</a> - comprehensive testing</li>\n";
echo "</ul>\n";

echo "<p><em>If you see styling issues, run the full test suite above for detailed diagnostics.</em></p>\n";

echo "<hr>\n";
echo "<p class='info'>Completed at " . date('Y-m-d H:i:s') . "</p>\n";
echo "</body></html>\n";
?>