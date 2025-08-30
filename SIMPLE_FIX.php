<?php
/**
 * Simple Fix - Direct database configuration fix
 * No dependencies, just fixes the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Database Fix</h1><pre>";

// Database configurations to test
$configs = [
    ['host' => 'localhost', 'user' => 'dalthaus_cms', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_cms', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
];

$working = null;

echo "Testing database connections...\n\n";

foreach ($configs as $c) {
    echo "Testing {$c['user']}@{$c['host']} with password '{$c['pass']}' ... ";
    
    $conn = @mysqli_connect($c['host'], $c['user'], $c['pass'], $c['db']);
    
    if ($conn) {
        echo "✅ SUCCESS!\n";
        $working = $c;
        mysqli_close($conn);
        break;
    } else {
        echo "❌ Failed\n";
    }
}

if ($working) {
    echo "\n✅ FOUND WORKING CONFIGURATION!\n";
    echo "User: {$working['user']}\n";
    echo "Pass: {$working['pass']}\n";
    echo "Database: {$working['db']}\n\n";
    
    // Write config.local.php
    $config = "<?php
define('DB_HOST', 'localhost');
define('DB_NAME', '{$working['db']}');
define('DB_USER', '{$working['user']}');
define('DB_PASS', '{$working['pass']}');
define('ENV', 'production');
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');
define('LOG_LEVEL', 'ERROR');
define('CACHE_ENABLED', true);
";
    
    $written = file_put_contents('includes/config.local.php', $config);
    if ($written) {
        echo "✅ Written includes/config.local.php\n\n";
        
        // Clear cache
        $cache_files = glob('cache/*.cache');
        foreach ($cache_files as $file) {
            @unlink($file);
        }
        echo "✅ Cleared cache\n\n";
        
        echo "🎉 DATABASE FIXED! Site should work now.\n\n";
        echo "<a href='/'>View Homepage</a> | <a href='/admin/login.php'>Admin Login</a>";
    } else {
        echo "❌ Could not write config file\n";
    }
} else {
    echo "\n❌ NO WORKING DATABASE FOUND\n\n";
    echo "Trying to connect without database selection...\n\n";
    
    // Try to connect without database to list what's available
    $test_users = ['dalthaus', 'dalthaus_cms', 'kevin'];
    $test_passes = ['(130Bpm)', '130Bpm'];
    
    foreach ($test_users as $user) {
        foreach ($test_passes as $pass) {
            $conn = @mysqli_connect('localhost', $user, $pass);
            if ($conn) {
                echo "✅ Can connect as '$user' with '$pass'\n";
                
                $result = @mysqli_query($conn, "SHOW DATABASES");
                if ($result) {
                    echo "Available databases:\n";
                    while ($row = mysqli_fetch_row($result)) {
                        if (!in_array($row[0], ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                            echo "  - {$row[0]}\n";
                        }
                    }
                }
                mysqli_close($conn);
                break 2;
            }
        }
    }
    
    echo "\nCheck your hosting control panel for correct credentials.";
}

echo "</pre>";
?>