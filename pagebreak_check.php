<?php
// Simple pagebreak analysis script
require_once 'config/config.php';

// Simple output for web viewing
echo "<h1>Pagebreak Analysis Report</h1>\n";
echo "<style>body{font-family:Arial;margin:20px;} .article{margin:20px 0;padding:15px;border:1px solid #ccc;} .found{background:#ffffcc;} .title{font-weight:bold;color:#333;}</style>\n";

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->query("SELECT id, title, type, body FROM content WHERE type = 'article' ORDER BY id");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total articles found:</strong> " . count($articles) . "</p>\n";
    
    $pagebreakPatterns = [
        '<!-- pagebreak -->',
        '<!--pagebreak-->',
        '<hr class="pagebreak"',
        '<div class="pagebreak"',
        '[pagebreak]',
        '<!-- wp:nextpage -->',
        '<!--nextpage-->',
        '<nextpage>',
        '<!-- more -->',
        '<!--more-->',
        '<span class="mce-pagebreak"',
        'class="mce-pagebreak"',
        'data-mce-pagebreak'
    ];
    
    $foundCount = 0;
    
    foreach ($articles as $article) {
        $foundPatterns = [];
        
        foreach ($pagebreakPatterns as $pattern) {
            if (stripos($article['body'], $pattern) !== false) {
                $foundPatterns[] = $pattern;
            }
        }
        
        if (!empty($foundPatterns)) {
            $foundCount++;
            echo "<div class='article found'>\n";
            echo "<div class='title'>Article ID: {$article['id']} - {$article['title']}</div>\n";
            echo "<p><strong>Pagebreak patterns found:</strong></p>\n";
            echo "<ul>\n";
            foreach ($foundPatterns as $pattern) {
                echo "<li>" . htmlspecialchars($pattern) . "</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
    }
    
    if ($foundCount == 0) {
        echo "<p><strong>No standard pagebreak markers found in any articles.</strong></p>\n";
        
        // Check for other potential indicators
        echo "<h2>Checking for other potential page break indicators...</h2>\n";
        
        foreach ($articles as $article) {
            $body = $article['body'];
            $indicators = [];
            
            // Check for multiple <hr> tags
            $hrCount = substr_count($body, '<hr');
            if ($hrCount > 1) {
                $indicators[] = "Contains {$hrCount} &lt;hr&gt; tags";
            }
            
            // Check for break-related CSS classes
            if (preg_match('/class=["\'][^"\']*break[^"\']*["\']/', $body)) {
                $indicators[] = "Contains CSS classes with 'break' in the name";
            }
            
            // Check for common split indicators
            if (stripos($body, 'continued') !== false || stripos($body, 'part 2') !== false) {
                $indicators[] = "Contains text suggesting content continuation";
            }
            
            if (!empty($indicators)) {
                echo "<div class='article'>\n";
                echo "<div class='title'>Article ID: {$article['id']} - {$article['title']}</div>\n";
                echo "<ul>\n";
                foreach ($indicators as $indicator) {
                    echo "<li>{$indicator}</li>\n";
                }
                echo "</ul>\n";
                echo "</div>\n";
            }
        }
    }
    
    echo "<h2>Summary</h2>\n";
    echo "<p><strong>Articles with pagebreaks:</strong> {$foundCount}</p>\n";
    echo "<p><strong>Total articles checked:</strong> " . count($articles) . "</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>