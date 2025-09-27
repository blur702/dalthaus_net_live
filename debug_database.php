<?php
/**
 * Debug script to check database tables and test remember token functionality
 */

// Load config
$config = [
    'host' => 'localhost',
    'dbname' => 'dalthaus_maincms',
    'username' => 'dalthaus_maincms',
    'password' => 'f4!,Wpds=w6*=~+1'
];

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<h1>Database Connection: SUCCESS</h1>\n";

    // Check if remember_tokens table exists
    $result = $pdo->query("SHOW TABLES LIKE 'remember_tokens'");
    $tableExists = $result->fetch();

    echo "<h2>Table Status:</h2>\n";
    if ($tableExists) {
        echo "<p>✅ remember_tokens table EXISTS</p>\n";

        // Show table structure
        $result = $pdo->query("DESCRIBE remember_tokens");
        echo "<h3>Table Structure:</h3>\n<table border='1'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
        foreach ($result as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";

        // Check if there are any existing tokens
        $result = $pdo->query("SELECT COUNT(*) as count FROM remember_tokens");
        $count = $result->fetch()['count'];
        echo "<p>Current token count: {$count}</p>\n";

        // Test insert
        try {
            $pdo->exec("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (1, 'test_token_hash', DATE_ADD(NOW(), INTERVAL 30 DAY))");
            echo "<p>✅ Test INSERT successful</p>\n";

            // Clean up test
            $pdo->exec("DELETE FROM remember_tokens WHERE token_hash = 'test_token_hash'");
            echo "<p>✅ Test cleanup successful</p>\n";
        } catch (Exception $e) {
            echo "<p>❌ Test INSERT failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }

    } else {
        echo "<p>❌ remember_tokens table DOES NOT EXIST</p>\n";

        // Try to create it
        try {
            $sql = "CREATE TABLE `remember_tokens` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `token_hash` VARCHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_token_hash` (`token_hash`),
                KEY `idx_expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec($sql);
            echo "<p>✅ remember_tokens table CREATED successfully</p>\n";
        } catch (Exception $e) {
            echo "<p>❌ Failed to create table: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // Check users table for reference
    $result = $pdo->query("SELECT user_id, username FROM users LIMIT 5");
    echo "<h3>Sample Users:</h3>\n<table border='1'>\n";
    echo "<tr><th>user_id</th><th>username</th></tr>\n";
    foreach ($result as $row) {
        echo "<tr><td>{$row['user_id']}</td><td>" . htmlspecialchars($row['username']) . "</td></tr>\n";
    }
    echo "</table>\n";

    echo "<p><strong>IMPORTANT:</strong> Delete this file for security after testing.</p>\n";
    echo "<p><a href='/admin/login'>Test Remember Me Login</a></p>\n";

} catch (Exception $e) {
    echo "<h1>ERROR</h1>\n";
    echo "<p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>