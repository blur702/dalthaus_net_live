<?php
session_start();
session_destroy();
session_start();

require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";

// Test database
try {
    $db = CMS\Utils\Database::getInstance($config["database"]);
    echo "Database: CONNECTED\n";
    
    // Test Settings model
    $maintenanceMode = CMS\Models\Settings::getBool("maintenance_mode", false);
    echo "Settings model: WORKING (maintenance=" . ($maintenanceMode ? "on" : "off") . ")\n";
    
    // Test BaseController
    $controller = new class extends CMS\Controllers\BaseController {
        protected function initialize(): void {}
    };
    echo "BaseController: INSTANTIATED\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>