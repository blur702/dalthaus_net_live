<?php
// Emergency fix script - pulls latest code and fixes issues
if ($_GET['token'] !== 'fix-' . date('Ymd')) {
    die('Token: fix-' . date('Ymd'));
}

header('Content-Type: text/plain');
echo "=== EMERGENCY FIX SCRIPT ===\n\n";

// 1. Show current directory
echo "Current directory: " . getcwd() . "\n";
chdir('/home/dalthaus/public_html');

// 2. Git pull latest
echo "\n[GIT PULL]\n";
$pull = shell_exec('git fetch --all 2>&1');
echo $pull;
$reset = shell_exec('git reset --hard origin/main 2>&1');
echo $reset;

// 3. Check critical files
echo "\n[FILE CHECK]\n";
$files = ['index.php', 'setup.php', '.htaccess', 'includes/config.php'];
foreach($files as $file) {
    echo $file . ": " . (file_exists($file) ? "EXISTS" : "MISSING") . "\n";
}

// 4. Fix .htaccess to minimal
echo "\n[FIX .HTACCESS]\n";
$minimal_htaccess = "# Minimal .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
";
file_put_contents('.htaccess', $minimal_htaccess);
echo ".htaccess replaced with minimal version\n";

// 5. Show PHP info
echo "\n[PHP INFO]\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Extensions: " . implode(', ', get_loaded_extensions()) . "\n";

echo "\n=== FIX COMPLETE ===\n";
echo "Try accessing: https://dalthaus.net/setup.php\n";
?>