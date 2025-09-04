<?php
// Minimal test to see if fallback data works
$articles = [];

// Test: Force empty articles to trigger fallback
if (empty($articles)) {
    $articles = [
        [
            'id' => 1,
            'title' => 'Test Article 1',
            'content' => 'This is a test article to verify the fallback system works',
            'author' => 'Don Althaus',
            'created_at' => '2025-09-01'
        ],
        [
            'id' => 2,
            'title' => 'Test Article 2',
            'content' => 'This is another test article',
            'author' => 'Don Althaus',
            'created_at' => '2025-08-29'
        ]
    ];
}

echo "<h1>Minimal Test</h1>";
echo "<p>Articles array has " . count($articles) . " items.</p>";
echo "<p>Is articles empty? " . (empty($articles) ? "YES" : "NO") . "</p>";

if (!empty($articles)) {
    echo "<h2>Articles:</h2>";
    foreach ($articles as $article) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
        echo "<h3>" . htmlspecialchars($article['title']) . "</h3>";
        echo "<p>" . htmlspecialchars($article['content']) . "</p>";
        echo "<small>By " . htmlspecialchars($article['author']) . " on " . htmlspecialchars($article['created_at']) . "</small>";
        echo "</div>";
    }
} else {
    echo "<p>No articles found.</p>";
}
?>