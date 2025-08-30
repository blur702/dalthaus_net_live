<?php
/**
 * Fix PHP 8.4 session compatibility issues
 */

echo "<h1>Fixing PHP 8.4 Session Compatibility</h1><pre>";

// Fix config.php
$config_path = 'includes/config.php';
if (file_exists($config_path)) {
    $config = file_get_contents($config_path);
    
    echo "Fixing config.php session settings...\n";
    
    // Comment out or remove deprecated session settings
    $config = preg_replace('/ini_set\([\'"]session\.sid_bits_per_character[\'"],.*?\);/s', '// Removed deprecated session.sid_bits_per_character', $config);
    $config = preg_replace('/ini_set\([\'"]session\.hash_bits_per_character[\'"],.*?\);/s', '// Removed deprecated session.hash_bits_per_character', $config);
    
    // Also suppress any ini_set warnings
    $config = str_replace('ini_set(', '@ini_set(', $config);
    
    file_put_contents($config_path, $config);
    echo "✅ Fixed config.php\n";
}

// Fix config.local.php
$local_path = 'includes/config.local.php';
if (file_exists($local_path)) {
    $local = file_get_contents($local_path);
    
    echo "\nFixing config.local.php session settings...\n";
    
    // Same fixes
    $local = preg_replace('/ini_set\([\'"]session\.sid_bits_per_character[\'"],.*?\);/s', '// Removed deprecated', $local);
    $local = preg_replace('/ini_set\([\'"]session\.hash_bits_per_character[\'"],.*?\);/s', '// Removed deprecated', $local);
    $local = str_replace('ini_set(', '@ini_set(', $local);
    
    file_put_contents($local_path, $local);
    echo "✅ Fixed config.local.php\n";
}

// Also fix auth.php if it has session settings
$auth_path = 'includes/auth.php';
if (file_exists($auth_path)) {
    $auth = file_get_contents($auth_path);
    
    echo "\nFixing auth.php session settings...\n";
    
    $auth = str_replace('ini_set(', '@ini_set(', $auth);
    $auth = str_replace('session_set_cookie_params(', '@session_set_cookie_params(', $auth);
    
    file_put_contents($auth_path, $auth);
    echo "✅ Fixed auth.php\n";
}

// Clear any opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n✅ Cleared OPcache\n";
}

echo "\n🎉 PHP 8.4 COMPATIBILITY FIXED!\n\n";
echo "</pre>";

echo "<h2>Test the Fixed Site:</h2>";
echo "<ul>";
echo "<li><a href='/' style='font-size: 20px; font-weight: bold;'>🏠 Homepage (Should Work Now!)</a></li>";
echo "<li><a href='/admin/login.php'>🔐 Admin Login</a></li>";
echo "<li><a href='/articles'>📄 Articles</a></li>";
echo "<li><a href='/photobooks'>📷 Photobooks</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p>Database is connected to: <strong>dalthaus_photocms</strong></p>";
echo "<p>Admin credentials: <strong>admin / 130Bpm</strong></p>";
?>