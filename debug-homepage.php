<?php
// Debug version of homepage to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Homepage</h1>";

// Include files safely and initialize database
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
    echo "<p>✓ Config loaded</p>";
}

if (file_exists('includes/database.php')) {
    require_once 'includes/database.php';
    echo "<p>✓ Database class loaded</p>";
}

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
    echo "<p>✓ Functions loaded</p>";
} else if (file_exists('functions-fixed.php')) {
    require_once 'functions-fixed.php';
    echo "<p>✓ Functions-fixed loaded</p>";
}

// Initialize database connection if not already done
$pdo = null;
if (!isset($pdo) && class_exists('Database')) {
    try {
        $pdo = Database::getInstance();
        echo "<p>✓ Database connection successful</p>";
    } catch (Exception $e) {
        echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        $pdo = null;
    }
}

// Test the functions
echo "<h2>Testing getRecentArticles function</h2>";

function debugGetRecentArticles($limit = 4) {
    global $pdo;
    
    echo "<p>PDO status: " . (isset($pdo) && $pdo ? "Connected" : "Not connected") . "</p>";
    
    if (!isset($pdo) || !$pdo) {
        echo "<p>Returning fallback data (no database)</p>";
        return [
            [
                'id' => 1,
                'title' => 'Ramchargers Conquer The Automatic',
                'slug' => 'ramchargers-conquer-the-automatic',
                'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing...',
                'author' => 'Don Althaus',
                'created_at' => '2025-09-01',
                'featured_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'Sample Article 2',
                'slug' => 'sample-2',
                'content' => 'This is sample content for testing...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'featured_image' => ''
            ]
        ];
    }
    
    try {
        echo "<p>Trying database query...</p>";
        $stmt = $pdo->prepare("
            SELECT id, title, slug, body as content, author, created_at, featured_image 
            FROM content 
            WHERE type = 'article' AND status = 'published' 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Query executed. Results count: " . count($results) . "</p>";
        
        if (empty($results)) {
            echo "<p>No results from database, returning fallback data</p>";
            return [
                [
                    'id' => 1,
                    'title' => 'Ramchargers Conquer The Automatic',
                    'slug' => 'ramchargers-conquer-the-automatic',
                    'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-09-01',
                    'featured_image' => ''
                ],
                [
                    'id' => 2,
                    'title' => 'Sample Article 2',
                    'slug' => 'sample-2',
                    'content' => 'This is sample content for testing...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-29',
                    'featured_image' => ''
                ]
            ];
        }
        
        return $results;
    } catch (PDOException $e) {
        echo "<p>❌ Database query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Returning fallback data due to error</p>";
        return [
            [
                'id' => 1,
                'title' => 'Ramchargers Conquer The Automatic',
                'slug' => 'ramchargers-conquer-the-automatic',
                'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing...',
                'author' => 'Don Althaus',
                'created_at' => '2025-09-01',
                'featured_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'Sample Article 2',
                'slug' => 'sample-2',
                'content' => 'This is sample content for testing...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'featured_image' => ''
            ]
        ];
    }
}

$articles = debugGetRecentArticles(4);

echo "<h3>Articles result:</h3>";
echo "<pre>" . print_r($articles, true) . "</pre>";

// Check if articles are empty
if (empty($articles)) {
    echo "<p style='color: red;'>❌ Articles array is empty!</p>";
} else {
    echo "<p style='color: green;'>✓ Articles array has " . count($articles) . " items</p>";
}
?>