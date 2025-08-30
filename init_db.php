<?php
/**
 * Initialize database tables
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Initializing Database</h1><pre>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    echo "Running database setup...\n";
    Database::setup();
    echo "✅ Database setup completed successfully!\n";
    
    // Test database by checking tables
    $pdo = Database::getInstance();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables created:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check content count
    $stmt = $pdo->query("SELECT COUNT(*) FROM content");
    $count = $stmt->fetchColumn();
    echo "\nContent records: $count\n";
    
    if ($count == 0) {
        echo "\nCreating sample content...\n";
        
        // Insert sample article
        $stmt = $pdo->prepare("INSERT INTO content (type, title, slug, body, status, published_at) VALUES (?, ?, ?, ?, 'published', NOW())");
        $stmt->execute(['article', 'Welcome to Dalthaus.net', 'welcome-to-dalthaus-net', 
            '<p>Welcome to the new Dalthaus Photography website. This is a sample article to demonstrate the CMS functionality.</p>']);
        
        // Insert sample photobook
        $stmt->execute(['photobook', 'The Storyteller\'s Legacy', 'the-storytellers-legacy', 
            '<p>This is a sample photobook about Elena, a photographer whose work captured the essence of human stories.</p>']);
        
        echo "✅ Sample content created\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "</pre>";
echo "<h2>Test the Site:</h2>";
echo "<p><a href='/' style='font-size: 20px;'>🏠 Go to Homepage</a></p>";
?>