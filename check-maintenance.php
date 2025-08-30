<?php
/**
 * Check Maintenance Mode Status
 */
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Maintenance Mode Check</h2>\n";

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $maintenanceMode = $stmt->fetchColumn();
    
    echo "<p>Maintenance Mode Status: " . ($maintenanceMode === '1' ? 'ENABLED' : 'DISABLED') . "</p>\n";
    
    if ($maintenanceMode === '1') {
        echo "<p style='color: red;'><strong>WARNING: Maintenance mode is ENABLED!</strong></p>\n";
        echo "<p>This will prevent access to all public pages.</p>\n";
        echo "<p>To disable: UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode';</p>\n";
    } else {
        echo "<p style='color: green;'>✓ Maintenance mode is disabled - site should be accessible</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>This could indicate the database connection is still failing.</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>