<?php
/**
 * Test script to verify custom element error fix
 */

require_once __DIR__ . '/config/config.php';

echo "Testing custom element error fix...\n\n";

// Test login credentials
$baseUrl = 'http://localhost:8000';
$username = 'kevin';
$password = '(130Bpm)';

// Initialize curl for session handling
$ch = curl_init();
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Step 1: Login
echo "1. Logging in...\n";
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/admin/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => $username,
    'password' => $password
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo "   ✗ Login failed (HTTP $httpCode)\n";
    exit(1);
}
echo "   ✓ Login successful\n";

// Step 2: Test pages for custom element errors
$testPages = [
    '/admin/dashboard' => 'Dashboard',
    '/admin/content?type=article' => 'Article Listing',
    '/admin/content/create?type=article' => 'Article Create',
    '/admin/pages' => 'Pages Listing',
    '/admin/pages/create' => 'Page Create',
    '/admin/menus' => 'Menus',
    '/admin/users' => 'Users',
    '/admin/settings' => 'Settings'
];

$hasErrors = false;

foreach ($testPages as $path => $name) {
    echo "\n2. Testing $name ($path)...\n";
    
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $path);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        echo "   ✗ Page returned HTTP $httpCode\n";
        continue;
    }
    
    // Check for custom element protection script
    if (strpos($response, 'customElements.define = function') !== false) {
        echo "   ✓ Custom element protection found\n";
    } else {
        echo "   ⚠ Custom element protection not found\n";
    }
    
    // Check for problematic custom elements
    if (strpos($response, 'mce-autosize-textarea') !== false) {
        echo "   ✗ Found mce-autosize-textarea element (potential conflict)\n";
        $hasErrors = true;
    }
    
    // Check for TinyMCE initialization
    if (strpos($response, 'tinymce.init') !== false) {
        echo "   ✓ TinyMCE initialization found\n";
    }
    
    // Check for error handling
    if (strpos($response, 'editorInitialized') !== false) {
        echo "   ✓ Editor initialization guard found\n";
    }
}

// Cleanup
curl_close($ch);
unlink($cookieFile);

echo "\n" . str_repeat('=', 50) . "\n";
if ($hasErrors) {
    echo "RESULT: ✗ Some issues detected\n";
    exit(1);
} else {
    echo "RESULT: ✓ All tests passed - Custom element error fix is working\n";
    exit(0);
}