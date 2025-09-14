<?php

declare(strict_types=1);

/**
 * Deployment Agent for dalthaus.net
 * 
 * Secure agent for remote file operations and Git synchronization
 * Allows Claude to manage the live website through API commands
 * 
 * @package CMS
 * @author  Kevin
 * @version 1.0.0
 */

// Configuration
define('AGENT_ENABLED', true);
define('AGENT_KEY', 'dalthaus_agent_key_2025'); // CHANGE THIS!
define('GIT_BRANCH', 'main');
define('LOG_FILE', __DIR__ . '/logs/agent.log');
define('MAX_FILE_SIZE', 10485760); // 10MB max file size

// Start session for rate limiting
session_start();

// Set JSON response header
header('Content-Type: application/json');
header('X-Agent-Version: 1.0.0');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => date('c')
];

// Check if agent is enabled
if (!AGENT_ENABLED) {
    $response['message'] = 'Agent is disabled';
    die(json_encode($response));
}

// Rate limiting
$rateLimit = $_SESSION['agent_requests'] ?? [];
$currentTime = time();
$rateLimit = array_filter($rateLimit, fn($time) => $currentTime - $time < 60);
if (count($rateLimit) > 60) { // Max 60 requests per minute
    http_response_code(429);
    $response['message'] = 'Rate limit exceeded';
    die(json_encode($response));
}
$rateLimit[] = $currentTime;
$_SESSION['agent_requests'] = $rateLimit;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate authentication
if (empty($input['key']) || $input['key'] !== AGENT_KEY) {
    http_response_code(401);
    $response['message'] = 'Invalid authentication key';
    logAction('AUTH_FAILED', $input['action'] ?? 'unknown');
    die(json_encode($response));
}

// Validate action
if (empty($input['action'])) {
    http_response_code(400);
    $response['message'] = 'No action specified';
    die(json_encode($response));
}

// Process action
try {
    switch ($input['action']) {
        case 'status':
            $response = getSystemStatus();
            break;
            
        case 'read':
            $response = readFile($input['path'] ?? '');
            break;
            
        case 'write':
            $response = writeFile($input['path'] ?? '', $input['content'] ?? '');
            break;
            
        case 'edit':
            $response = editFile($input['path'] ?? '', $input['search'] ?? '', $input['replace'] ?? '');
            break;
            
        case 'delete':
            $response = deleteFile($input['path'] ?? '');
            break;
            
        case 'list':
            $response = listDirectory($input['path'] ?? '.');
            break;
            
        case 'exists':
            $response = checkFileExists($input['path'] ?? '');
            break;
            
        case 'git_pull':
            $response = gitPull();
            break;
            
        case 'git_status':
            $response = gitStatus();
            break;
            
        case 'backup':
            $response = createBackup($input['path'] ?? '');
            break;
            
        case 'restore':
            $response = restoreBackup($input['backup'] ?? '');
            break;
            
        case 'exec':
            $response = executeCommand($input['command'] ?? '');
            break;
            
        case 'logs':
            $response = getLogs($input['type'] ?? 'agent', $input['lines'] ?? 50);
            break;
            
        default:
            http_response_code(400);
            $response['message'] = 'Unknown action: ' . $input['action'];
    }
    
    logAction($input['action'], $response['success'] ? 'SUCCESS' : 'FAILED');
    
} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    logAction($input['action'], 'ERROR: ' . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);

// ===== FUNCTIONS =====

function getSystemStatus(): array {
    return [
        'success' => true,
        'message' => 'System operational',
        'data' => [
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'current_dir' => __DIR__,
            'git_installed' => shell_exec('which git') !== null,
            'disk_free' => disk_free_space(__DIR__),
            'memory_usage' => memory_get_usage(true),
            'agent_version' => '1.0.0',
            'timezone' => date_default_timezone_get()
        ]
    ];
}

function readFile(string $path): array {
    if (empty($path)) {
        return ['success' => false, 'message' => 'Path is required'];
    }
    
    $fullPath = resolvePath($path);
    
    if (!file_exists($fullPath)) {
        return ['success' => false, 'message' => 'File not found: ' . $path];
    }
    
    if (!is_readable($fullPath)) {
        return ['success' => false, 'message' => 'File not readable: ' . $path];
    }
    
    if (is_dir($fullPath)) {
        return ['success' => false, 'message' => 'Path is a directory: ' . $path];
    }
    
    $content = file_get_contents($fullPath);
    
    return [
        'success' => true,
        'message' => 'File read successfully',
        'data' => [
            'path' => $path,
            'content' => $content,
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'encoding' => mb_detect_encoding($content)
        ]
    ];
}

function writeFile(string $path, string $content): array {
    if (empty($path)) {
        return ['success' => false, 'message' => 'Path is required'];
    }
    
    $fullPath = resolvePath($path);
    
    // Check if path is protected
    if (isProtectedPath($fullPath)) {
        return ['success' => false, 'message' => 'Cannot write to protected path: ' . $path];
    }
    
    // Create backup if file exists
    if (file_exists($fullPath)) {
        $backupPath = $fullPath . '.backup.' . date('YmdHis');
        copy($fullPath, $backupPath);
    }
    
    // Create directory if it doesn't exist
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Write file
    $result = file_put_contents($fullPath, $content);
    
    if ($result === false) {
        return ['success' => false, 'message' => 'Failed to write file: ' . $path];
    }
    
    return [
        'success' => true,
        'message' => 'File written successfully',
        'data' => [
            'path' => $path,
            'bytes_written' => $result,
            'backup' => isset($backupPath) ? basename($backupPath) : null
        ]
    ];
}

function editFile(string $path, string $search, string $replace): array {
    if (empty($path) || empty($search)) {
        return ['success' => false, 'message' => 'Path and search text are required'];
    }
    
    $fullPath = resolvePath($path);
    
    if (!file_exists($fullPath)) {
        return ['success' => false, 'message' => 'File not found: ' . $path];
    }
    
    if (isProtectedPath($fullPath)) {
        return ['success' => false, 'message' => 'Cannot edit protected file: ' . $path];
    }
    
    $content = file_get_contents($fullPath);
    $occurrences = substr_count($content, $search);
    
    if ($occurrences === 0) {
        return ['success' => false, 'message' => 'Search text not found in file'];
    }
    
    // Create backup
    $backupPath = $fullPath . '.backup.' . date('YmdHis');
    copy($fullPath, $backupPath);
    
    // Replace content
    $newContent = str_replace($search, $replace, $content);
    file_put_contents($fullPath, $newContent);
    
    return [
        'success' => true,
        'message' => "Replaced {$occurrences} occurrence(s)",
        'data' => [
            'path' => $path,
            'occurrences' => $occurrences,
            'backup' => basename($backupPath)
        ]
    ];
}

function deleteFile(string $path): array {
    if (empty($path)) {
        return ['success' => false, 'message' => 'Path is required'];
    }
    
    $fullPath = resolvePath($path);
    
    if (!file_exists($fullPath)) {
        return ['success' => false, 'message' => 'File not found: ' . $path];
    }
    
    if (isProtectedPath($fullPath)) {
        return ['success' => false, 'message' => 'Cannot delete protected file: ' . $path];
    }
    
    // Create backup before deletion
    $backupPath = $fullPath . '.deleted.' . date('YmdHis');
    rename($fullPath, $backupPath);
    
    return [
        'success' => true,
        'message' => 'File deleted (backed up)',
        'data' => [
            'path' => $path,
            'backup' => basename($backupPath)
        ]
    ];
}

function listDirectory(string $path): array {
    $fullPath = resolvePath($path);
    
    if (!is_dir($fullPath)) {
        return ['success' => false, 'message' => 'Not a directory: ' . $path];
    }
    
    $files = [];
    $iterator = new DirectoryIterator($fullPath);
    
    foreach ($iterator as $file) {
        if ($file->isDot()) continue;
        
        $files[] = [
            'name' => $file->getFilename(),
            'type' => $file->getType(),
            'size' => $file->getSize(),
            'modified' => $file->getMTime(),
            'permissions' => substr(sprintf('%o', $file->getPerms()), -4)
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Directory listed',
        'data' => [
            'path' => $path,
            'count' => count($files),
            'files' => $files
        ]
    ];
}

function checkFileExists(string $path): array {
    $fullPath = resolvePath($path);
    $exists = file_exists($fullPath);
    
    return [
        'success' => true,
        'message' => $exists ? 'File exists' : 'File does not exist',
        'data' => [
            'path' => $path,
            'exists' => $exists,
            'type' => $exists ? (is_dir($fullPath) ? 'directory' : 'file') : null
        ]
    ];
}

function gitPull(): array {
    if (!shell_exec('which git')) {
        return ['success' => false, 'message' => 'Git is not installed'];
    }
    
    $commands = [
        'cd ' . escapeshellarg(__DIR__),
        'git fetch origin ' . GIT_BRANCH,
        'git reset --hard origin/' . GIT_BRANCH,
        'git clean -fd'
    ];
    
    $output = [];
    $returnCode = 0;
    
    foreach ($commands as $command) {
        $result = [];
        $code = 0;
        exec($command . ' 2>&1', $result, $code);
        $output = array_merge($output, $result);
        if ($code !== 0) {
            $returnCode = $code;
            break;
        }
    }
    
    // Clear any caches
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    return [
        'success' => $returnCode === 0,
        'message' => $returnCode === 0 ? 'Git pull successful' : 'Git pull failed',
        'data' => [
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
            'branch' => GIT_BRANCH
        ]
    ];
}

function gitStatus(): array {
    if (!shell_exec('which git')) {
        return ['success' => false, 'message' => 'Git is not installed'];
    }
    
    $commands = [
        'branch' => 'git branch --show-current',
        'status' => 'git status --short',
        'remote' => 'git remote -v',
        'log' => 'git log --oneline -5'
    ];
    
    $data = [];
    
    foreach ($commands as $key => $command) {
        $output = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && ' . $command . ' 2>&1');
        $data[$key] = trim($output);
    }
    
    return [
        'success' => true,
        'message' => 'Git status retrieved',
        'data' => $data
    ];
}

function createBackup(string $path): array {
    if (empty($path)) {
        // Backup entire site
        $backupName = 'site_backup_' . date('YmdHis') . '.tar.gz';
        $backupPath = __DIR__ . '/backups/' . $backupName;
        
        if (!is_dir(__DIR__ . '/backups')) {
            mkdir(__DIR__ . '/backups', 0755, true);
        }
        
        $command = sprintf(
            'cd %s && tar -czf %s --exclude=backups --exclude=node_modules --exclude=.git .',
            escapeshellarg(__DIR__),
            escapeshellarg($backupPath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            return ['success' => false, 'message' => 'Backup failed'];
        }
        
        return [
            'success' => true,
            'message' => 'Full site backup created',
            'data' => [
                'backup' => $backupName,
                'size' => filesize($backupPath),
                'path' => 'backups/' . $backupName
            ]
        ];
    } else {
        // Backup specific file
        $fullPath = resolvePath($path);
        
        if (!file_exists($fullPath)) {
            return ['success' => false, 'message' => 'File not found: ' . $path];
        }
        
        $backupPath = $fullPath . '.backup.' . date('YmdHis');
        copy($fullPath, $backupPath);
        
        return [
            'success' => true,
            'message' => 'File backup created',
            'data' => [
                'original' => $path,
                'backup' => basename($backupPath)
            ]
        ];
    }
}

function restoreBackup(string $backup): array {
    if (strpos($backup, 'site_backup_') === 0) {
        // Restore full site backup
        $backupPath = __DIR__ . '/backups/' . $backup;
        
        if (!file_exists($backupPath)) {
            return ['success' => false, 'message' => 'Backup not found'];
        }
        
        $command = sprintf(
            'cd %s && tar -xzf %s',
            escapeshellarg(__DIR__),
            escapeshellarg($backupPath)
        );
        
        exec($command, $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Backup restored' : 'Restore failed',
            'data' => ['backup' => $backup]
        ];
    } else {
        // Restore file backup
        $backupPath = __DIR__ . '/' . $backup;
        
        if (!file_exists($backupPath)) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }
        
        // Extract original filename
        $originalPath = preg_replace('/\.backup\.\d+$/', '', $backupPath);
        
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }
        
        copy($backupPath, $originalPath);
        
        return [
            'success' => true,
            'message' => 'File restored from backup',
            'data' => [
                'backup' => $backup,
                'restored_to' => basename($originalPath)
            ]
        ];
    }
}

function executeCommand(string $command): array {
    // Whitelist of allowed commands
    $allowedCommands = [
        'ls', 'pwd', 'whoami', 'php -v', 'mysql --version',
        'df -h', 'free -m', 'uptime', 'date'
    ];
    
    $isAllowed = false;
    foreach ($allowedCommands as $allowed) {
        if (strpos($command, $allowed) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        return ['success' => false, 'message' => 'Command not allowed'];
    }
    
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'message' => 'Command executed',
        'data' => [
            'command' => $command,
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ]
    ];
}

function getLogs(string $type, int $lines): array {
    $logFiles = [
        'agent' => LOG_FILE,
        'error' => __DIR__ . '/logs/error.log',
        'access' => __DIR__ . '/logs/access.log'
    ];
    
    if (!isset($logFiles[$type])) {
        return ['success' => false, 'message' => 'Unknown log type'];
    }
    
    $logFile = $logFiles[$type];
    
    if (!file_exists($logFile)) {
        return ['success' => false, 'message' => 'Log file not found'];
    }
    
    $command = sprintf('tail -n %d %s', $lines, escapeshellarg($logFile));
    $output = shell_exec($command);
    
    return [
        'success' => true,
        'message' => 'Logs retrieved',
        'data' => [
            'type' => $type,
            'lines' => $lines,
            'content' => $output
        ]
    ];
}

function resolvePath(string $path): string {
    // If absolute path, use it
    if (strpos($path, '/') === 0) {
        return $path;
    }
    
    // Otherwise, relative to document root
    return __DIR__ . '/' . ltrim($path, '/');
}

function isProtectedPath(string $path): bool {
    $protected = [
        'agent.php',
        'config/config.php',
        '.env',
        '.git',
        '.gitignore'
    ];
    
    foreach ($protected as $protectedPath) {
        if (strpos($path, $protectedPath) !== false) {
            return true;
        }
    }
    
    return false;
}

function logAction(string $action, string $result): void {
    if (!is_dir(dirname(LOG_FILE))) {
        mkdir(dirname(LOG_FILE), 0755, true);
    }
    
    $entry = sprintf(
        "[%s] %s | Action: %s | Result: %s | IP: %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        $action,
        $result,
        $_SERVER['REMOTE_ADDR'] ?? 'localhost'
    );
    
    file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

?>