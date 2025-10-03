<?php
/**
 * TinyMCE Debug Script - Check configuration and loading for different users
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Utils/Auth.php';

// Start session
session_start();

echo "=== TinyMCE Debug Information ===\n\n";

echo "Session Information:\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "- Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
echo "- Is Admin: " . (($_SESSION['is_admin'] ?? false) ? 'YES' : 'NO') . "\n";
echo "- Logged In: " . (($_SESSION['logged_in'] ?? false) ? 'YES' : 'NO') . "\n\n";

echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "\n\n";

echo "TinyMCE Configuration Check:\n";
echo "- CDN URL: https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js\n";
echo "- Single JS: /assets/js/tinymce-single.js\n\n";

// Check if tinymce-single.js exists and is readable
$tinymceFile = __DIR__ . '/assets/js/tinymce-single.js';
if (file_exists($tinymceFile)) {
    echo "✅ tinymce-single.js exists\n";
    echo "- File size: " . filesize($tinymceFile) . " bytes\n";
    echo "- Last modified: " . date('Y-m-d H:i:s', filemtime($tinymceFile)) . "\n";
    
    // Check if it contains the toolbar configuration
    $content = file_get_contents($tinymceFile);
    if (strpos($content, 'dualimage') !== false) {
        echo "✅ Contains 'dualimage' button\n";
    } else {
        echo "❌ Missing 'dualimage' button\n";
    }
    
    if (strpos($content, 'modalimage') !== false) {
        echo "✅ Contains 'modalimage' button\n";
    } else {
        echo "❌ Missing 'modalimage' button\n";
    }
    
    if (strpos($content, 'testbutton') !== false) {
        echo "✅ Contains 'testbutton' button\n";
    } else {
        echo "❌ Missing 'testbutton' button\n";
    }
} else {
    echo "❌ tinymce-single.js NOT FOUND\n";
}

echo "\nAdmin Layout Check:\n";
$adminLayoutFile = __DIR__ . '/src/Views/Layouts/admin.php';
if (file_exists($adminLayoutFile)) {
    echo "✅ admin.php layout exists\n";
    $content = file_get_contents($adminLayoutFile);
    
    if (strpos($content, 'tinymce-single.js') !== false) {
        echo "✅ admin.php loads tinymce-single.js\n";
    } else {
        echo "❌ admin.php does NOT load tinymce-single.js\n";
    }
    
    if (strpos($content, 'tinymce@6') !== false) {
        echo "✅ admin.php loads TinyMCE CDN\n";
    } else {
        echo "❌ admin.php does NOT load TinyMCE CDN\n";
    }
} else {
    echo "❌ admin.php layout NOT FOUND\n";
}

echo "\nBrowser Cache Headers:\n";
if (isset($_SERVER['HTTP_CACHE_CONTROL'])) {
    echo "- Cache-Control: " . $_SERVER['HTTP_CACHE_CONTROL'] . "\n";
}
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    echo "- If-Modified-Since: " . $_SERVER['HTTP_IF_MODIFIED_SINCE'] . "\n";
}
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
    echo "- If-None-Match: " . $_SERVER['HTTP_IF_NONE_MATCH'] . "\n";
}

echo "\nRecommendations:\n";
echo "1. Clear browser cache and cookies\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Verify network requests are loading TinyMCE scripts\n";
echo "4. Try incognito/private browsing mode\n";
echo "5. Check if different browsers show the same issue\n";
?>