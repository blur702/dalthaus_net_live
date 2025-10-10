<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/vendor/autoload.php';

set_exception_handler(function ($exception) {
    $config = require __DIR__ . '/config/config.php';
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
    if ($exception instanceof PDOException && !$config['app']['debug'] && (strpos($exception->getMessage(), 'Connection refused') !== false || strpos($exception->getMessage(), 'Access denied') !== false || strpos($exception->getMessage(), 'Unknown database') !== false || strpos($exception->getMessage(), 'SQLSTATE[HY000]') !== false)) {
        $trace = $exception->getTraceAsString();
        if (strpos($trace, 'Database::getInstance') !== false || strpos($trace, 'Database::__construct') !== false) {
            http_response_code(503);
            echo "<!DOCTYPE html>... [Service Unavailable HTML] ...";
            exit;
        }
    }
    if ($config['app']['debug']) {
        echo "<h1>Error</h1><p>" . htmlspecialchars($exception->getMessage()) . "</p><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') === 0) {
            header("Location: /admin/login");
            exit;
        }
        http_response_code(500);
        try {
            require_once __DIR__ . '/src/Utils/View.php';
            $view = new CMS\Utils\View($config['views']);
            $settings = ['site_name' => $config['app']['name'] ?? 'Site', 'site_description' => '', 'site_url' => $config['app']['base_url'] ?? '', 'admin_email' => ''];
            $view->layout('default');
            $view->render('errors/500', ['page_title' => 'Internal Server Error', 'settings' => $settings, 'current_user' => null]);
        } catch (Exception $e) {
            echo "<!DOCTYPE html><html><head><title>Internal Server Error</title></head><body><h1>500 - Internal Server Error</h1></body></html>";
        }
    }
});

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

try {
    $db = CMS\Utils\Database::getInstance($config['database']);
    $testSessionId = session_create_id();
    $sessionData = "test_data_" . time();
    $testResult = @file_put_contents(session_save_path() . "/sess_" . $testSessionId, $sessionData);
    if ($testResult === false || !@unlink(session_save_path() . "/sess_" . $testSessionId)) {
        require_once __DIR__ . '/config/session_fix.php';
        $sessionHandler = new DatabaseSessionHandler($db->getConnection());
        session_set_save_handler($sessionHandler, true);
        $db->query("CREATE TABLE IF NOT EXISTS user_sessions (session_id VARCHAR(128) PRIMARY KEY, session_data TEXT, expires DATETIME, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_expires (expires))");
        error_log("Using database sessions due to file session storage issues");
    }
} catch (Exception $e) {
    error_log("Session handler setup error: " . $e->getMessage());
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_cacheable = (strpos($request_uri, '/admin') === false && strpos($request_uri, '/login') === false && strpos($request_uri, '/logout') === false && (strpos($request_uri, '/article/') !== false || strpos($request_uri, '/photobook/') !== false || strpos($request_uri, '/page/') !== false || strpos($request_uri, '/assets/') !== false));

if ($is_cacheable) {
    ini_set('session.cache_limiter', '');
    session_start();
    header('Cache-Control: public, max-age=7200');
} else {
    session_start();
}

date_default_timezone_set($config['app']['timezone']);

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if (isset($config['errors']['log_errors']) && $config['errors']['log_errors']) {
    ini_set('log_errors', '1');
    ini_set('error_log', $config['errors']['error_log_path']);
}

try {
    $db = CMS\Utils\Database::getInstance($config['database']);
    $router = new CMS\Utils\Router($config, $db);
    $routes = require __DIR__ . '/config/routes.php';
    $routes($router);
    $router->dispatch();
} catch (Exception $e) {
    throw $e;
}
