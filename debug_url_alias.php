<?php
/**
 * Debug script to check url_alias values in the database
 */

require_once __DIR__ . '/src/Utils/Database.php';
require_once __DIR__ . '/config/config.php';

try {
    $database = CMS\Utils\Database::getInstance($config['database']);
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("SELECT content_id, title, url_alias, content_type FROM content WHERE content_type = 'article' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 Checking url_alias values in database:\n";
    echo "==========================================\n\n";
    
    foreach ($articles as $article) {
        echo "ID: {$article['content_id']}\n";
        echo "Title: {$article['title']}\n";
        echo "URL Alias: '{$article['url_alias']}'\n";
        echo "Content Type: {$article['content_type']}\n";
        echo "Expected URL: /article/{$article['url_alias']}\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>