<?php
/**
 * Main entry point - routes to homepage
 */

// Check if we're at the root URL, then show homepage
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove trailing slash except for root
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Route to homepage for root path
if ($path === '/' || $path === '') {
    require_once 'homepage.php';
    exit;
}

// For other paths, check if file exists and include it
// This is a simple routing system

// Clean the path and check for various file extensions
$clean_path = ltrim($path, '/');

// Check for direct file matches
$possible_files = [
    $clean_path . '.php',
    $clean_path . '.html',
    $clean_path . '/index.php',
    $clean_path . '/index.html'
];

foreach ($possible_files as $file) {
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// Handle special routes
switch ($path) {
    case '/articles':
        if (file_exists('articles.html')) {
            require_once 'articles.html';
            exit;
        }
        break;
    
    case '/photobooks':
        if (file_exists('photobooks.html')) {
            require_once 'photobooks.html';
            exit;
        }
        break;
    
    case '/admin':
    case '/admin/':
        if (file_exists('admin/index.php')) {
            require_once 'admin/index.php';
            exit;
        } elseif (file_exists('admin/login.php')) {
            require_once 'admin/login.php';
            exit;
        }
        break;
}

// If nothing matches, show 404
if (file_exists('404.html')) {
    http_response_code(404);
    require_once '404.html';
} elseif (file_exists('404-content.html')) {
    http_response_code(404);
    require_once '404-content.html';
} else {
    http_response_code(404);
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Page Not Found - Dalthaus Photography</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #2c3e50; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Page Not Found</h1>
    <p>The page you are looking for could not be found.</p>
    <a href="/">← Return to Homepage</a>
</body>
</html>';
}
?>