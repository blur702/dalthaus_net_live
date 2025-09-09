<?php
/**
 * Test for JavaScript errors in admin pages
 */

require_once __DIR__ . '/config/config.php';

echo "Testing for JavaScript errors in admin pages...\n\n";

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

// Login
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/admin/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => $username,
    'password' => $password
]));
$response = curl_exec($ch);

// Test admin dashboard for the custom element protection
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/admin/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HTTPGET, true);
$dashboardHtml = curl_exec($ch);

// Check if our protection code is present
if (strpos($dashboardHtml, 'window.customElements.define = function') !== false) {
    echo "✓ Custom element protection is present in the HTML\n";
    
    // Extract the protection script
    preg_match('/<script>\s*\/\/[^<]*customElements[^<]*<\/script>/s', $dashboardHtml, $matches);
    if (!empty($matches)) {
        echo "✓ Protection script found:\n";
        echo substr($matches[0], 0, 200) . "...\n\n";
    }
} else {
    echo "✗ Custom element protection NOT found in HTML\n";
    echo "First 500 chars of response:\n";
    echo substr($dashboardHtml, 0, 500) . "\n";
}

// Check for TinyMCE initialization guard
if (strpos($dashboardHtml, 'editorInitialized') !== false) {
    echo "✓ TinyMCE initialization guard is present\n";
} else {
    echo "⚠ TinyMCE initialization guard not found\n";
}

// Check for any mce-autosize elements
if (strpos($dashboardHtml, 'mce-autosize-textarea') === false) {
    echo "✓ No problematic mce-autosize-textarea elements found\n";
} else {
    echo "✗ Found mce-autosize-textarea element (potential conflict)\n";
}

// Cleanup
curl_close($ch);
unlink($cookieFile);

echo "\n" . str_repeat('=', 50) . "\n";
echo "Test complete\n";