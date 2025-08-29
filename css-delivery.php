<?php
/**
 * CSS Delivery Script with Proper Headers
 * 
 * This script ensures CSS is served with the correct Content-Type header
 * and handles various fallback scenarios for CSS delivery.
 */

// Set proper headers for CSS delivery
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Path to the CSS file
$cssFile = __DIR__ . '/assets/css/public.css';

// Check if CSS file exists and is readable
if (file_exists($cssFile) && is_readable($cssFile)) {
    // Output the CSS content
    echo file_get_contents($cssFile);
} else {
    // Fallback: output error message as CSS comment
    echo "/* ERROR: CSS file not found or not readable at: $cssFile */\n";
    echo "/* Please check file permissions and path */\n";
    
    // Output basic fallback CSS
    echo "
    /* Emergency Fallback CSS */
    body {
        font-family: Arial, sans-serif;
        font-size: 16px;
        line-height: 1.6;
        color: #333;
        background: #fff;
        margin: 0;
        padding: 20px;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: Arial, sans-serif;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    
    a {
        color: #0066cc;
        text-decoration: none;
    }
    
    a:hover {
        text-decoration: underline;
    }
    
    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .content-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .section-title {
        font-size: 1.5rem;
        color: #333;
        border-bottom: 2px solid #0066cc;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .content-layout {
            grid-template-columns: 1fr;
        }
    }
    ";
}

exit;
?>