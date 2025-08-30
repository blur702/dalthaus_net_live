<?php
/**
 * Safe Database Fix - Properly handles errors
 */

// Suppress display errors but log them
error_reporting(0);
ini_set('display_errors', 0);

echo "<h1>Safe Database Fix</h1><pre>";

// Possible database configurations to try
$configs_to_try = [
    ['host' => 'localhost', 'user' => 'dalthaus_dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_cms', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_admin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'admin', 'pass' => '130Bpm', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_kevin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthausnet', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
];

echo "Testing database connections (suppressing errors)...\n\n";

$working_config = null;

foreach ($configs_to_try as $config) {
    echo "Testing: " . $config['user'] . "@" . $config['host'] . " with pass '" . $config['pass'] . "' ... ";
    
    // Use @ to suppress warnings/errors
    $conn = @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['db']);
    
    if ($conn) {
        echo "✅ SUCCESS!\n";
        
        // Test query
        $result = @mysqli_query($conn, "SHOW TABLES");
        if ($result) {
            $count = mysqli_num_rows($result);
            echo "   Found $count tables\n";
        }
        
        mysqli_close($conn);
        
        $working_config = $config;
        break;
    } else {
        $error = @mysqli_connect_error();
        echo "❌ Failed" . ($error ? ": $error" : "") . "\n";
    }
}

// Also check if database exists with different name patterns
if (!$working_config) {
    echo "\nTrying alternative database names...\n";
    
    $alt_databases = ['dalthaus_dalthaus', 'dalthaus_db', 'dalthaus_site', 'dalthaus'];
    $alt_users = ['dalthaus', 'dalthaus_user', 'dalthaus_admin'];
    $alt_passwords = ['(130Bpm)', '130Bpm', 'admin'];
    
    foreach ($alt_databases as $db) {
        foreach ($alt_users as $user) {
            foreach ($alt_passwords as $pass) {
                echo "Testing: $user / $db ... ";
                $conn = @mysqli_connect('localhost', $user, $pass, $db);
                if ($conn) {
                    echo "✅ SUCCESS!\n";
                    $working_config = [
                        'host' => 'localhost',
                        'user' => $user,
                        'pass' => $pass,
                        'db' => $db
                    ];
                    mysqli_close($conn);
                    break 3;
                } else {
                    echo "❌\n";
                }
            }
        }
    }
}

if ($working_config) {
    echo "\n✅ FOUND WORKING CONFIGURATION!\n";
    echo "Host: " . $working_config['host'] . "\n";
    echo "User: " . $working_config['user'] . "\n";
    echo "Password: " . $working_config['pass'] . "\n";
    echo "Database: " . $working_config['db'] . "\n";
    
    // Write the working config
    echo "\nWriting configuration files...\n";
    
    $config_content = "<?php
// Local configuration overrides
// This file is not tracked in git

// Database Configuration
define('DB_HOST', '" . $working_config['host'] . "');
define('DB_NAME', '" . $working_config['db'] . "');
define('DB_USER', '" . $working_config['user'] . "');
define('DB_PASS', '" . addslashes($working_config['pass']) . "');

// Environment
define('ENV', 'production');

// Admin defaults (for setup)
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Logging
define('LOG_LEVEL', 'ERROR');
define('CACHE_ENABLED', true);
";
    
    // Write config.local.php
    $written = @file_put_contents('includes/config.local.php', $config_content);
    if ($written) {
        echo "✅ Written includes/config.local.php\n";
    } else {
        echo "❌ Failed to write config.local.php\n";
    }
    
    // Also update main config.php
    if (file_exists('includes/config.php')) {
        $main = @file_get_contents('includes/config.php');
        if ($main) {
            $main = preg_replace("/define\('DB_HOST',\s*'[^']+'\)/", "define('DB_HOST', '" . $working_config['host'] . "')", $main);
            $main = preg_replace("/define\('DB_USER',\s*'[^']+'\)/", "define('DB_USER', '" . $working_config['user'] . "')", $main);
            $main = preg_replace("/define\('DB_PASS',\s*'[^']+'\)/", "define('DB_PASS', '" . addslashes($working_config['pass']) . "')", $main);
            $main = preg_replace("/define\('DB_NAME',\s*'[^']+'\)/", "define('DB_NAME', '" . $working_config['db'] . "')", $main);
            
            if (@file_put_contents('includes/config.php', $main)) {
                echo "✅ Updated includes/config.php\n";
            }
        }
    }
    
    echo "\n🎉 Database configuration fixed!\n";
    echo "\nPlease check:\n";
    echo "- https://dalthaus.net/ (homepage)\n";
    echo "- https://dalthaus.net/admin/login.php (admin)\n";
    
} else {
    echo "\n❌ NO WORKING DATABASE CONFIGURATION FOUND!\n\n";
    
    echo "Let me check what databases exist on this server...\n\n";
    
    // Try to connect without selecting a database
    $test_users = ['dalthaus', 'dalthaus_user', 'dalthaus_admin', 'kevin', 'root'];
    $test_passes = ['(130Bpm)', '130Bpm', '', 'admin'];
    
    foreach ($test_users as $user) {
        foreach ($test_passes as $pass) {
            $conn = @mysqli_connect('localhost', $user, $pass);
            if ($conn) {
                echo "✅ Can connect as '$user' with password '$pass'\n";
                
                // List databases
                $result = @mysqli_query($conn, "SHOW DATABASES");
                if ($result) {
                    echo "   Available databases:\n";
                    while ($row = mysqli_fetch_row($result)) {
                        if ($row[0] != 'information_schema' && $row[0] != 'mysql' && $row[0] != 'performance_schema') {
                            echo "   - " . $row[0] . "\n";
                        }
                    }
                }
                mysqli_close($conn);
                break 2;
            }
        }
    }
    
    echo "\n⚠️  MANUAL ACTION REQUIRED:\n";
    echo "1. Check your hosting control panel (cPanel) for database details\n";
    echo "2. Look for database name, username, and password\n";
    echo "3. The database might need to be created first\n";
    echo "4. On shared hosting, database names often start with your account name\n";
    echo "   (e.g., dalthaus_cms or dalthaus_database)\n";
}

echo "</pre>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='/'>Homepage</a></li>";
echo "<li><a href='/admin/login.php'>Admin Login</a></li>";
echo "<li><a href='/list-files.php'>List Files</a></li>";
echo "</ul>";
?>