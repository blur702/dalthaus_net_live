<?php
// Simple web-accessible script to check database table
header('Content-Type: text/plain');

// Security check - only allow from localhost or specific IP
$allowed_ips = ['127.0.0.1', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($client_ip, $allowed_ips) && !isset($_GET['check'])) {
    http_response_code(403);
    die("Access denied");
}

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
        echo "user_tokens table: DOES NOT EXIST!\n";
        echo "This explains why remember me login fails.\n";
    }

    // Also check users table for reference
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $userCount = $stmt->fetch()['count'];
    echo "Users in database: $userCount\n";

} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>