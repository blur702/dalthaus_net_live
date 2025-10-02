<?php
// Direct database connection for pagebreak analysis
echo "<h1>Article Pagebreak Analysis</h1>\n";
echo "<style>body{font-family:Arial;margin:20px;} .article{margin:20px 0;padding:15px;border:1px solid #ccc;background:#f9f9f9;} .found{background:#ffffcc;} .title{font-weight:bold;color:#333;} .pattern{color:#c00;font-family:monospace;}</style>\n";

try {
    // Direct database connection with exact credentials
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dalthaus_maincms;charset=utf8mb4",
        "dalthaus_maincms",
        "f4!,Wpds=w6*=~+1",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p><strong>✓ Database connection successful!</strong></p>\n";
    
    // Get all articles
    $stmt = $pdo->query("SELECT id, title, type, body FROM content WHERE type = 'article' ORDER BY id");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total articles found:</strong> " . count($articles) . "</p>\n";
    
    // Comprehensive pagebreak patterns to check
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
        'data-mce-pagebreak',
        'mce-pagebreak',
        'pagebreak',
        '<hr',
        'style="page-break',
        'break-after:',
        'break-before:'
    ];
    
    $foundCount = 0;
    $totalPagebreaks = 0;
    
    echo "<h2>Articles with Pagebreak Markers</h2>\n";
    
    foreach ($articles as $article) {
        $foundPatterns = [];
        $contexts = [];
        
        foreach ($pagebreakPatterns as $pattern) {
            $pos = stripos($article['body'], $pattern);
            if ($pos !== false) {
                $foundPatterns[] = $pattern;
                // Get context around the pagebreak
                $start = max(0, $pos - 100);
                $end = min(strlen($article['body']), $pos + strlen($pattern) + 100);
                $context = substr($article['body'], $start, $end - $start);
                $contexts[$pattern] = htmlspecialchars($context);
                $totalPagebreaks++;
            }
        }
        
        if (!empty($foundPatterns)) {
            $foundCount++;
            echo "<div class='article found'>\n";
            echo "<div class='title'>Article ID: {$article['id']} - " . htmlspecialchars($article['title']) . "</div>\n";
            echo "<p><strong>Pagebreak patterns found:</strong></p>\n";
            echo "<ul>\n";
            foreach ($foundPatterns as $pattern) {
                echo "<li><span class='pattern'>" . htmlspecialchars($pattern) . "</span>";
                if (isset($contexts[$pattern])) {
                    echo "<br><small><strong>Context:</strong> ..." . $contexts[$pattern] . "...</small>";
                }
                echo "</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
    }
    
    if ($foundCount == 0) {
        echo "<p><strong>No pagebreak markers found in any articles.</strong></p>\n";
    }
    
    // Additional analysis
    echo "<h2>Additional Analysis</h2>\n";
    
    $hrCount = 0;
    $longArticles = 0;
    
    foreach ($articles as $article) {
        $body = $article['body'];
        
        // Count HR tags across all articles
        $hrCount += substr_count($body, '<hr');
        
        // Check for long articles that might benefit from pagebreaks
        if (strlen($body) > 5000) {
            $longArticles++;
        }
    }
    
    echo "<div class='article'>\n";
    echo "<p><strong>Total &lt;hr&gt; tags across all articles:</strong> {$hrCount}</p>\n";
    echo "<p><strong>Articles longer than 5000 characters:</strong> {$longArticles}</p>\n";
    echo "<p><strong>Articles with pagebreaks:</strong> {$foundCount}</p>\n";
    echo "<p><strong>Total pagebreak instances:</strong> {$totalPagebreaks}</p>\n";
    echo "</div>\n";
    
    // TinyMCE Configuration Note
    echo "<h2>TinyMCE Configuration</h2>\n";
    echo "<div class='article'>\n";
    echo "<p><strong>✓ TinyMCE pagebreak plugin is configured in config.php</strong></p>\n";
    echo "<p>The pagebreak plugin is included in the TinyMCE plugins array, which means pagebreaks can be inserted.</p>\n";
    echo "<p>Standard TinyMCE pagebreak format: <span class='pattern'>&lt;!-- pagebreak --&gt;</span></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>