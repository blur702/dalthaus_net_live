<?php
// Fix to disable maintenance mode by updating database
header('Content-Type: text/plain');

require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';

echo "=== Fixing Maintenance Mode ===\n\n";

try {
    // Connect to database
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );
    
    echo "✓ Connected to database\n\n";
    
    // Check current maintenance mode setting
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $currentValue = $stmt->fetchColumn();
    
    if ($currentValue !== false) {
        echo "Current maintenance_mode value: '$currentValue'\n";
        
        // Update to disabled
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = ?");
        $stmt->execute(['maintenance_mode']);
        echo "✓ Maintenance mode disabled in database\n";
    } else {
        echo "No maintenance_mode setting found, inserting...\n";
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['maintenance_mode', '0']);
        echo "✓ Maintenance mode setting created and disabled\n";
    }
    
    // Verify all settings
    echo "\n=== All Settings ===\n";
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("  %-25s => %s\n", $row['setting_key'], $row['setting_value']);
    }
    
    echo "\n✅ Maintenance mode fix complete!\n";
    echo "The site should now be accessible.\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
?>