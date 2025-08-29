<?php
/**
 * Reusable header template for all front-end pages
 * Includes site header, navigation menu, and common head elements
 */

// Ensure we have database connection
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
    $pdo = Database::getInstance();
}

// Get menu items if not already loaded
if (!isset($topMenu)) {
    $topMenu = $pdo->query("
        SELECT m.*, c.title, c.slug, c.type 
        FROM menus m
        JOIN content c ON m.content_id = c.id
        WHERE m.location = 'top' 
        AND m.is_active = TRUE
        AND c.deleted_at IS NULL
        ORDER BY m.sort_order
    ")->fetchAll();
}

// Get site settings if not already loaded
if (!isset($settings)) {
    $settings = [];
    $result = $pdo->query("SELECT setting_key, setting_value FROM settings");
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default page title if not provided
if (!isset($pageTitle)) {
    $pageTitle = $settings['site_title'] ?? 'Dalthaus.net';
} else {
    $pageTitle = $pageTitle . ' - ' . ($settings['site_title'] ?? 'Dalthaus.net');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS Loading: Multiple Methods for Maximum Compatibility -->
    <!-- Method 1: Standard external CSS with cache busting -->
    <link rel="stylesheet" href="/assets/css/public.css?v=<?= time() ?>" type="text/css" media="all" id="main-css">
    
    <!-- Method 2: Alternative CSS path -->
    <link rel="stylesheet" href="./assets/css/public.css?v=<?= time() ?>" type="text/css" media="all" id="alt-css">
    
    <!-- Method 3: CSS via PHP script with proper headers -->
    <link rel="stylesheet" href="/css-delivery.php?v=<?= time() ?>" type="text/css" media="all" id="php-css">
    
    <!-- Method 4: Preload CSS for better performance -->
    <link rel="preload" href="/assets/css/public.css?v=<?= time() ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/public.css?v=<?= time() ?>"></noscript>
    
    <!-- Method 5: Critical inline CSS as emergency fallback -->
    <style id="critical-css">
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
        
        a:hover { color: #2980b9; }
        
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
        
        .slide-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background: white;
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .slide-menu.active {
            right: 0;
        }
        
        .slide-menu ul {
            list-style: none;
            padding: 80px 0 20px 0;
        }
        
        .slide-menu li {
            border-bottom: 1px solid #ecf0f1;
        }
        
        .slide-menu a {
            display: block;
            padding: 15px 25px;
            color: #333;
            font-family: 'Arimo', Arial, sans-serif;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        .slide-menu a:hover {
            background: #f8f9fa;
            color: #3498db;
        }
        
        .no-content {
            color: #7f8c8d;
            font-style: italic;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
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
        // CSS Loading Detection and Fallback System
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a moment for CSS to load, then check
            setTimeout(function() {
                // Test if external CSS loaded properly
                var testElement = document.createElement('div');
                testElement.className = 'page-wrapper';
                testElement.style.position = 'absolute';
                testElement.style.left = '-9999px';
                document.body.appendChild(testElement);
                
                var computed = window.getComputedStyle(testElement);
                var cssLoaded = computed.display === 'flex' || computed.minHeight === '100vh';
                
                document.body.removeChild(testElement);
                
                if (!cssLoaded) {
                    console.warn('External CSS failed to load, attempting to inject full CSS...');
                    injectFullCSS();
                }
            }, 1000);
        });
        
        function injectFullCSS() {
            // Try multiple methods to inject CSS
            var methods = [
                '/css-delivery.php?v=' + Date.now(),
                '/assets/css/public.css?v=' + Date.now(),
                './assets/css/public.css?v=' + Date.now()
            ];
            
            function tryMethod(index) {
                if (index >= methods.length) {
                    console.error('All CSS injection methods failed');
                    expandCriticalCSS();
                    return;
                }
                
                var xhr = new XMLHttpRequest();
                xhr.open('GET', methods[index], true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var style = document.createElement('style');
                        style.id = 'injected-full-css-' + index;
                        style.textContent = xhr.responseText;
                        document.head.appendChild(style);
                        console.log('Full CSS injected successfully via method:', methods[index]);
                    } else if (xhr.readyState === 4) {
                        console.warn('CSS injection method failed:', methods[index], 'status:', xhr.status);
                        // Try next method
                        tryMethod(index + 1);
                    }
                };
                xhr.send();
            }
            
            tryMethod(0);
        }
        
        function expandCriticalCSS() {
            console.log('Using expanded critical CSS as final fallback');
            // The critical CSS already in the page should provide basic styling
            // This is our final fallback - the page should still be usable
        }
    </script>
    
    <?php if (isset($additionalStyles)): ?>
    <?= $additionalStyles ?>
    <?php endif; ?>
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
                
                <!-- Hamburger menu button (always visible) -->
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
                    // Build URL based on content type and special cases
                    if ($item['type'] === 'page') {
                        // Special handling for system pages
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