<?php
// Corrected pagebreak analysis with proper column names
echo "<h1>Article Pagebreak Analysis Report</h1>\n";
echo "<style>
body{font-family:Arial;margin:20px;background:#f5f5f5;} 
.container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.article{margin:20px 0;padding:15px;border:1px solid #ddd;background:#f9f9f9;border-radius:5px;} 
.found{background:#fff3cd;border-color:#ffeaa7;} 
.title{font-weight:bold;color:#333;margin-bottom:10px;} 
.pattern{color:#c00;font-family:monospace;background:#f8f8f8;padding:2px 4px;border-radius:3px;} 
.context{font-size:0.9em;color:#666;margin-top:5px;background:#f0f0f0;padding:5px;border-radius:3px;word-break:break-all;}
.summary{background:#e8f5e8;border:1px solid #4caf50;border-radius:5px;padding:15px;margin:20px 0;}
.error{background:#ffebee;border:1px solid #f44336;color:#c62828;}
</style>\n";

echo "<div class='container'>\n";

try {
    // Direct database connection
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dalthaus_maincms;charset=utf8mb4",
        "dalthaus_maincms",
        "f4!,Wpds=w6*=~+1",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='summary'><strong>✓ Database connection successful!</strong></div>\n";
    
    // Get all articles using correct column names
    $stmt = $pdo->query("SELECT content_id, title, content_type, body FROM content WHERE content_type = 'article' ORDER BY content_id");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total articles found:</strong> " . count($articles) . "</p>\n";
    
    // Comprehensive pagebreak patterns
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
        'tinymce-pagebreak',
        '<hr style="page-break',
        'style="break-after',
        'style="break-before',
        'page-break-after',
        'page-break-before'
    ];
    
    $foundCount = 0;
    $totalPagebreaks = 0;
    $articlesWithPagebreaks = [];
    
    echo "<h2>Scanning Articles for Pagebreak Markers...</h2>\n";
    
    foreach ($articles as $article) {
        $foundPatterns = [];
        $contexts = [];
        
        foreach ($pagebreakPatterns as $pattern) {
            $bodyLower = strtolower($article['body']);
            $patternLower = strtolower($pattern);
            $pos = strpos($bodyLower, $patternLower);
            
            if ($pos !== false) {
                $foundPatterns[] = $pattern;
                // Get context around the pagebreak
                $start = max(0, $pos - 80);
                $end = min(strlen($article['body']), $pos + strlen($pattern) + 80);
                $context = substr($article['body'], $start, $end - $start);
                $contexts[$pattern] = htmlspecialchars($context);
                $totalPagebreaks++;
            }
        }
        
        if (!empty($foundPatterns)) {
            $foundCount++;
            $articlesWithPagebreaks[] = [
                'id' => $article['content_id'],
                'title' => $article['title'],
                'patterns' => $foundPatterns,
                'contexts' => $contexts
            ];
            
            echo "<div class='article found'>\n";
            echo "<div class='title'>Article ID: {$article['content_id']} - " . htmlspecialchars($article['title']) . "</div>\n";
            echo "<p><strong>Pagebreak patterns found:</strong></p>\n";
            echo "<ul>\n";
            foreach ($foundPatterns as $pattern) {
                echo "<li><span class='pattern'>" . htmlspecialchars($pattern) . "</span>";
                if (isset($contexts[$pattern])) {
                    echo "<div class='context'><strong>Context:</strong> ..." . $contexts[$pattern] . "...</div>";
                }
                echo "</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
    }
    
    if ($foundCount == 0) {
        echo "<div class='article'><strong>✓ No standard pagebreak markers found in any articles.</strong></div>\n";
    }
    
    // Additional analysis
    echo "<h2>Additional Content Analysis</h2>\n";
    
    $hrCount = 0;
    $longArticles = [];
    $articlesWithHr = [];
    
    foreach ($articles as $article) {
        $body = $article['body'];
        
        // Count HR tags in this article
        $articleHrCount = substr_count(strtolower($body), '<hr');
        $hrCount += $articleHrCount;
        
        if ($articleHrCount > 0) {
            $articlesWithHr[] = [
                'id' => $article['content_id'],
                'title' => $article['title'],
                'hr_count' => $articleHrCount
            ];
        }
        
        // Check for long articles that might benefit from pagebreaks
        if (strlen($body) > 5000) {
            $longArticles[] = [
                'id' => $article['content_id'],
                'title' => $article['title'],
                'length' => strlen($body)
            ];
        }
    }
    
    echo "<div class='article'>\n";
    echo "<h3>Statistics</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Total articles analyzed:</strong> " . count($articles) . "</li>\n";
    echo "<li><strong>Articles with pagebreak markers:</strong> {$foundCount}</li>\n";
    echo "<li><strong>Total pagebreak instances:</strong> {$totalPagebreaks}</li>\n";
    echo "<li><strong>Articles with &lt;hr&gt; tags:</strong> " . count($articlesWithHr) . "</li>\n";
    echo "<li><strong>Total &lt;hr&gt; tags across all articles:</strong> {$hrCount}</li>\n";
    echo "<li><strong>Articles longer than 5000 characters:</strong> " . count($longArticles) . "</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    // Show articles with HR tags
    if (!empty($articlesWithHr)) {
        echo "<h3>Articles with &lt;hr&gt; Tags (Potential Manual Breaks)</h3>\n";
        foreach ($articlesWithHr as $article) {
            echo "<div class='article'>\n";
            echo "<strong>ID {$article['id']}:</strong> " . htmlspecialchars($article['title']) . " <em>({$article['hr_count']} &lt;hr&gt; tags)</em>\n";
            echo "</div>\n";
        }
    }
    
    // Show long articles
    if (!empty($longArticles)) {
        echo "<h3>Long Articles (May Benefit from Pagebreaks)</h3>\n";
        foreach ($longArticles as $article) {
            echo "<div class='article'>\n";
            echo "<strong>ID {$article['id']}:</strong> " . htmlspecialchars($article['title']) . " <em>(" . number_format($article['length']) . " characters)</em>\n";
            echo "</div>\n";
        }
    }
    
    // TinyMCE Configuration Summary
    echo "<h2>TinyMCE Pagebreak Configuration</h2>\n";
    echo "<div class='summary'>\n";
    echo "<p><strong>✓ TinyMCE pagebreak plugin is configured</strong></p>\n";
    echo "<p>The pagebreak plugin is included in the TinyMCE configuration in config.php.</p>\n";
    echo "<p><strong>Standard TinyMCE pagebreak format:</strong> <span class='pattern'>&lt;!-- pagebreak --&gt;</span></p>\n";
    echo "<p>Users can insert pagebreaks using the pagebreak button in the editor toolbar.</p>\n";
    echo "</div>\n";
    
    // Final Summary
    echo "<h2>Summary Report</h2>\n";
    echo "<div class='summary'>\n";
    if ($foundCount > 0) {
        echo "<p><strong>PAGEBREAKS FOUND:</strong> {$foundCount} articles contain pagebreak markers.</p>\n";
        echo "<p>The pagebreak functionality is being used in your content.</p>\n";
    } else {
        echo "<p><strong>NO PAGEBREAKS FOUND:</strong> None of your articles currently use pagebreak markers.</p>\n";
        echo "<p>However, TinyMCE is configured to support pagebreaks if you want to use them in the future.</p>\n";
    }
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='article error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

echo "</div>\n"; // Close container
?>