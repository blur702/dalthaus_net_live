<?php
/**
 * Remote Debugging Agent for Dalthaus CMS
 * Provides secure remote access for troubleshooting
 * DELETE THIS FILE AFTER FIXING THE ISSUES!
 */

// Security token (change this!)
$SECRET_TOKEN = 'debug-' . date('Ymd');

// Check token
$provided_token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($provided_token !== $SECRET_TOKEN) {
    http_response_code(403);
    die('Access Denied. Token: debug-' . date('Ymd'));
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'info';
$response = ['status' => 'ok', 'action' => $action];

header('Content-Type: application/json');

try {
    switch($action) {
        case 'info':
            $response['data'] = [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
                'current_dir' => __DIR__,
                'user' => get_current_user(),
                'extensions' => get_loaded_extensions(),
                'ini_settings' => [
                    'error_reporting' => ini_get('error_reporting'),
                    'display_errors' => ini_get('display_errors'),
                    'log_errors' => ini_get('log_errors'),
                    'error_log' => ini_get('error_log'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'memory_limit' => ini_get('memory_limit'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                ]
            ];
            break;

        case 'check_files':
            $files = [
                '.htaccess' => file_exists('.htaccess'),
                'setup.php' => file_exists('setup.php'),
                'index.php' => file_exists('index.php'),
                'includes/config.php' => file_exists('includes/config.php'),
                'includes/database.php' => file_exists('includes/database.php'),
            ];
            
            foreach($files as $file => $exists) {
                if ($exists) {
                    $files[$file] = [
                        'exists' => true,
                        'size' => filesize($file),
                        'perms' => substr(sprintf('%o', fileperms($file)), -4),
                        'owner' => posix_getpwuid(fileowner($file))['name'] ?? 'unknown',
                        'modified' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            $response['data'] = $files;
            break;

        case 'read_file':
            $file = $_POST['file'] ?? '';
            $allowed_files = ['.htaccess', 'error_log', 'logs/error_log', 'logs/php_errors.log'];
            
            if (!in_array($file, $allowed_files)) {
                throw new Exception('File not allowed');
            }
            
            if (file_exists($file)) {
                $response['data'] = [
                    'content' => file_get_contents($file),
                    'size' => filesize($file),
                    'lines' => count(file($file))
                ];
            } else {
                $response['data'] = ['error' => 'File not found'];
            }
            break;

        case 'write_file':
            $file = $_POST['file'] ?? '';
            $content = $_POST['content'] ?? '';
            $allowed_files = ['.htaccess', '.htaccess.test'];
            
            if (!in_array($file, $allowed_files)) {
                throw new Exception('File not allowed for writing');
            }
            
            // Backup first
            if (file_exists($file)) {
                copy($file, $file . '.backup.' . time());
            }
            
            file_put_contents($file, $content);
            $response['data'] = ['success' => true, 'bytes_written' => strlen($content)];
            break;

        case 'check_errors':
            $error_locations = [
                'error_log' => 'error_log',
                'logs/error_log' => 'logs/error_log', 
                'apache_error' => '/home/dalthaus/logs/error_log',
                'php_errors' => 'logs/php_errors.log',
                'cpanel_error' => '../logs/error_log',
            ];
            
            $errors = [];
            foreach($error_locations as $name => $path) {
                if (file_exists($path)) {
                    $errors[$name] = [
                        'exists' => true,
                        'size' => filesize($path),
                        'last_10_lines' => array_slice(file($path), -10)
                    ];
                } else {
                    $errors[$name] = ['exists' => false];
                }
            }
            $response['data'] = $errors;
            break;

        case 'test_db':
            $host = $_POST['host'] ?? 'localhost';
            $name = $_POST['name'] ?? '';
            $user = $_POST['user'] ?? '';
            $pass = $_POST['pass'] ?? '';
            
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $response['data'] = ['connected' => true, 'message' => 'Database connection successful'];
            } catch(Exception $e) {
                $response['data'] = ['connected' => false, 'error' => $e->getMessage()];
            }
            break;

        case 'fix_htaccess':
            // Create a minimal working .htaccess
            $minimal = "# Minimal .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
";
            // Backup current
            if (file_exists('.htaccess')) {
                copy('.htaccess', '.htaccess.backup.' . time());
            }
            file_put_contents('.htaccess', $minimal);
            $response['data'] = ['success' => true, 'message' => '.htaccess replaced with minimal version'];
            break;

        case 'phpinfo':
            ob_start();
            phpinfo();
            $response['data'] = ['phpinfo' => ob_get_clean()];
            break;

        case 'execute':
            // Limited command execution for debugging
            $cmd = $_POST['cmd'] ?? '';
            $allowed_commands = ['pwd', 'ls -la', 'whoami', 'php -v', 'mysql --version'];
            
            if (!in_array($cmd, $allowed_commands)) {
                throw new Exception('Command not allowed');
            }
            
            $output = shell_exec($cmd . ' 2>&1');
            $response['data'] = ['command' => $cmd, 'output' => $output];
            break;

        case 'self_destruct':
            unlink(__FILE__);
            $response['data'] = ['message' => 'Agent deleted'];
            break;

        default:
            $response['error'] = 'Unknown action';
    }
} catch(Exception $e) {
    $response['status'] = 'error';
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>