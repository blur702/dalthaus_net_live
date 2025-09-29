<?php
// Simple script to check if user_tokens table exists
require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "Database connection: SUCCESS\n";

    // Check if user_tokens table exists
    $query = "SHOW TABLES LIKE 'user_tokens'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result) {
        echo "user_tokens table: EXISTS\n";

        // Check table structure
        $query = "DESCRIBE user_tokens";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll();

        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']}: {$column['Type']}\n";
        }

        // Check if table has any data
        $query = "SELECT COUNT(*) as count FROM user_tokens";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "Records in table: $count\n";

    } else {
        echo "user_tokens table: DOES NOT EXIST\n";
        echo "This is likely the cause of the remember me login failure!\n";

        echo "\nTo fix this, run the following SQL:\n";
        echo "CREATE TABLE user_tokens (\n";
        echo "    id INT PRIMARY KEY AUTO_INCREMENT,\n";
        echo "    user_id INT NOT NULL,\n";
        echo "    token VARCHAR(64) NOT NULL,\n";
        echo "    expires_at DATETIME NOT NULL,\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,\n";
        echo "    INDEX idx_token (token),\n";
        echo "    INDEX idx_user_id (user_id),\n";
        echo "    INDEX idx_expires_at (expires_at)\n";
        echo ");\n";
    }

} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>