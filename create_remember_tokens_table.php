<?php
/**
 * Migration script to create remember_tokens table
 * Run this script to ensure the Remember Me functionality works
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "Creating remember_tokens table...\n\n";

// Load configuration
$config = require __DIR__ . '/config/config.php';

// Connect to database
try {
    $db = \CMS\Utils\Database::getInstance($config['database']);
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if table already exists
try {
    $tableExists = $db->query("SHOW TABLES LIKE 'remember_tokens'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "⚠️  Table 'remember_tokens' already exists\n";
        
        // Check if structure is correct
        $columns = $db->query("DESCRIBE remember_tokens")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'token_hash', 'expires_at', 'created_at'];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        if (!empty($missingColumns)) {
            echo "❌ Table structure is incomplete. Missing columns: " . implode(', ', $missingColumns) . "\n";
            echo "Please manually fix the table structure or drop and recreate it.\n";
            exit(1);
        }
        
        echo "✅ Table structure looks correct\n";
        
        // Show current token count
        $tokenCount = $db->query("SELECT COUNT(*) as count FROM remember_tokens")->fetch();
        echo "📊 Current tokens in table: " . $tokenCount['count'] . "\n";
        
        // Clean up expired tokens
        $deleted = $db->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        if ($deleted > 0) {
            echo "🧹 Cleaned up $deleted expired tokens\n";
        }
    } else {
        echo "📝 Creating remember_tokens table...\n";
        
        $sql = "
        CREATE TABLE remember_tokens (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_token_hash (token_hash),
            INDEX idx_expires_at (expires_at),
            CONSTRAINT fk_remember_tokens_user 
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Stores remember me tokens for persistent login';
        ";
        
        $db->exec($sql);
        echo "✅ Table 'remember_tokens' created successfully!\n";
        
        // Verify table was created
        $verify = $db->query("SHOW TABLES LIKE 'remember_tokens'")->rowCount() > 0;
        if ($verify) {
            echo "✅ Table creation verified\n";
            
            // Show table structure
            echo "\n📋 Table structure:\n";
            $columns = $db->query("DESCRIBE remember_tokens")->fetchAll();
            foreach ($columns as $column) {
                echo "   - {$column['Field']} ({$column['Type']})\n";
            }
        } else {
            echo "❌ Failed to verify table creation\n";
            exit(1);
        }
    }
    
    echo "\n✅ Remember tokens table is ready!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test the Remember Me functionality at: /debug_remember_me.php\n";
    echo "2. Login with Remember Me checked at: /admin/login\n";
    echo "3. Clear your session and verify auto-login works\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}