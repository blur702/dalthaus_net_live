<?php
header("Content-Type: text/plain");

echo "=== Checking Settings Table Schema ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dalthaus_maincms;charset=utf8mb4",
        "dalthaus_maincms",
        "f4!,Wpds=w6*=~+1"
    );
    
    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        echo "Settings table does not exist!\n";
        echo "Creating settings table...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            setting_id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_id),
            UNIQUE KEY setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "Settings table created!\n";
        
        // Insert default settings
        $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
        echo "Default settings inserted!\n";
    } else {
        // Check column names
        $stmt = $pdo->query("DESCRIBE settings");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Settings table columns:\n";
        foreach ($columns as $col) {
            echo "  - $col\n";
        }
        
        // Check if we have setting_name or setting_key
        if (in_array("setting_name", $columns)) {
            echo "\n⚠️ Table has 'setting_name' column - needs to be renamed to 'setting_key'!\n";
            
            // Rename column
            $pdo->exec("ALTER TABLE settings CHANGE setting_name setting_key varchar(100) NOT NULL");
            echo "✓ Column renamed from setting_name to setting_key\n";
        } elseif (in_array("setting_key", $columns)) {
            echo "\n✓ Table already has correct 'setting_key' column\n";
        }
    }
    
    // Ensure maintenance_mode is set to 0
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute(["maintenance_mode", "0", "0"]);
    echo "\n✓ Maintenance mode disabled\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>