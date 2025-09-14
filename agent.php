<?php
/**
 * Enhanced Deployment Agent with Extended Operations
 * Provides comprehensive server management capabilities
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

switch ($action) {
    case 'git_pull':
        $output = [];
        $returnCode = 0;
        exec('cd ' . escapeshellarg(__DIR__) . ' && git pull origin main 2>&1', $output, $returnCode);
        echo json_encode([
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Git pull completed' : 'Git pull failed',
            'output' => $output,
            'return_code' => $returnCode
        ]);
        break;

    case 'git_status':
        $output = [];
        exec('cd ' . escapeshellarg(__DIR__) . ' && git status --short 2>&1', $output);
        echo json_encode(['success' => true, 'output' => $output]);
        break;

    case 'git_log':
        $output = [];
        exec('cd ' . escapeshellarg(__DIR__) . ' && git log --oneline -10 2>&1', $output);
        echo json_encode(['success' => true, 'output' => $output]);
        break;

    case 'composer_install':
        $output = [];
        $returnCode = 0;
        exec('cd ' . escapeshellarg(__DIR__) . ' && composer install --no-dev --optimize-autoloader 2>&1', $output, $returnCode);
        echo json_encode([
            'success' => $returnCode === 0,
            'output' => $output,
            'return_code' => $returnCode
        ]);
        break;

    case 'clear_cache':
        $cacheDir = __DIR__ . '/cache';
        $output = [];
        if (is_dir($cacheDir)) {
            exec('find ' . escapeshellarg($cacheDir) . ' -type f -name "*.cache" -delete 2>&1', $output);
            $output[] = 'Cache cleared';
        }
        // Clear PHP opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $output[] = 'OPcache cleared';
        }
        echo json_encode(['success' => true, 'output' => $output]);
        break;

    case 'check_permissions':
        $dirs = ['uploads', 'logs', 'cache'];
        $results = [];
        foreach ($dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $results[$dir] = [
                'exists' => file_exists($path),
                'writable' => is_writable($path)
            ];
        }
        echo json_encode(['success' => true, 'permissions' => $results]);
        break;

    case 'database_backup':
        require_once __DIR__ . '/config/config.php';
        $config = require __DIR__ . '/config/config.php';
        $db = $config['database'];
        $backupFile = __DIR__ . '/backups/backup_' . date('Y-m-d_His') . '.sql';
        
        // Create backups directory if it doesn't exist
        if (!is_dir(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0755, true);
        }
        
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg($db['host']),
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['dbname']),
            escapeshellarg($backupFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        echo json_encode([
            'success' => $returnCode === 0 && file_exists($backupFile),
            'backup_file' => basename($backupFile),
            'size' => file_exists($backupFile) ? filesize($backupFile) : 0
        ]);
        break;

    case 'system_info':
        $info = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => __DIR__,
            'disk_free' => disk_free_space(__DIR__),
            'disk_total' => disk_total_space(__DIR__),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'loaded_extensions' => get_loaded_extensions()
        ];
        echo json_encode(['success' => true, 'info' => $info]);
        break;

    case 'error_log':
        $logFile = __DIR__ . '/logs/error.log';
        $lines = $_GET['lines'] ?? 50;
        $output = [];
        
        if (file_exists($logFile)) {
            exec('tail -n ' . intval($lines) . ' ' . escapeshellarg($logFile), $output);
        } else {
            $output[] = 'No error log found';
        }
        echo json_encode(['success' => true, 'log' => $output]);
        break;

    case 'run_command':
        // Only allow specific safe commands
        $command = $_POST['command'] ?? '';
        $allowedCommands = [
            'composer dump-autoload',
            'php artisan cache:clear',
            'npm run build'
        ];
        
        if (in_array($command, $allowedCommands)) {
            $output = [];
            $returnCode = 0;
            exec('cd ' . escapeshellarg(__DIR__) . ' && ' . $command . ' 2>&1', $output, $returnCode);
            echo json_encode([
                'success' => $returnCode === 0,
                'output' => $output,
                'return_code' => $returnCode
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Command not allowed'
            ]);
        }
        break;

    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'Agent is operational',
            'php_version' => PHP_VERSION,
            'directory' => __DIR__,
            'timestamp' => date('c')
        ]);
        break;

    case 'fix_config':
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
        break;
        
    case 'check_config':
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
        break;

    case 'help':
        echo json_encode([
            'success' => true,
            'available_actions' => [
                'git_pull' => 'Pull latest changes from GitHub',
                'git_status' => 'Show git status',
                'git_log' => 'Show last 10 commits',
                'composer_install' => 'Run composer install',
                'clear_cache' => 'Clear application cache',
                'check_permissions' => 'Check directory permissions',
                'database_backup' => 'Create database backup',
                'system_info' => 'Get system information',
                'error_log' => 'View last N lines of error log (?lines=50)',
                'run_command' => 'Run allowed shell commands',
                'fix_config' => 'Force fix database configuration',
                'check_config' => 'Check current database configuration',
                'test' => 'Test agent connectivity',
                'help' => 'Show this help message'
            ]
        ]);
        break;

    
    case 'clear_all_sessions':
        // Clear ALL session files on the server
        $sessionPath = session_save_path() ?: '/opt/alt/php84/var/lib/php/session';
        $files = glob($sessionPath . '/sess_*');
        $count = 0;
        foreach ($files as $file) {
            if (@unlink($file)) $count++;
        }
        echo json_encode([
            'success' => true,
            'message' => "Cleared $count session files",
            'session_path' => $sessionPath
        ]);
        exit;
        
    case 'test_auth':
        // Test authentication system
        session_start();
        require_once __DIR__ . '/vendor/autoload.php';
        $config = require __DIR__ . '/config/config.php';
        
        try {
            $db = CMS\Utils\Database::getInstance($config['database']);
            $auth = new CMS\Utils\Auth($db, $config['security']);
            
            $result = [
                'session_id' => session_id(),
                'session_data' => $_SESSION,
                'auth_check' => $auth->check(),
                'user_id' => $_SESSION['user_id'] ?? null,
                'logged_in' => $_SESSION['logged_in'] ?? false,
                'is_admin' => $_SESSION['is_admin'] ?? false
            ];
            
            echo json_encode(['success' => true, 'auth_status' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use action=help to see available actions'
        ]);
}
?>