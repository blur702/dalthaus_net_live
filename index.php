<?php

declare(strict_types=1);

/**
 * Front Controller
 * 
 * Entry point for all web requests. Implements front controller pattern
 * to route requests to appropriate controllers and actions.
 * 
 * @package CMS
 * @author  Kevin
 * @version 1.0.0
 */

// TEMPORARY: Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Custom exception handler - only show database error for actual connection failures
set_exception_handler(function ($exception) {
    $config = require __DIR__ . '/config/config.php';
    
    // Log all exceptions
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
    
    // Only show database error page for actual connection failures during initialization
    // BUT only in production - allow development to continue
    if ($exception instanceof PDOException && 
        !$config['app']['debug'] && // Only show database error page in production
        (strpos($exception->getMessage(), 'Connection refused') !== false ||
         strpos($exception->getMessage(), 'Access denied') !== false ||
         strpos($exception->getMessage(), 'Unknown database') !== false ||
         strpos($exception->getMessage(), 'SQLSTATE[HY000]') !== false)) {
        
        // Check if we're actually trying to initialize the database
        $trace = $exception->getTraceAsString();
        if (strpos($trace, 'Database::getInstance') !== false || 
            strpos($trace, 'Database::__construct') !== false) {
            
            http_response_code(503); // Service Unavailable
            echo "<!DOCTYPE html>\n<html lang='en'>\n<head>\n    <title>Database Connection Error</title>\n    <meta charset='utf-8'>\n    <meta name='viewport' content='width=device-width, initial-scale=1'>\n    <script src='https://cdn.tailwindcss.com'></script>\n</head>\n<body class='bg-gray-100 text-gray-800 font-sans'>\n    <div class='min-h-screen flex items-center justify-center'>\n        <div class='max-w-2xl w-full bg-white shadow-lg rounded-lg p-8'>\n            <h1 class='text-3xl font-bold text-red-600 mb-4'>Database Connection Error</h1>\n            <p class='text-lg mb-4'>The application could not connect to the database. This is usually due to incorrect configuration.</p>\n            <div class='bg-gray-50 p-6 rounded-lg'>\n                <h2 class='text-xl font-semibold mb-3'>How to Fix This:</h2>\n                <p class='mb-4'>Please ensure your database server is running and that the credentials in <strong>config/config.php</strong> are correct. You may need to create the database and user.</p>\n                <ol class='list-decimal list-inside space-y-4'>\n                    <li>\n                        <strong>Create the database in MySQL:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>CREATE DATABASE IF NOT EXISTS dalthaus_maincms;</code></pre>\n                    </li>\n                    <li>\n                        <strong>Create the database user:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>CREATE USER IF NOT EXISTS 'dalthaus_maincms'@'localhost' IDENTIFIED BY 'f4!,Wpds=w6*=~+1';</code></pre>\n                    </li>\n                    <li>\n                        <strong>Grant privileges to the user:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>GRANT ALL PRIVILEGES ON dalthaus_maincms.* TO 'dalthaus_maincms'@'localhost';</code></pre>\n                    </li>\n                    <li>\n                        <strong>Import the database schema:</strong>\n                        <p class='mt-1'>Run this command from your project's root directory in your terminal:</p>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>mysql -u dalthaus_maincms -p dalthaus_maincms < database.sql</code></pre>\n                        <small class='text-gray-600'>You will be prompted for the password: <strong>f4!,Wpds=w6*=~+1</strong></small>\n                    </li>\n                </ol>\n            </div>\n            <div class='mt-6 text-center'>\n                <p class='text-sm text-gray-500'>Once the database is set up, please refresh this page.</p>\n            </div>\n        </div>\n    </div>\n</body>\n</html>";
            exit;
        }
    }
    
    // For all other exceptions (including non-connection PDO exceptions)
    if ($config['app']['debug']) {
        echo "<h1>Error</h1><p>" . htmlspecialchars($exception->getMessage()) . "</p><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        // For admin pages, redirect to login on errors
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') === 0) {
            header("Location: /admin/login");
            exit;
        }
        
        // Set 500 status code
        http_response_code(500);
        
        // Try to render the proper 500 error page
        try {
            require_once __DIR__ . '/src/Utils/View.php';
            $view = new CMS\Utils\View($config['views']);
            
            // Get site settings if possible
            $settings = [
                'site_name' => $config['app']['name'] ?? 'Site',
                'site_description' => '',
                'site_url' => $config['app']['base_url'] ?? '',
                'admin_email' => ''
            ];
            
            $view->layout('default');
            $view->render('errors/500', [
                'page_title' => 'Internal Server Error',
                'settings' => $settings,
                'current_user' => null
            ]);
        } catch (Exception $e) {
            // Fallback to simple error page if view rendering fails
            echo "<!DOCTYPE html><html><head><title>Internal Server Error</title></head><body><h1>500 - Internal Server Error</h1></body></html>";
        }
    }
});

// Start session with secure settings
$config = require __DIR__ . '/config/config.php';

session_set_cookie_params([
    'lifetime' => $config['security']['session_lifetime'],
    'path' => '/',
    'domain' => '',
    'secure' => $config['security']['secure_cookies'],
    'httponly' => $config['security']['cookie_httponly'],
    'samesite' => $config['security']['cookie_samesite']
]);

session_name($config['security']['session_name']);

// Initialize database connection for session handler
try {
    $db = CMS\Utils\Database::getInstance($config['database']);

    // Check if we need to use database sessions (when file sessions fail)
    $testSessionId = session_create_id();
    $sessionData = "test_data_" . time();
    $testResult = @file_put_contents(session_save_path() . "/sess_" . $testSessionId, $sessionData);

    if ($testResult === false || !@unlink(session_save_path() . "/sess_" . $testSessionId)) {
        // File sessions not working, use database sessions
        require_once __DIR__ . '/config/session_fix.php';
        $sessionHandler = new DatabaseSessionHandler($db->getConnection());
        session_set_save_handler($sessionHandler, true);

        // Create sessions table if it doesn't exist
        $db->query("CREATE TABLE IF NOT EXISTS user_sessions (
            session_id VARCHAR(128) PRIMARY KEY,
            session_data TEXT,
            expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_expires (expires)
        )");

        error_log("Using database sessions due to file session storage issues");
    }
} catch (Exception $e) {
    error_log("Session handler setup error: " . $e->getMessage());
    // Continue with default file sessions
}

// Check if this is a public content page that should be cached
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_cacheable = (
    strpos($request_uri, '/admin') === false &&
    strpos($request_uri, '/login') === false &&
    strpos($request_uri, '/logout') === false &&
    (strpos($request_uri, '/article/') !== false ||
     strpos($request_uri, '/photobook/') !== false ||
     strpos($request_uri, '/page/') !== false ||
     strpos($request_uri, '/assets/') !== false)
);

// Start session but control cache headers for cacheable pages
if ($is_cacheable) {
    // For cacheable content, start session read-only without no-cache headers
    ini_set('session.cache_limiter', '');
    session_start();
    // Set cache headers for Cloudflare
    header('Cache-Control: public, max-age=7200'); // 2 hours
} else {
    // For non-cacheable pages (admin, forms, etc), use normal session with no-cache
    session_start();
}

// Set timezone
date_default_timezone_set($config['app']['timezone']);

// Error handling
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set error log path
if (isset($config['errors']['log_errors']) && $config['errors']['log_errors']) {
    ini_set('log_errors', '1');
    ini_set('error_log', $config['errors']['error_log_path']);
}

try {
    // Initialize router
    $router = new CMS\Utils\Router($config['routing']);
    
    // Load routes from the dedicated configuration file
    $routes = require __DIR__ . '/config/routes.php';
    $routes($router);
    
    // Dispatch the request
    $router->dispatch();
    
} catch (Exception $e) {
    // The main exception handler will catch this
    throw $e;
}