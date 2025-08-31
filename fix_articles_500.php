<?php
/**
 * Emergency fix for articles.php 500 error
 * Adds missing database columns needed by articles.php
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "Connected to database.\n";
    
    // Check if content table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'content'");
    if ($stmt->rowCount() === 0) {
        echo "Content table does not exist. Run setup.php first.\n";
        exit(1);
    }
    
    // Get current table structure
    $stmt = $pdo->query("DESCRIBE content");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $columnNames) . "\n";
    
    // Add missing columns
    $missingColumns = [];
    
    if (!in_array('featured_image', $columnNames)) {
        $pdo->exec("ALTER TABLE content ADD COLUMN featured_image VARCHAR(500) NULL AFTER sort_order");
        $missingColumns[] = 'featured_image';
        echo "Added featured_image column.\n";
    }
    
    if (!in_array('teaser_image', $columnNames)) {
        $pdo->exec("ALTER TABLE content ADD COLUMN teaser_image VARCHAR(500) NULL AFTER featured_image");
        $missingColumns[] = 'teaser_image';
        echo "Added teaser_image column.\n";
    }
    
    if (!in_array('teaser_text', $columnNames)) {
        $pdo->exec("ALTER TABLE content ADD COLUMN teaser_text TEXT NULL AFTER teaser_image");
        $missingColumns[] = 'teaser_text';
        echo "Added teaser_text column.\n";
    }
    
    if (!in_array('page_breaks', $columnNames)) {
        $pdo->exec("ALTER TABLE content ADD COLUMN page_breaks JSON NULL AFTER teaser_text");
        $missingColumns[] = 'page_breaks';
        echo "Added page_breaks column.\n";
    }
    
    if (!in_array('page_count', $columnNames)) {
        $pdo->exec("ALTER TABLE content ADD COLUMN page_count INT DEFAULT 1 AFTER page_breaks");
        $missingColumns[] = 'page_count';
        echo "Added page_count column.\n";
    }
    
    if (empty($missingColumns)) {
        echo "All required columns already exist.\n";
    } else {
        echo "Added missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Verify the fix worked
    echo "\nTesting articles.php dependencies...\n";
    
    // Test basic functions
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/page_tracker.php';
    
    if (function_exists('validateCSRFToken')) {
        echo "✓ validateCSRFToken function available\n";
    } else {
        echo "✗ validateCSRFToken function missing\n";
    }
    
    if (function_exists('updatePageTracking')) {
        echo "✓ updatePageTracking function available\n";
    } else {
        echo "✗ updatePageTracking function missing\n";
    }
    
    if (function_exists('generateCSRFToken')) {
        echo "✓ generateCSRFToken function available\n";
    } else {
        echo "✗ generateCSRFToken function missing\n";
    }
    
    // Test database query that articles.php uses
    try {
        $stmt = $pdo->query("SELECT * FROM content WHERE type = 'article' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1");
        echo "✓ Content query successful\n";
    } catch (Exception $e) {
        echo "✗ Content query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\nFix complete. Try accessing the articles edit page now.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}