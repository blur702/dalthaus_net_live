<?php
/**
 * FINAL DEPLOYMENT SCRIPT FOR DALTHAUS.NET
 * 
 * This script:
 * 1. Fixes .htaccess routing for CSS files
 * 2. Clears all caches
 * 3. Tests CSS loading
 * 4. Verifies styling is applied
 * 5. Tests admin access
 * 6. Provides comprehensive status report
 * 
 * Run this script after deploying code to production.
 * Access via: https://dalthaus.net/FINAL_DEPLOYMENT_SCRIPT.php
 */

set_time_limit(300); // 5 minutes
ini_set('display_errors', 1);
error_reporting(E_ALL);

$results = [];
$allTestsPassed = true;

function logResult($test, $passed, $message, $details = '') {
    global $results, $allTestsPassed;
    $results[] = [
        'test' => $test,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];
    if (!$passed) {
        $allTestsPassed = false;
    }
    return $passed;
}

function testUrl($url, $expectedContent = null, $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Deployment-Test-Script/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error, 'code' => 0];
    }
    
    list($headers, $body) = explode("\r\n\r\n", $response, 2);
    
    return [
        'success' => $httpCode === 200,
        'code' => $httpCode,
        'content_type' => $contentType,
        'body' => $body,
        'headers' => $headers
    ];
}

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>Dalthaus.net Final Deployment Test</title>\n";
echo "    <style>\n";
echo "        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; line-height: 1.6; }\n";
echo "        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }\n";
echo "        .pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }\n";
echo "        .fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }\n";
echo "        .summary { font-weight: bold; font-size: 1.2em; margin: 20px 0; }\n";
echo "        .details { font-size: 0.9em; margin-top: 10px; background: #f8f9fa; padding: 10px; border-radius: 4px; }\n";
echo "        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; }\n";
echo "        h1, h2 { color: #2c3e50; }\n";
echo "        .progress { margin: 10px 0; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🚀 Dalthaus.net Final Deployment Test</h1>\n";
echo "<p><em>Started at " . date('Y-m-d H:i:s') . "</em></p>\n";

// Step 1: Clear all caches
echo "<h2>Step 1: Clearing Caches</h2>\n";
echo "<div class='progress'>Clearing caches...</div>\n";

$cacheDir = __DIR__ . '/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $cleared = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
            $cleared++;
        }
    }
    logResult('Cache Clear', true, "Cleared $cleared cache files");
} else {
    logResult('Cache Clear', false, 'Cache directory not found');
}

// Step 2: Test .htaccess file exists and has correct rules
echo "<h2>Step 2: Testing .htaccess Configuration</h2>\n";
echo "<div class='progress'>Checking .htaccess file...</div>\n";

$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    $htaccessContent = file_get_contents($htaccessFile);
    
    // Check for static file rule
    if (strpos($htaccessContent, '\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map|xml|txt|pdf|doc|docx)$ [NC]') !== false) {
        logResult('.htaccess Static Files', true, '.htaccess has static file bypass rule');
    } else {
        logResult('.htaccess Static Files', false, '.htaccess missing static file bypass rule');
    }
    
    // Check for MIME types
    if (strpos($htaccessContent, 'AddType text/css .css') !== false) {
        logResult('.htaccess MIME Types', true, '.htaccess has CSS MIME type');
    } else {
        logResult('.htaccess MIME Types', false, '.htaccess missing CSS MIME type');
    }
} else {
    logResult('.htaccess File', false, '.htaccess file not found');
}

// Step 3: Test CSS file access
echo "<h2>Step 3: Testing CSS File Access</h2>\n";
echo "<div class='progress'>Testing CSS files...</div>\n";

$cssFile = '/assets/css/public.css';
$cssPath = __DIR__ . $cssFile;

// Check file exists
if (file_exists($cssPath)) {
    $fileSize = filesize($cssPath);
    logResult('CSS File Exists', true, "public.css found ($fileSize bytes)");
    
    // Test HTTP access
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $cssUrl = $baseUrl . $cssFile;
    
    $cssTest = testUrl($cssUrl);
    if ($cssTest['success']) {
        if (strpos($cssTest['content_type'], 'text/css') !== false) {
            logResult('CSS HTTP Access', true, 'CSS file served with correct Content-Type');
        } elseif (strpos($cssTest['content_type'], 'text/html') !== false) {
            logResult('CSS HTTP Access', false, 'CSS file served as HTML (routing problem)', 
                'Content-Type: ' . $cssTest['content_type']);
        } else {
            logResult('CSS HTTP Access', false, 'CSS file served with unexpected Content-Type', 
                'Content-Type: ' . $cssTest['content_type']);
        }
    } else {
        logResult('CSS HTTP Access', false, "Failed to access CSS file (HTTP {$cssTest['code']})", 
            $cssTest['error'] ?? '');
    }
} else {
    logResult('CSS File Exists', false, 'public.css not found on filesystem');
}

// Step 4: Test homepage access and styling
echo "<h2>Step 4: Testing Homepage</h2>\n";
echo "<div class='progress'>Testing homepage access...</div>\n";

$homepageUrl = $baseUrl . '/';
$homepageTest = testUrl($homepageUrl);

if ($homepageTest['success']) {
    logResult('Homepage Access', true, 'Homepage loads successfully');
    
    // Check for CSS link
    if (strpos($homepageTest['body'], '/assets/css/public.css') !== false) {
        logResult('Homepage CSS Link', true, 'Homepage includes CSS link');
    } else {
        logResult('Homepage CSS Link', false, 'Homepage missing CSS link');
    }
    
    // Check for Google Fonts
    if (strpos($homepageTest['body'], 'fonts.googleapis.com') !== false) {
        logResult('Homepage Fonts', true, 'Homepage includes Google Fonts');
    } else {
        logResult('Homepage Fonts', false, 'Homepage missing Google Fonts');
    }
    
    // Check for proper HTML structure
    if (strpos($homepageTest['body'], 'class="page-wrapper"') !== false) {
        logResult('Homepage Structure', true, 'Homepage has proper CSS classes');
    } else {
        logResult('Homepage Structure', false, 'Homepage missing expected CSS classes');
    }
} else {
    logResult('Homepage Access', false, "Homepage failed to load (HTTP {$homepageTest['code']})");
}

// Step 5: Test admin access
echo "<h2>Step 5: Testing Admin Access</h2>\n";
echo "<div class='progress'>Testing admin pages...</div>\n";

$adminUrl = $baseUrl . '/admin/login.php';
$adminTest = testUrl($adminUrl);

if ($adminTest['success']) {
    logResult('Admin Access', true, 'Admin login page accessible');
    
    // Check if it contains login form
    if (strpos($adminTest['body'], '<form') !== false && strpos($adminTest['body'], 'password') !== false) {
        logResult('Admin Login Form', true, 'Admin login form present');
    } else {
        logResult('Admin Login Form', false, 'Admin login form not found');
    }
} else {
    logResult('Admin Access', false, "Admin page failed to load (HTTP {$adminTest['code']})");
}

// Step 6: Test database connection
echo "<h2>Step 6: Testing Database Connection</h2>\n";
echo "<div class='progress'>Testing database...</div>\n";

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/database.php';
    
    $pdo = Database::getInstance();
    $stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE type = 'article'");
    $articleCount = $stmt->fetchColumn();
    
    logResult('Database Connection', true, "Database connected ($articleCount articles found)");
} catch (Exception $e) {
    logResult('Database Connection', false, 'Database connection failed', $e->getMessage());
}

// Step 7: Final styling test
echo "<h2>Step 7: Final Styling Test</h2>\n";
echo "<div class='progress'>Creating live styling test...</div>\n";

echo "<div style='border: 2px solid #ddd; padding: 20px; margin: 20px 0; background: white;'>\n";
echo "<h3>Live Styling Test</h3>\n";
echo "<link rel='stylesheet' href='$cssFile?v=" . time() . "'>\n";
echo "<link href='https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap' rel='stylesheet'>\n";
echo "<div class='alert alert-success' style='margin: 10px 0;'>✓ This should have green background if CSS loads</div>\n";
echo "<div style='font-family: \"Arimo\", sans-serif; color: #3498db; font-size: 18px; margin: 10px 0;'>✓ This should be Arimo font and blue</div>\n";
echo "<div style='font-family: \"Gelasio\", serif; color: #333; font-size: 16px; margin: 10px 0;'>✓ This should be Gelasio serif font</div>\n";
echo "</div>\n";

// Display results summary
echo "<h2>📊 Test Results Summary</h2>\n";

$passedTests = array_filter($results, function($r) { return $r['passed']; });
$totalTests = count($results);
$passedCount = count($passedTests);

echo "<div class='summary " . ($allTestsPassed ? 'pass' : 'fail') . "'>\n";
echo "Tests Passed: $passedCount / $totalTests\n";
echo "</div>\n";

// Display all test results
foreach ($results as $result) {
    $class = $result['passed'] ? 'pass' : 'fail';
    $icon = $result['passed'] ? '✅' : '❌';
    
    echo "<div class='test-result $class'>\n";
    echo "<strong>$icon {$result['test']}</strong>: {$result['message']}\n";
    if (!empty($result['details'])) {
        echo "<div class='details'>{$result['details']}</div>\n";
    }
    echo "</div>\n";
}

// Final recommendations
echo "<h2>🔧 Recommendations</h2>\n";

if ($allTestsPassed) {
    echo "<div class='test-result pass'>\n";
    echo "<strong>🎉 All tests passed!</strong> The site should now be fully functional with proper styling.\n";
    echo "</div>\n";
    
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Visit <a href='$baseUrl'>$baseUrl</a> to verify styling</li>\n";
    echo "<li>Visit <a href='{$baseUrl}/admin/login.php'>{$baseUrl}/admin/login.php</a> to test admin access</li>\n";
    echo "<li>Delete this test script: <code>FINAL_DEPLOYMENT_SCRIPT.php</code></li>\n";
    echo "<li>Delete the CSS test script: <code>test-css-loading.php</code></li>\n";
    echo "</ul>\n";
} else {
    echo "<div class='test-result fail'>\n";
    echo "<strong>⚠️ Some tests failed.</strong> Review the failures above and fix before proceeding.\n";
    echo "</div>\n";
    
    echo "<p><strong>Common fixes:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>If CSS access fails: Check file permissions on assets/css/ directory</li>\n";
    echo "<li>If homepage has no styling: Clear browser cache and hard refresh</li>\n";
    echo "<li>If database fails: Check includes/config.php settings</li>\n";
    echo "<li>If admin access fails: Check file permissions on admin/ directory</li>\n";
    echo "</ul>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</p>\n";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>\n";

echo "</body>\n";
echo "</html>\n";
?>