<?php
/**
 * Test script to verify autosave endpoint functionality without authentication
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add CORS headers for testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // First test database connection
    $config = require __DIR__ . '/config/config.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    
    // Debug: Check if config is loaded properly
    $configLoaded = isset($config['database']);
    $dbConfig = $config['database'] ?? null;
    
    if (!$configLoaded || !$dbConfig) {
        throw new Exception('Database configuration not found in config file');
    }
    
    $db = CMS\Utils\Database::getInstance($dbConfig);
    $connection = $db->getConnection();
    
    // Test a simple query
    $stmt = $connection->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database_test' => true,
        'config_loaded' => $configLoaded,
        'query_test' => $result['test'] === 1,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'database_test' => false,
        'error_trace' => $e->getTraceAsString(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>