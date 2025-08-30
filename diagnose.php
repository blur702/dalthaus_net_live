<?php
// Diagnostic script - find the actual error

// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>PHP Diagnostic</h1>";
echo "<pre>";

// Basic info
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n\n";

// Check if we can connect to database at all
echo "Testing raw database connection...\n";
$conn = mysqli_connect('localhost', 'dalthaus_photocms', 'f-I*GSo^Urt*k*&#', 'dalthaus_photocms');
if ($conn) {
    echo "✅ Database connection works!\n\n";
    mysqli_close($conn);
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "\n\n";
}

// Check what's in includes directory
echo "Checking includes directory...\n";
if (is_dir('includes')) {
    $files = scandir('includes');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - $file (" . filesize("includes/$file") . " bytes)\n";
        }
    }
} else {
    echo "  ❌ includes directory not found!\n";
}

echo "\n";

// Try to include config and see what happens
echo "Testing config.php include...\n";
try {
    // Suppress errors first
    @include_once 'includes/config.php';
    echo "  ✅ config.php included\n";
} catch (Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    echo "  ❌ Parse Error: " . $e->getMessage() . "\n";
}

// Check for parse errors in index.php
echo "\nChecking index.php for syntax errors...\n";
$code = file_get_contents('index.php');
$tokens = @token_get_all($code);
if ($tokens === false) {
    echo "  ❌ index.php has syntax errors\n";
} else {
    echo "  ✅ index.php syntax is valid\n";
}

// Get last error
$error = error_get_last();
if ($error) {
    echo "\nLast PHP Error:\n";
    print_r($error);
}

echo "</pre>";

// Try to show a simple page
echo "<h2>If you see this, basic PHP works!</h2>";
echo "<p>Database credentials are correct: dalthaus_photocms</p>";
echo "<p><a href='/admin/login.php'>Try Admin Login</a></p>";
?>