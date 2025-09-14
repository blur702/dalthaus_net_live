<?php
/**
 * Enhanced Agent with Config Fix Capability
 * Adds ability to directly fix configuration issues
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

// Authentication
$providedKey = $_POST['key'] ?? $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$validKey = 'dalthaus_agent_key_2025';

if ($providedKey !== $validKey) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Add this new action to the existing agent
if ($action === 'fix_config') {
    $configFile = __DIR__ . '/config/config.php';
    $backupFile = $configFile . '.backup.' . date('YmdHis');
    
    // Backup current config
    copy($configFile, $backupFile);
    
    // Write correct configuration
    $correctConfig = '<?php

declare(strict_types=1);

return [
    "database" => [
        "host" => "localhost",
        "dbname" => "dalthaus_maincms",
        "username" => "dalthaus_maincms",
        "password" => "f4!,Wpds=w6*=~+1",
        "charset" => "utf8mb4",
        "options" => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],
    "app" => [
        "name" => "CMS Application",
        "version" => "1.0.0",
        "timezone" => "America/New_York",
        "debug" => false,
        "base_url" => "https://dalthaus.net",
        "upload_path" => __DIR__ . "/../uploads/",
        "max_upload_size" => 10485760,
        "allowed_image_types" => ["jpg", "jpeg", "png", "gif", "webp"],
        "items_per_page" => 10
    ],
    "security" => [
        "session_name" => "cms_session",
        "session_lifetime" => 3600,
        "csrf_token_name" => "_token",
        "password_min_length" => 8,
        "login_max_attempts" => 5,
        "login_lockout_time" => 900,
        "secure_cookies" => true,
        "cookie_httponly" => true,
        "cookie_samesite" => "Strict"
    ],
    "views" => [
        "cache_enabled" => false,
        "cache_path" => __DIR__ . "/../cache/views/",
        "default_layout" => "default",
        "admin_layout" => "admin"
    ],
    "routing" => [
        "default_controller" => "Home",
        "default_action" => "index",
        "admin_prefix" => "admin",
        "url_suffix" => "",
        "case_sensitive" => false
    ],
    "tinymce" => [
        "api_key" => "",
        "plugins" => [
            "advlist", "autolink", "lists", "link", "image", "charmap",
            "preview", "anchor", "searchreplace", "visualblocks", "code",
            "fullscreen", "insertdatetime", "media", "table", "help",
            "wordcount", "pagebreak"
        ],
        "toolbar" => "undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | pagebreak | help",
        "height" => 400,
        "content_css" => "/assets/css/editor.css"
    ],
    "email" => [
        "smtp_host" => "localhost",
        "smtp_port" => 587,
        "smtp_username" => "",
        "smtp_password" => "",
        "smtp_encryption" => "tls",
        "from_email" => "noreply@dalthaus.net",
        "from_name" => "CMS Application"
    ],
    "cache" => [
        "enabled" => false,
        "default_ttl" => 3600,
        "path" => __DIR__ . "/../cache/",
        "file_extension" => ".cache"
    ],
    "errors" => [
        "display_errors" => false,
        "log_errors" => true,
        "error_log_path" => __DIR__ . "/../logs/error.log",
        "exception_handler" => true
    ]
];';
    
    file_put_contents($configFile, $correctConfig);
    
    // Test the connection
    try {
        $config = require $configFile;
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
        $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
        $testResult = 'Database connection successful';
        $dbSuccess = true;
    } catch (PDOException $e) {
        $testResult = 'Database connection failed: ' . $e->getMessage();
        $dbSuccess = false;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Config fixed',
        'backup_created' => basename($backupFile),
        'database_test' => $testResult,
        'database_connected' => $dbSuccess
    ]);
    exit;
}

if ($action === 'check_config') {
    $configFile = __DIR__ . '/config/config.php';
    $config = require $configFile;
    
    // Check what's actually in the config
    $dbConfig = $config['database'] ?? [];
    
    // Try to connect
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options'] ?? []);
        $connected = true;
        $error = null;
    } catch (PDOException $e) {
        $connected = false;
        $error = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'config_file' => 'config/config.php',
        'database_config' => [
            'host' => $dbConfig['host'] ?? 'NOT SET',
            'dbname' => $dbConfig['dbname'] ?? 'NOT SET',
            'username' => $dbConfig['username'] ?? 'NOT SET',
            'password_set' => !empty($dbConfig['password'])
        ],
        'connected' => $connected,
        'error' => $error
    ]);
    exit;
}

// Include all existing agent actions here...
echo json_encode([
    'success' => false,
    'message' => 'Action not implemented in this enhancement. Use fix_config or check_config.'
]);
?>