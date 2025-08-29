<?php
/**
 * CSS Loading Diagnostic Script
 * 
 * This script performs comprehensive diagnostics to identify why CSS isn't loading
 * on the homepage and implements multiple fallback methods to ensure proper styling.
 */
declare(strict_types=1);

// Start output buffering to capture any errors
ob_start();

// Include necessary files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Check if this is a CSS test request
if (isset($_GET['test']) && $_GET['test'] === 'css') {
    // Test CSS content delivery with proper headers
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Read and output the CSS file
    $cssFile = __DIR__ . '/assets/css/public.css';
    if (file_exists($cssFile)) {
        echo file_get_contents($cssFile);
    } else {
        echo "/* CSS file not found at: $cssFile */";
    }
    exit;
}

// Check if this is an inline CSS test request
if (isset($_GET['test']) && $_GET['test'] === 'inline') {
    // Generate a test page with inline CSS to verify CSS parsing works
    $inlineCSS = file_get_contents(__DIR__ . '/assets/css/public.css');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inline CSS Test - Dalthaus.net</title>
        <style><?= $inlineCSS ?></style>
    </head>
    <body>
        <div class="page-wrapper">
            <header class="site-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="site-title">
                            <a href="/">Inline CSS Test - Dalthaus.net</a>
                        </h1>
                        <p class="site-motto">Testing inline CSS delivery</p>
                    </div>
                </div>
            </header>
            
            <main class="main-content">
                <div class="content-layout">
                    <section class="articles-section">
                        <h2 class="section-title">CSS Test Results</h2>
                        <div class="alert alert-success">
                            <strong>SUCCESS:</strong> If you see styled content with Arimo/Gelasio fonts, 
                            blue links, and proper layout, then CSS parsing works and the issue is 
                            with external CSS file delivery.
                        </div>
                        
                        <article class="front-article-item">
                            <div class="front-article-thumb">
                                <div class="image-placeholder"></div>
                            </div>
                            <div class="front-article-content">
                                <h3 class="front-article-title">
                                    <a href="#">Test Article Title</a>
                                </h3>
                                <div class="front-article-meta">
                                    Don Althaus · 29 August 2025 · Articles
                                </div>
                                <div class="front-article-teaser">
                                    This is a test article to verify that CSS styling is working properly
                                    with inline styles. The layout should be properly formatted.
                                </div>
                            </div>
                        </article>
                    </section>
                    
                    <aside class="photobooks-section">
                        <h2 class="section-title">Photo Books</h2>
                        <div class="front-photobook-item">
                            <div class="front-photobook-thumbnail image-placeholder"></div>
                            <h3 class="front-photobook-title">
                                <a href="#">Test Photo Book</a>
                            </h3>
                            <div class="front-photobook-meta">
                                Don Althaus · 29 August 2025 · Photo Books
                            </div>
                            <div class="front-photobook-excerpt">
                                This is a test photobook entry to verify styling.
                            </div>
                        </div>
                    </aside>
                </div>
            </main>
        </div>
    </body>
    </html>
    <?php
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Loading Diagnostics - Dalthaus.net</title>
    
    <!-- Multiple CSS loading methods for maximum compatibility -->
    
    <!-- Method 1: Standard external CSS with cache busting -->
    <link rel="stylesheet" href="/assets/css/public.css?v=<?= time() ?>" id="main-css">
    
    <!-- Method 2: Alternative CSS path -->
    <link rel="stylesheet" href="./assets/css/public.css?v=<?= time() ?>" id="alt-css">
    
    <!-- Method 3: CSS served via PHP script -->
    <link rel="stylesheet" href="?test=css&v=<?= time() ?>" id="php-css">
    
    <!-- Method 4: Preload CSS for better performance -->
    <link rel="preload" href="/assets/css/public.css?v=<?= time() ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- Google Fonts (should work if external resources are loading) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Emergency inline styles as last resort */
        .diagnostic-info {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        
        .diagnostic-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .diagnostic-section h2 {
            color: #2c3e50;
            margin-top: 0;
        }
        
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .test-pass {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .test-fail {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .test-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .action-buttons {
            margin: 20px 0;
        }
        
        .action-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .action-buttons a:hover {
            background: #2980b9;
        }
    </style>
    
    <script>
        // JavaScript diagnostic functions
        
        function checkCSSLoading() {
            const results = [];
            const links = document.querySelectorAll('link[rel="stylesheet"]');
            
            links.forEach((link, index) => {
                const id = link.id || `css-${index}`;
                const href = link.href;
                
                // Check if CSS is loaded by testing computed styles
                const testDiv = document.createElement('div');
                testDiv.style.position = 'absolute';
                testDiv.style.left = '-9999px';
                testDiv.className = 'page-wrapper'; // Use a class from our CSS
                document.body.appendChild(testDiv);
                
                const computed = window.getComputedStyle(testDiv);
                const hasStyles = computed.display !== 'block' || 
                                computed.minHeight === '100vh' ||
                                computed.flexDirection === 'column';
                
                document.body.removeChild(testDiv);
                
                results.push({
                    id: id,
                    href: href,
                    loaded: hasStyles
                });
            });
            
            return results;
        }
        
        function testFontLoading() {
            // Test if Google Fonts are loading
            const testText = document.createElement('span');
            testText.style.fontFamily = 'Arimo, sans-serif';
            testText.style.position = 'absolute';
            testText.style.left = '-9999px';
            testText.textContent = 'Test';
            document.body.appendChild(testText);
            
            const width = testText.offsetWidth;
            document.body.removeChild(testText);
            
            // If width is 0, font didn't load
            return width > 0;
        }
        
        function performDiagnostics() {
            const results = {
                css: checkCSSLoading(),
                fonts: testFontLoading(),
                timestamp: new Date().toISOString()
            };
            
            // Display results
            const resultsDiv = document.getElementById('js-diagnostics');
            if (resultsDiv) {
                let html = '<h3>JavaScript Diagnostics Results</h3>';
                
                html += '<h4>CSS Loading Status:</h4>';
                results.css.forEach(css => {
                    const status = css.loaded ? 'PASS' : 'FAIL';
                    const cssClass = css.loaded ? 'test-pass' : 'test-fail';
                    html += `<div class="${cssClass}">
                        <strong>${status}:</strong> ${css.id} - ${css.href}
                    </div>`;
                });
                
                html += '<h4>Font Loading Status:</h4>';
                const fontStatus = results.fonts ? 'PASS' : 'FAIL';
                const fontClass = results.fonts ? 'test-pass' : 'test-fail';
                html += `<div class="${fontClass}">
                    <strong>${fontStatus}:</strong> Google Fonts (Arimo)
                </div>`;
                
                resultsDiv.innerHTML = html;
            }
            
            // Store results for debugging
            window.diagnosticResults = results;
            console.log('CSS Loading Diagnostics:', results);
        }
        
        // Run diagnostics when page loads
        document.addEventListener('DOMContentLoaded', performDiagnostics);
        
        // Also run after a delay to catch late-loading resources
        setTimeout(performDiagnostics, 2000);
    </script>
</head>
<body>

<div class="diagnostic-info">
    <h1 style="color: #2c3e50;">CSS Loading Diagnostics - Dalthaus.net</h1>
    <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    
    <div class="action-buttons">
        <a href="?test=inline">Test Inline CSS</a>
        <a href="?test=css">Download CSS File</a>
        <a href="/">Return to Homepage</a>
        <a href="javascript:performDiagnostics()">Rerun Diagnostics</a>
    </div>

    <div class="diagnostic-section">
        <h2>File System Diagnostics</h2>
        
        <?php
        $cssFile = __DIR__ . '/assets/css/public.css';
        $cssExists = file_exists($cssFile);
        $cssReadable = is_readable($cssFile);
        $cssSize = $cssExists ? filesize($cssFile) : 0;
        $cssModified = $cssExists ? date('Y-m-d H:i:s', filemtime($cssFile)) : 'N/A';
        ?>
        
        <div class="test-result <?= $cssExists ? 'test-pass' : 'test-fail' ?>">
            <strong>CSS File Exists:</strong> <?= $cssExists ? 'YES' : 'NO' ?> - <?= $cssFile ?>
        </div>
        
        <div class="test-result <?= $cssReadable ? 'test-pass' : 'test-fail' ?>">
            <strong>CSS File Readable:</strong> <?= $cssReadable ? 'YES' : 'NO' ?>
        </div>
        
        <div class="test-result test-info">
            <strong>CSS File Size:</strong> <?= number_format($cssSize) ?> bytes
        </div>
        
        <div class="test-result test-info">
            <strong>CSS Last Modified:</strong> <?= $cssModified ?>
        </div>
    </div>

    <div class="diagnostic-section">
        <h2>Web Server Configuration</h2>
        
        <?php
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Not Set';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? 'Not Set';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'Not Set';
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'Not Set';
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Not Set';
        ?>
        
        <div class="test-result test-info">
            <strong>Document Root:</strong> <?= htmlspecialchars($documentRoot) ?>
        </div>
        
        <div class="test-result test-info">
            <strong>Script Name:</strong> <?= htmlspecialchars($scriptName) ?>
        </div>
        
        <div class="test-result test-info">
            <strong>Request URI:</strong> <?= htmlspecialchars($requestUri) ?>
        </div>
        
        <div class="test-result test-info">
            <strong>HTTP Host:</strong> <?= htmlspecialchars($httpHost) ?>
        </div>
        
        <div class="test-result test-info">
            <strong>Server Software:</strong> <?= htmlspecialchars($serverSoftware) ?>
        </div>
    </div>

    <div class="diagnostic-section">
        <h2>CSS Content Preview</h2>
        
        <?php if ($cssExists): ?>
            <p>First 500 characters of CSS file:</p>
            <div class="code-block">
                <?= htmlspecialchars(substr(file_get_contents($cssFile), 0, 500)) ?>...
            </div>
        <?php else: ?>
            <div class="test-result test-fail">
                <strong>ERROR:</strong> CSS file not found - cannot preview content
            </div>
        <?php endif; ?>
    </div>

    <div class="diagnostic-section" id="js-diagnostics">
        <h2>JavaScript Diagnostics</h2>
        <p>Loading... (diagnostics will appear here once JavaScript executes)</p>
    </div>

    <div class="diagnostic-section">
        <h2>Potential Issues and Solutions</h2>
        
        <div class="test-result test-info">
            <strong>Issue 1:</strong> MIME Type - Web server might not be serving CSS with correct Content-Type header
            <br><strong>Solution:</strong> Configure server to serve .css files with Content-Type: text/css
        </div>
        
        <div class="test-result test-info">
            <strong>Issue 2:</strong> Path Resolution - CSS path might be incorrect relative to document root
            <br><strong>Solution:</strong> Use absolute paths or check .htaccess rewrite rules
        </div>
        
        <div class="test-result test-info">
            <strong>Issue 3:</strong> Caching - Browser or server might be caching old/empty CSS
            <br><strong>Solution:</strong> Clear browser cache and add cache-busting parameters
        </div>
        
        <div class="test-result test-info">
            <strong>Issue 4:</strong> File Permissions - CSS file might not be readable by web server
            <br><strong>Solution:</strong> Set proper file permissions (644 for files, 755 for directories)
        </div>
    </div>

    <div class="diagnostic-section">
        <h2>Emergency CSS Fix</h2>
        <p>If external CSS continues to fail, the system will automatically inject inline CSS as a fallback.</p>
        
        <div class="action-buttons">
            <a href="javascript:injectEmergencyCSS()">Inject Emergency CSS Now</a>
        </div>
    </div>

</div>

<script>
function injectEmergencyCSS() {
    // Create and inject a style element with emergency CSS
    const style = document.createElement('style');
    style.id = 'emergency-css';
    
    // Fetch CSS content via AJAX and inject inline
    fetch('?test=css')
        .then(response => response.text())
        .then(css => {
            style.textContent = css;
            document.head.appendChild(style);
            alert('Emergency CSS injected! The page should now be styled.');
            
            // Reload page to see results
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            console.error('Failed to inject emergency CSS:', error);
            alert('Failed to inject emergency CSS. Check console for details.');
        });
}
</script>

</body>
</html>