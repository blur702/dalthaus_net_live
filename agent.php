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
                'test' => 'Test agent connectivity',
                'help' => 'Show this help message'
            ]
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use action=help to see available actions'
        ]);
}
?>