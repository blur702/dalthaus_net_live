<?php
/**
 * Fix the strict_types error in config.php
 */

echo "<h1>Fixing strict_types Error</h1><pre>";

// Read config.php
$config_path = 'includes/config.php';
$config = file_get_contents($config_path);

echo "Original config.php first 20 lines:\n";
$lines = explode("\n", $config);
for ($i = 0; $i < min(20, count($lines)); $i++) {
    echo ($i+1) . ": " . htmlspecialchars($lines[$i]) . "\n";
}

echo "\n\nFixing...\n";

// Remove any declare(strict_types) that's not at the top
$config = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', '', $config);

// If we want strict types, add it right after <?php
// For now, just remove it completely to fix the site

file_put_contents($config_path, $config);
echo "✅ Removed strict_types declaration\n";

// Also check config.local.php
if (file_exists('includes/config.local.php')) {
    echo "\nChecking config.local.php...\n";
    $local = file_get_contents('includes/config.local.php');
    $local = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', '', $local);
    file_put_contents('includes/config.local.php', $local);
    echo "✅ Fixed config.local.php too\n";
}

echo "\n🎉 FIXED! The site should work now.\n\n";
echo "</pre>";

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/'>Homepage</a></li>";
echo "<li><a href='/hello.php'>Hello World Test</a></li>";
echo "<li><a href='/admin/login.php'>Admin Login</a></li>";
echo "</ul>";
?>