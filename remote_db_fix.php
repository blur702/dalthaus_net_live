<?php
// Simple database fix script for missing columns
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Use the same configuration as the main application
    $pdo = new PDO(
        'mysql:host=localhost;dbname=dalthaus_photocms;charset=utf8mb4',
        'dalthaus_photocms',
        'f-I*GSo^Urt*k*&#',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "Database connection successful.\n";
    
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE content");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $columnNames) . "\n\n";
    
    // Add missing columns for articles.php
    $alterQueries = [];
    
    if (!in_array('featured_image', $columnNames)) {
        $alterQueries[] = "ALTER TABLE content ADD COLUMN featured_image VARCHAR(500) NULL";
        echo "Will add: featured_image\n";
    }
    
    if (!in_array('teaser_image', $columnNames)) {
        $alterQueries[] = "ALTER TABLE content ADD COLUMN teaser_image VARCHAR(500) NULL";
        echo "Will add: teaser_image\n";
    }
    
    if (!in_array('teaser_text', $columnNames)) {
        $alterQueries[] = "ALTER TABLE content ADD COLUMN teaser_text TEXT NULL";
        echo "Will add: teaser_text\n";
    }
    
    if (!in_array('page_breaks', $columnNames)) {
        $alterQueries[] = "ALTER TABLE content ADD COLUMN page_breaks JSON NULL";
        echo "Will add: page_breaks\n";
    }
    
    if (!in_array('page_count', $columnNames)) {
        $alterQueries[] = "ALTER TABLE content ADD COLUMN page_count INT DEFAULT 1";
        echo "Will add: page_count\n";
    }
    
    if (empty($alterQueries)) {
        echo "All required columns already exist.\n";
    } else {
        echo "\nExecuting ALTER TABLE statements...\n";
        foreach ($alterQueries as $query) {
            $pdo->exec($query);
            echo "✓ " . $query . "\n";
        }
        echo "Database fix complete.\n";
    }
    
    echo "\n=== TESTING ===\n";
    
    // Test that we can select from content with new columns
    $stmt = $pdo->query("SELECT id, title, featured_image, teaser_image, teaser_text, page_breaks, page_count FROM content LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Content table query with new columns successful\n";
    } else {
        echo "ℹ Content table is empty (expected for new installation)\n";
    }
    
    echo "\n=== STATUS ===\n";
    echo "Database fix completed successfully. The articles.php edit page should now work.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}