<?php
/**
 * Remote File Agent for Dalthaus CMS
 * Allows secure remote file editing and management
 * 
 * SECURITY WARNING: Delete this file after use!
 */

// Security token (changes daily)
$VALID_TOKEN = 'agent-' . date('Ymd');
$provided_token = $_REQUEST['token'] ?? '';

if ($provided_token !== $VALID_TOKEN) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid token. Use: ' . $VALID_TOKEN]));
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Base directory (restrict operations to this directory)
$BASE_DIR = realpath($_SERVER['DOCUMENT_ROOT'] ?: __DIR__);
if (!$BASE_DIR) {
    $BASE_DIR = realpath(__DIR__);
}
chdir($BASE_DIR);

// Get action
$action = $_REQUEST['action'] ?? 'status';

// Response array
$response = [
    'action' => $action,
    'status' => 'ok',
    'base_dir' => $BASE_DIR,
    'timestamp' => date('Y-m-d H:i:s')
];

// Set JSON header
header('Content-Type: application/json');

try {
    switch($action) {
        case 'status':
            // System status
            $response['data'] = [
                'php_version' => PHP_VERSION,
                'current_dir' => getcwd(),
                'user' => get_current_user(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'git_branch' => trim(shell_exec('git branch --show-current 2>&1')),
                'last_commit' => trim(shell_exec('git log -1 --oneline 2>&1'))
            ];
            break;

        case 'list':
            // List files in directory
            $path = $_REQUEST['path'] ?? '.';
            $full_path = realpath($BASE_DIR . '/' . $path);
            
            // Security check - ensure path is within BASE_DIR
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid path - outside base directory');
            }
            
            $files = [];
            if (is_dir($full_path)) {
                $items = scandir($full_path);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $item_path = $full_path . '/' . $item;
                    $files[] = [
                        'name' => $item,
                        'type' => is_dir($item_path) ? 'dir' : 'file',
                        'size' => is_file($item_path) ? filesize($item_path) : null,
                        'modified' => date('Y-m-d H:i:s', filemtime($item_path)),
                        'permissions' => substr(sprintf('%o', fileperms($item_path)), -4)
                    ];
                }
            }
            
            $response['data'] = [
                'path' => $path,
                'full_path' => $full_path,
                'files' => $files
            ];
            break;

        case 'read':
            // Read file contents
            $file = $_REQUEST['file'] ?? '';
            $full_path = realpath($BASE_DIR . '/' . $file);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (!file_exists($full_path)) {
                throw new Exception('File not found: ' . $file);
            }
            
            if (!is_readable($full_path)) {
                throw new Exception('File not readable: ' . $file);
            }
            
            $content = file_get_contents($full_path);
            $response['data'] = [
                'file' => $file,
                'size' => filesize($full_path),
                'lines' => substr_count($content, "\n") + 1,
                'content' => $content,
                'permissions' => substr(sprintf('%o', fileperms($full_path)), -4)
            ];
            break;

        case 'write':
            // Write file contents
            $file = $_REQUEST['file'] ?? '';
            $content = $_REQUEST['content'] ?? '';
            $full_path = $BASE_DIR . '/' . $file;
            
            // Security check - ensure path is within BASE_DIR
            $dir = dirname(realpath($full_path) ?: $full_path);
            if (strpos($dir, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            // Create backup if file exists
            if (file_exists($full_path)) {
                $backup_path = $full_path . '.backup.' . time();
                copy($full_path, $backup_path);
                $response['backup'] = $backup_path;
            }
            
            // Write the file
            $bytes = file_put_contents($full_path, $content);
            if ($bytes === false) {
                throw new Exception('Failed to write file: ' . $file);
            }
            
            $response['data'] = [
                'file' => $file,
                'bytes_written' => $bytes,
                'lines' => substr_count($content, "\n") + 1
            ];
            break;

        case 'edit':
            // Edit file (find and replace)
            $file = $_REQUEST['file'] ?? '';
            $find = $_REQUEST['find'] ?? '';
            $replace = $_REQUEST['replace'] ?? '';
            $full_path = realpath($BASE_DIR . '/' . $file);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (!file_exists($full_path)) {
                throw new Exception('File not found: ' . $file);
            }
            
            // Read current content
            $content = file_get_contents($full_path);
            
            // Count occurrences
            $count = substr_count($content, $find);
            if ($count === 0) {
                throw new Exception('String not found in file');
            }
            
            // Create backup
            $backup_path = $full_path . '.backup.' . time();
            copy($full_path, $backup_path);
            
            // Replace content
            $new_content = str_replace($find, $replace, $content);
            file_put_contents($full_path, $new_content);
            
            $response['data'] = [
                'file' => $file,
                'replacements' => $count,
                'backup' => $backup_path
            ];
            break;

        case 'create':
            // Create new file
            $file = $_REQUEST['file'] ?? '';
            $content = $_REQUEST['content'] ?? '';
            $full_path = $BASE_DIR . '/' . $file;
            
            // Security check
            $dir = dirname($full_path);
            if (strpos(realpath($dir), $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (file_exists($full_path)) {
                throw new Exception('File already exists: ' . $file);
            }
            
            // Create directory if needed
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Write file
            $bytes = file_put_contents($full_path, $content);
            
            $response['data'] = [
                'file' => $file,
                'bytes_written' => $bytes,
                'created' => true
            ];
            break;

        case 'delete':
            // Delete file
            $file = $_REQUEST['file'] ?? '';
            $full_path = realpath($BASE_DIR . '/' . $file);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (!file_exists($full_path)) {
                throw new Exception('File not found: ' . $file);
            }
            
            // Create backup before deletion
            $backup_path = $full_path . '.deleted.' . time();
            rename($full_path, $backup_path);
            
            $response['data'] = [
                'file' => $file,
                'deleted' => true,
                'backup' => $backup_path
            ];
            break;

        case 'chmod':
            // Change file permissions
            $file = $_REQUEST['file'] ?? '';
            $permissions = $_REQUEST['permissions'] ?? '644';
            $full_path = realpath($BASE_DIR . '/' . $file);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (!file_exists($full_path)) {
                throw new Exception('File not found: ' . $file);
            }
            
            // Change permissions
            chmod($full_path, octdec($permissions));
            
            $response['data'] = [
                'file' => $file,
                'permissions' => $permissions,
                'new_permissions' => substr(sprintf('%o', fileperms($full_path)), -4)
            ];
            break;

        case 'git_pull':
            // Pull latest from git
            $output = [];
            exec('cd ' . escapeshellarg($BASE_DIR) . ' && git pull origin main 2>&1', $output, $return_var);
            
            $response['data'] = [
                'command' => 'git pull origin main',
                'output' => $output,
                'return_code' => $return_var,
                'success' => $return_var === 0
            ];
            break;

        case 'git_status':
            // Get git status
            $status = shell_exec('cd ' . escapeshellarg($BASE_DIR) . ' && git status --porcelain 2>&1');
            $branch = trim(shell_exec('cd ' . escapeshellarg($BASE_DIR) . ' && git branch --show-current 2>&1'));
            $log = shell_exec('cd ' . escapeshellarg($BASE_DIR) . ' && git log -5 --oneline 2>&1');
            
            $response['data'] = [
                'branch' => $branch,
                'has_changes' => !empty(trim($status)),
                'changes' => $status ? explode("\n", trim($status)) : [],
                'recent_commits' => $log ? explode("\n", trim($log)) : []
            ];
            break;

        case 'search':
            // Search for text in files
            $pattern = $_REQUEST['pattern'] ?? '';
            $path = $_REQUEST['path'] ?? '.';
            $full_path = realpath($BASE_DIR . '/' . $path);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid path - outside base directory');
            }
            
            // Use grep to search
            $cmd = 'grep -r ' . escapeshellarg($pattern) . ' ' . escapeshellarg($full_path) . ' 2>/dev/null | head -50';
            $output = shell_exec($cmd);
            
            $results = [];
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (preg_match('/^([^:]+):(.*)$/', $line, $matches)) {
                        $file = str_replace($BASE_DIR . '/', '', $matches[1]);
                        $results[] = [
                            'file' => $file,
                            'line' => $matches[2]
                        ];
                    }
                }
            }
            
            $response['data'] = [
                'pattern' => $pattern,
                'path' => $path,
                'results' => $results,
                'count' => count($results)
            ];
            break;

        case 'backup':
            // Create backup of file
            $file = $_REQUEST['file'] ?? '';
            $full_path = realpath($BASE_DIR . '/' . $file);
            
            // Security check
            if (strpos($full_path, $BASE_DIR) !== 0) {
                throw new Exception('Invalid file path - outside base directory');
            }
            
            if (!file_exists($full_path)) {
                throw new Exception('File not found: ' . $file);
            }
            
            $backup_path = $full_path . '.backup.' . date('Ymd_His');
            copy($full_path, $backup_path);
            
            $response['data'] = [
                'file' => $file,
                'backup' => str_replace($BASE_DIR . '/', '', $backup_path),
                'size' => filesize($backup_path)
            ];
            break;

        case 'execute':
            // Execute PHP code (BE VERY CAREFUL!)
            $code = $_REQUEST['code'] ?? '';
            
            // Basic safety check - no system commands
            if (preg_match('/\b(exec|system|shell_exec|passthru|eval|file_get_contents|file_put_contents|unlink)\b/i', $code)) {
                throw new Exception('Potentially dangerous code detected');
            }
            
            ob_start();
            $result = eval($code);
            $output = ob_get_clean();
            
            $response['data'] = [
                'output' => $output,
                'result' => $result
            ];
            break;

        case 'cleanup':
            // Self-destruct and cleanup
            $files_to_delete = [
                'file-agent.php',
                'remote-agent.php',
                'e2e-endpoint.php',
                'git-pull.php',
                'emergency-fix.php',
                'setup-debug.php',
                'test-php.php'
            ];
            
            $deleted = [];
            foreach ($files_to_delete as $file) {
                if (file_exists($BASE_DIR . '/' . $file)) {
                    unlink($BASE_DIR . '/' . $file);
                    $deleted[] = $file;
                }
            }
            
            $response['data'] = [
                'deleted' => $deleted,
                'message' => 'Agent files cleaned up'
            ];
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

// Output response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>