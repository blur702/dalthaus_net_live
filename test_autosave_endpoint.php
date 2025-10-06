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
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    
    $db = CMS\Utils\Database::getInstance();
    $connection = $db->getConnection();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database_test' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'database_test' => false,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>