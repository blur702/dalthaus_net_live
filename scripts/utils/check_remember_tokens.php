<?php
// Quick diagnostic for remember_tokens table
try {
    $config = ['host' => 'localhost', 'dbname' => 'dalthaus_maincms', 'username' => 'dalthaus_maincms', 'password' => 'f4!,Wpds=w6*=~+1'];
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "<h1>Remember Tokens Table Diagnostic</h1>";

    // Check if table exists
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'remember_tokens'");
        if ($result->rowCount() > 0) {
            echo "<p>✅ Table 'remember_tokens' EXISTS</p>";

            // Show structure
            $result = $pdo->query("DESCRIBE remember_tokens");
            echo "<h3>Table Structure:</h3><table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($result as $row) {
                echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
            }
            echo "</table>";

            // Test insert
            try {
                $pdo->exec("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (1, 'test_token', DATE_ADD(NOW(), INTERVAL 30 DAY))");
                echo "<p>✅ Test INSERT successful</p>";
                $pdo->exec("DELETE FROM remember_tokens WHERE token_hash = 'test_token'");
                echo "<p>✅ Test DELETE successful</p>";
            } catch (Exception $e) {
                echo "<p>❌ Test INSERT/DELETE failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }

        } else {
            echo "<p>❌ Table 'remember_tokens' DOES NOT EXIST</p>";
            echo "<p>Creating table...</p>";

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
            echo "<p>✅ Table created successfully!</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Table check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<p><strong>Delete this file after use!</strong></p>";
    echo "<p><a href='/admin/login'>Test Remember Me Login</a></p>";

} catch (Exception $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>