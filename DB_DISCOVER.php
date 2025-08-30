<?php
/**
 * Database Discovery Script
 * Tries to find ANY working database connection
 */

error_reporting(0);
ini_set('display_errors', 0);

echo "<h1>Database Discovery</h1><pre>";

// Extended list of possible configurations
$users = ['dalthaus', 'dalthaus_cms', 'dalthaus_user', 'dalthaus_admin', 'dalthaus_dalthaus', 'kevin', 'admin', 'root'];
$passwords = ['(130Bpm)', '130Bpm', 'admin', ''];
$databases = ['dalthaus_cms', 'dalthaus_photocms', 'dalthaus_dalthaus', 'dalthaus', 'dalthaus_db'];

echo "Testing all combinations...\n\n";

$found = false;

foreach ($users as $user) {
    foreach ($passwords as $pass) {
        // First try to connect without database
        $conn = @mysqli_connect('localhost', $user, $pass);
        if ($conn) {
            echo "✅ Can connect as '$user' with password '$pass'\n";
            
            // List databases
            $result = @mysqli_query($conn, "SHOW DATABASES");
            if ($result) {
                echo "   Available databases:\n";
                $user_dbs = [];
                while ($row = mysqli_fetch_row($result)) {
                    if (!in_array($row[0], ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                        echo "   - {$row[0]}\n";
                        $user_dbs[] = $row[0];
                    }
                }
                
                // Try each database
                foreach ($user_dbs as $db) {
                    if (@mysqli_select_db($conn, $db)) {
                        echo "   ✅ Can access database '$db'\n";
                        
                        // Check for CMS tables
                        $tables_result = @mysqli_query($conn, "SHOW TABLES");
                        $table_count = @mysqli_num_rows($tables_result);
                        echo "      Found $table_count tables\n";
                        
                        if ($table_count > 0) {
                            // Check for content table
                            $content_check = @mysqli_query($conn, "SELECT COUNT(*) FROM content");
                            if ($content_check) {
                                echo "      ✅ Has 'content' table - THIS IS OUR CMS!\n\n";
                                echo "🎉 FOUND WORKING CONFIGURATION:\n";
                                echo "User: $user\n";
                                echo "Pass: $pass\n";
                                echo "Database: $db\n\n";
                                
                                // Write config
                                $config = "<?php
define('DB_HOST', 'localhost');
define('DB_NAME', '$db');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('ENV', 'production');
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');
define('LOG_LEVEL', 'ERROR');
define('CACHE_ENABLED', true);
";
                                
                                if (file_put_contents('includes/config.local.php', $config)) {
                                    echo "✅ Config written!\n";
                                    echo "<a href='/'>Test Homepage</a>";
                                    $found = true;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
            mysqli_close($conn);
        }
    }
}

if (!$found) {
    echo "\n❌ Could not find CMS database\n\n";
    echo "MANUAL ACTION REQUIRED:\n";
    echo "1. Login to your hosting control panel\n";
    echo "2. Check MySQL Databases section\n";
    echo "3. Look for database and username\n";
    echo "4. Note the password\n";
}

echo "</pre>";
?>