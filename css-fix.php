<?php
/**
 * Definitive CSS Loading Fix
 * 
 * This script implements multiple approaches to ensure CSS loads properly:
 * 1. Header-based CSS injection
 * 2. Inline CSS fallback
 * 3. Multiple CSS delivery methods
 * 4. Content-Type header fixes
 * 5. Cache-busting
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Function to ensure proper CSS delivery
function ensureCSSDelivery() {
    $cssFile = __DIR__ . '/assets/css/public.css';
    
    if (!file_exists($cssFile)) {
        return false;
    }
    
    // Method 1: Try to deliver CSS with proper headers
    if (isset($_GET['css']) && $_GET['css'] === 'direct') {
        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        
        echo file_get_contents($cssFile);
        exit;
    }
    
    return true;
}

// Function to generate CSS with multiple delivery methods
function generateCSSIncludes() {
    $timestamp = time();
    $cssFile = '/assets/css/public.css';
    
    return "
    <!-- CSS Loading: Multiple Methods for Maximum Compatibility -->
    
    <!-- Method 1: Standard external CSS -->
    <link rel=\"stylesheet\" href=\"{$cssFile}?v={$timestamp}\" type=\"text/css\" media=\"all\">
    
    <!-- Method 2: Alternative path -->
    <link rel=\"stylesheet\" href=\".{$cssFile}?v={$timestamp}\" type=\"text/css\" media=\"all\">
    
    <!-- Method 3: PHP-served CSS with proper headers -->
    <link rel=\"stylesheet\" href=\"css-fix.php?css=direct&v={$timestamp}\" type=\"text/css\" media=\"all\">
    
    <!-- Method 4: Preload for performance -->
    <link rel=\"preload\" href=\"{$cssFile}?v={$timestamp}\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet'\">
    <noscript><link rel=\"stylesheet\" href=\"{$cssFile}?v={$timestamp}\"></noscript>
    
    <!-- Method 5: Critical inline CSS as fallback -->
    <style id=\"critical-css\">
        /* Critical CSS - Always loads */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Gelasio', Georgia, serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Arimo', Arial, sans-serif;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 1rem;
        }
        
        a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        a:hover {
            color: #2980b9;
        }
        
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .site-header {
            width: 100%;
            background: #fff;
            color: #333;
            position: relative;
            padding: 0;
            margin: 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        
        .site-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-family: 'Arimo', Arial, sans-serif;
        }
        
        .site-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .main-content {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }
        
        .content-layout {
            display: grid;
            grid-template-columns: 66fr 34fr;
            gap: 3rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-family: 'Arimo', Arial, sans-serif;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        .front-article-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .front-article-thumb {
            flex: 0 0 120px;
            height: 90px;
            background: #f5f5f5;
            overflow: hidden;
        }
        
        .front-article-thumb img,
        .front-article-thumb .image-placeholder {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .front-article-content {
            flex: 1;
        }
        
        .front-article-title {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
            font-family: 'Arimo', Arial, sans-serif;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .front-article-title a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .front-article-meta {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
            font-family: 'Arimo', Arial, sans-serif;
        }
        
        .front-article-teaser {
            font-size: 0.8125rem;
            color: #555;
            line-height: 1.4;
        }
        
        .front-photobook-item {
            margin-bottom: 2rem;
        }
        
        .front-photobook-thumbnail {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            background: #f5f5f5;
            margin-bottom: 0.75rem;
        }
        
        .front-photobook-title {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            font-family: 'Arimo', Arial, sans-serif;
        }
        
        .front-photobook-title a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .front-photobook-meta {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
            font-family: 'Arimo', Arial, sans-serif;
        }
        
        .front-photobook-excerpt {
            font-size: 0.875rem;
            color: #555;
            line-height: 1.4;
        }
        
        .hamburger-menu {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1001;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
            padding: 8px;
        }
        
        .hamburger-menu span {
            display: block;
            width: 24px;
            height: 2px;
            background: #666;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            
            .site-title {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <script>
        // JavaScript to ensure CSS loads and inject fallback if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Check if external CSS loaded properly
            setTimeout(function() {
                var testElement = document.createElement('div');
                testElement.className = 'page-wrapper';
                testElement.style.position = 'absolute';
                testElement.style.left = '-9999px';
                document.body.appendChild(testElement);
                
                var computed = window.getComputedStyle(testElement);
                var cssLoaded = computed.display === 'flex' || computed.minHeight === '100vh';
                
                document.body.removeChild(testElement);
                
                if (!cssLoaded) {
                    console.warn('External CSS failed to load, injecting full CSS inline...');
                    injectFullCSS();
                }
            }, 1000);
        });
        
        function injectFullCSS() {
            fetch('css-fix.php?css=direct')
                .then(response => response.text())
                .then(css => {
                    var style = document.createElement('style');
                    style.id = 'injected-css';
                    style.textContent = css;
                    document.head.appendChild(style);
                    console.log('Full CSS injected successfully');
                })
                .catch(error => {
                    console.error('Failed to inject CSS:', error);
                });
        }
    </script>
    ";
}

// Handle CSS delivery request
ensureCSSDelivery();

// If this is a regular page request, generate fixed homepage
$pdo = Database::getInstance();

// Get published articles
$articles = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'article'
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get published photobooks
$photobooks = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'photobook'
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get site settings
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get menu items
$topMenu = $pdo->query("
    SELECT m.*, c.title, c.slug, c.type 
    FROM menus m
    JOIN content c ON m.content_id = c.id
    WHERE m.location = 'top' 
    AND m.is_active = TRUE
    AND c.deleted_at IS NULL
    ORDER BY m.sort_order
")->fetchAll();

$pageTitle = $settings['site_title'] ?? 'Dalthaus.net';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    
    <?= generateCSSIncludes() ?>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header with site title and hamburger menu -->
        <header class="site-header" <?php if (!empty($settings['header_image'])): ?>
            style="background-image: linear-gradient(<?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>, <?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>), url('<?= htmlspecialchars($settings['header_image']) ?>'); background-size: cover; background-position: center; height: <?= $settings['header_height'] ?? '200' ?>px; color: <?= $settings['header_text_color'] ?? '#333333' ?>;"
        <?php else: ?>
            style="color: <?= $settings['header_text_color'] ?? '#333333' ?>;"
        <?php endif; ?>>
            <div class="header-content">
                <div class="header-text">
                    <h1 class="site-title">
                        <a href="/" style="color: inherit; text-decoration: none;">
                            <?= $settings['site_title'] ?? 'Dalthaus.net' ?>
                        </a>
                    </h1>
                    <?php if (!empty($settings['site_motto'])): ?>
                    <p class="site-motto" style="color: <?= $settings['header_text_color'] ?? '#333333' ?>;"><?= $settings['site_motto'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Hamburger menu button -->
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        
        <!-- Slide-out navigation menu -->
        <nav class="slide-menu" id="slide-menu">
            <ul>
                <?php foreach ($topMenu as $item): 
                    // Build URL based on content type
                    if ($item['type'] === 'page') {
                        if ($item['slug'] === 'home') {
                            $url = '/';
                        } elseif ($item['slug'] === 'articles-listing') {
                            $url = '/articles';
                        } elseif ($item['slug'] === 'photobooks-listing') {
                            $url = '/photobooks';
                        } else {
                            $url = '/' . $item['slug'];
                        }
                    } elseif ($item['type'] === 'article') {
                        $url = '/article/' . $item['slug'];
                    } elseif ($item['type'] === 'photobook') {
                        $url = '/photobook/' . $item['slug'];
                    } else {
                        $url = '/' . $item['slug'];
                    }
                ?>
                <li><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Main content area -->
        <main class="main-content" role="main">
            <div class="content-layout">
                <!-- Left column: Articles (66%) -->
                <section class="articles-section">
                    <h2 class="section-title">Articles</h2>
                    <div class="articles-list">
                        <?php if ($articles && count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                            <article class="front-article-item">
                                <div class="front-article-thumb">
                                    <?php
                                    $imgSrc = $article['teaser_image'] ?? $article['featured_image'] ?? null;
                                    if (!$imgSrc && !empty($article['content'])) {
                                        preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $article['content'], $imgMatch);
                                        $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                    }
                                    $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                    
                                    if ($hasImage) {
                                        echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="">';
                                    } else {
                                        echo '<div class="image-placeholder"></div>';
                                    }
                                    ?>
                                </div>
                                <div class="front-article-content">
                                    <h3 class="front-article-title">
                                        <a href="/article/<?= htmlspecialchars($article['alias']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="front-article-meta">
                                        Don Althaus · <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?> · Articles
                                    </div>
                                    <div class="front-article-teaser">
                                        <?= htmlspecialchars($article['teaser_text'] ?? (mb_substr(strip_tags($article['content'] ?? ''), 0, 150) . '...')) ?>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No articles published yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Right column: Photobooks (33%) -->
                <aside class="photobooks-section">
                    <h2 class="section-title">Photo Books</h2>
                    <div class="photobooks-list">
                        <?php if ($photobooks && count($photobooks) > 0): ?>
                            <?php foreach ($photobooks as $book): ?>
                            <div class="front-photobook-item">
                                <?php
                                $imgSrc = $book['teaser_image'] ?? $book['featured_image'] ?? null;
                                if (!$imgSrc && !empty($book['body'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $book['body'], $imgMatch);
                                    $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                }
                                $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                
                                if ($hasImage) {
                                    echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="front-photobook-thumbnail">';
                                } else {
                                    echo '<div class="image-placeholder front-photobook-thumbnail"></div>';
                                }
                                ?>
                                <h3 class="front-photobook-title">
                                    <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h3>
                                <div class="front-photobook-meta">
                                    Don Althaus · <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?> · Photo Books
                                </div>
                                <div class="front-photobook-excerpt">
                                    <?= htmlspecialchars($book['teaser_text'] ?? (mb_substr(strip_tags($book['body'] ?? ''), 0, 120) . '...')) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No photo books published yet.</p>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="site-footer">
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> <?= $settings['site_title'] ?? 'Dalthaus.net' ?>. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script>
        // Hamburger menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger-menu');
            const slideMenu = document.getElementById('slide-menu');
            
            if (hamburger && slideMenu) {
                hamburger.addEventListener('click', function() {
                    hamburger.classList.toggle('active');
                    slideMenu.classList.toggle('active');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!hamburger.contains(event.target) && !slideMenu.contains(event.target)) {
                        hamburger.classList.remove('active');
                        slideMenu.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>