<?php
/**
 * Quick script to create remember_tokens table on production
 * Run this once to set up the table for remember me functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

try {
    // Load config
    $config = require __DIR__ . '/config/config.php';

    // Create PDO connection
    $pdo = new PDO(
        'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['dbname'] . ';charset=' . $config['database']['charset'],
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );

    echo "Connected to database successfully!\n";

    // Create table SQL
    $sql = "CREATE TABLE IF NOT EXISTS `remember_tokens` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) UNSIGNED NOT NULL,
        `token_hash` VARCHAR(64) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_token_hash` (`token_hash`),
        KEY `idx_expires_at` (`expires_at`),
        CONSTRAINT `fk_remember_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    // Execute the SQL
    $pdo->exec($sql);

    echo "Remember tokens table created successfully!\n";
    echo "Authentication persistence improvements are now active.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "You may need to run this script manually on your server.\n";
}