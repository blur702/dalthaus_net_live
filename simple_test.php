<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . phpversion() . "\n";

try {
    require_once 'includes/config.php';
    echo "Config loaded\n";
    
    require_once 'includes/database.php';
    $pdo = Database::getInstance();
    echo "Database connected\n";
    
    // Quick check for content table
    $stmt = $pdo->query("SELECT COUNT(*) FROM content");
    $count = $stmt->fetchColumn();
    echo "Content records: " . $count . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>