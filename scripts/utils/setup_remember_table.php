<?php
/**
 * Minimal script to create remember_tokens table
 * Access via: https://dalthaus.net/setup_remember_table.php
 */

// Simple inline config for production database
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

    // Drop table if exists to start fresh
    $pdo->exec("DROP TABLE IF EXISTS `remember_tokens`");
    echo "<p>Dropped existing table (if any)</p>\n";

    // Create table without foreign key constraint
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

    echo "<h2>SUCCESS!</h2>\n";
    echo "<p>remember_tokens table created successfully!</p>\n";
    echo "<p>Remember Me functionality should now work.</p>\n";
    echo "<p><strong>IMPORTANT:</strong> Delete this file for security.</p>\n";
    echo "<p><a href='/admin/login'>Test Login</a></p>\n";

    // Show table structure for verification
    $result = $pdo->query("DESCRIBE remember_tokens");
    echo "<h3>Table Structure:</h3>\n<pre>\n";
    foreach ($result as $row) {
        print_r($row);
    }
    echo "</pre>\n";

} catch (Exception $e) {
    echo "<h1>ERROR</h1>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>