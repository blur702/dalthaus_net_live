<?php
/**
 * Debug script to check remember_tokens table status
 * Run this to diagnose the remember me functionality issue
 */

require_once __DIR__ . '/config/config.php';

try {
    // Create database connection
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Database connection successful\n\n";

    // Check if remember_tokens table exists
    echo "🔍 Checking if remember_tokens table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'remember_tokens'");
    $tableExists = $stmt->fetch() !== false;

    if ($tableExists) {
        echo "✅ remember_tokens table EXISTS\n\n";

        // Check table structure
        echo "📋 Table structure:\n";
        $stmt = $pdo->query("DESCRIBE remember_tokens");
        $columns = $stmt->fetchAll();

        foreach ($columns as $column) {
            echo "  - {$column['Field']}: {$column['Type']} " .
                 ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') .
                 ($column['Key'] ? " ({$column['Key']})" : '') . "\n";
        }
        echo "\n";

        // Check current entries
        echo "📊 Current entries in remember_tokens:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM remember_tokens");
        $count = $stmt->fetch()['count'];
        echo "  Total entries: {$count}\n";

        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, user_id, LEFT(token_hash, 10) as token_preview, expires_at FROM remember_tokens ORDER BY created_at DESC LIMIT 5");
            $entries = $stmt->fetchAll();
            echo "  Recent entries:\n";
            foreach ($entries as $entry) {
                echo "    ID: {$entry['id']}, User: {$entry['user_id']}, Token: {$entry['token_preview']}..., Expires: {$entry['expires_at']}\n";
            }
        }
        echo "\n";

        // Test INSERT permissions
        echo "🧪 Testing INSERT permission...\n";
        try {
            $testUserId = 999999; // Use a non-existent user ID for testing
            $testToken = hash('sha256', 'test_token_' . time());
            $testExpiry = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$testUserId, $testToken, $testExpiry]);

            echo "✅ INSERT permission works\n";

            // Clean up test entry
            $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$testUserId]);
            echo "✅ DELETE permission works\n";

        } catch (Exception $e) {
            echo "❌ INSERT/DELETE permission failed: " . $e->getMessage() . "\n";
        }
        echo "\n";

        // Test with actual user ID
        echo "🧪 Testing with actual user (kevin)...\n";
        try {
            // Get kevin's user ID
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute(['kevin']);
            $user = $stmt->fetch();

            if ($user) {
                $userId = $user['user_id'];
                echo "  Kevin's user_id: {$userId}\n";

                // Test the exact operations done in Auth::storeRememberToken()
                echo "  Testing DELETE operation...\n";
                $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->execute([$userId]);
                echo "  ✅ DELETE successful\n";

                echo "  Testing INSERT operation...\n";
                $testToken = hash('sha256', 'test_token_' . time());
                $testExpiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
                $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $testToken, $testExpiry]);
                echo "  ✅ INSERT successful\n";

                // Clean up
                $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$userId]);
                echo "  ✅ Cleanup successful\n";

            } else {
                echo "  ❌ User 'kevin' not found\n";
            }

        } catch (Exception $e) {
            echo "  ❌ Test with kevin failed: " . $e->getMessage() . "\n";
        }

    } else {
        echo "❌ remember_tokens table DOES NOT EXIST\n";
        echo "\n📝 To fix this issue, run the following SQL:\n\n";
        echo "CREATE TABLE IF NOT EXISTS `remember_tokens` (\n";
        echo "    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\n";
        echo "    `user_id` INT(11) NOT NULL,\n";
        echo "    `token_hash` VARCHAR(64) NOT NULL,\n";
        echo "    `expires_at` DATETIME NOT NULL,\n";
        echo "    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    PRIMARY KEY (`id`),\n";
        echo "    KEY `idx_user_id` (`user_id`),\n";
        echo "    KEY `idx_token_hash` (`token_hash`),\n";
        echo "    KEY `idx_expires_at` (`expires_at`),\n";
        echo "    CONSTRAINT `fk_remember_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE\n";
        echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    }

} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Check your database configuration in config/config.php\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "DIAGNOSIS COMPLETE\n";
echo str_repeat("=", 60) . "\n";
?>