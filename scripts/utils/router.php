<?php
/**
 * PHP Built-in Server Router
 * 
 * This file handles routing for the PHP built-in development server.
 * It simulates Apache mod_rewrite behavior.
 */

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly if they exist (but not PHP files)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    // Don't serve PHP files directly
    if ($ext === 'php') {
        // Fall through to index.php
    } else if (in_array($ext, ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'webp'])) {
        // Let the built-in server handle static files
        return false;
    }
}

// Everything else goes through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';