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

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Custom exception handler for database connection errors
set_exception_handler(function ($exception) {
    if ($exception instanceof PDOException) {
        http_response_code(503); // Service Unavailable
        echo "<!DOCTYPE html>\n<html lang='en'>\n<head>\n    <title>Database Connection Error</title>\n    <meta charset='utf-8'>\n    <meta name='viewport' content='width=device-width, initial-scale=1'>\n    <script src='https://cdn.tailwindcss.com'></script>\n</head>\n<body class='bg-gray-100 text-gray-800 font-sans'>\n    <div class='min-h-screen flex items-center justify-center'>\n        <div class='max-w-2xl w-full bg-white shadow-lg rounded-lg p-8'>\n            <h1 class='text-3xl font-bold text-red-600 mb-4'>Database Connection Error</h1>\n            <p class='text-lg mb-4'>The application could not connect to the database. This is usually due to incorrect configuration.</p>\n            <div class='bg-gray-50 p-6 rounded-lg'>\n                <h2 class='text-xl font-semibold mb-3'>How to Fix This:</h2>\n                <p class='mb-4'>Please ensure your database server is running and that the credentials in <strong>config/config.php</strong> are correct. You may need to create the database and user.</p>\n                <ol class='list-decimal list-inside space-y-4'>\n                    <li>\n                        <strong>Create the database in MySQL:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>CREATE DATABASE IF NOT EXISTS cms_db;</code></pre>\n                    </li>\n                    <li>\n                        <strong>Create the database user:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>CREATE USER IF NOT EXISTS 'cms_user'@'localhost' IDENTIFIED BY 'cms_password';</code></pre>\n                    </li>\n                    <li>\n                        <strong>Grant privileges to the user:</strong>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>GRANT ALL PRIVILEGES ON cms_db.* TO 'cms_user'@'localhost';</code></pre>\n                    </li>\n                    <li>\n                        <strong>Import the database schema:</strong>\n                        <p class='mt-1'>Run this command from your project's root directory in your terminal:</p>\n                        <pre class='bg-gray-200 text-sm p-3 rounded-md mt-2'><code>mysql -u cms_user -p cms_db < database.sql</code></pre>\n                        <small class='text-gray-600'>You will be prompted for the password: <strong>cms_password</strong></small>\n                    </li>\n                </ol>\n            </div>\n            <div class='mt-6 text-center'>\n                <p class='text-sm text-gray-500'>Once the database is set up, please refresh this page.</p>\n            </div>\n        </div>\n    </div>\n</body>\n</html>";
        exit;
    }

    // Fallback to the original exception handler for other errors
    $config = require __DIR__ . '/config/config.php';
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
    if ($config['app']['debug']) {
        echo "<h1>Error</h1><p>" . htmlspecialchars($exception->getMessage()) . "</p><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>Internal Server Error</title></head><body><h1>500 - Internal Server Error</h1></body></html>";
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
session_start();

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