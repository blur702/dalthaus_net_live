<?php
/**
 * Remote File Agent for Shared Hosting
 * Allows secure read/write operations on the server
 * 
 * SECURITY: Uses daily rotating token + IP whitelist
 */

// Security Configuration
$VALID_TOKEN = 'agent-' . date('Ymd'); // Changes daily
$ALLOWED_IPS = []; // Add your IP if needed, e.g., ['123.456.789.0']
$BASE_DIR = realpath(__DIR__); // Restrict to website directory

// Disable error display for production
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON response header
header('Content-Type: application/json');

// Security checks
function authenticate() {
    global $VALID_TOKEN, $ALLOWED_IPS;
    
    // Check token
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? $_POST['token'] ?? $_GET['token'] ?? '';
    if ($token !== $VALID_TOKEN) {
        http_response_code(401);
        die(json_encode(['error' => 'Invalid token']));
    }
    
    // Check IP if whitelist is configured
    if (!empty($ALLOWED_IPS) && !in_array($_SERVER['REMOTE_ADDR'], $ALLOWED_IPS)) {
        http_response_code(403);
        die(json_encode(['error' => 'IP not authorized']));
    }
}

// Validate path is within BASE_DIR
function validatePath($path) {
    global $BASE_DIR;
    $realPath = realpath($BASE_DIR . '/' . $path);
    
    // If file doesn't exist yet (for write operations), check parent directory
    if ($realPath === false) {
        $dir = dirname($BASE_DIR . '/' . $path);
        $realPath = realpath($dir);
        if ($realPath === false) {
            return false;
        }
        $realPath = $realPath . '/' . basename($path);
    }
    
    // Ensure path is within BASE_DIR
    if (strpos($realPath, $BASE_DIR) !== 0) {
        return false;
    }
    
    return $realPath;
}

// Main handler
authenticate();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false];

try {
    switch ($action) {
        case 'read':
            $path = $_POST['path'] ?? $_GET['path'] ?? '';
            $fullPath = validatePath($path);
            
            if (!$fullPath) {
                throw new Exception('Invalid path');
            }
            
            if (!file_exists($fullPath)) {
                throw new Exception('File not found');
            }
            
            $content = file_get_contents($fullPath);
            $response = [
                'success' => true,
                'content' => $content,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath)
            ];
            break;
            
        case 'write':
            $path = $_POST['path'] ?? '';
            $content = $_POST['content'] ?? '';
            $fullPath = validatePath($path);
            
            if (!$fullPath) {
                throw new Exception('Invalid path');
            }
            
            // Create directory if it doesn't exist
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $bytes = file_put_contents($fullPath, $content);
            
            if ($bytes === false) {
                throw new Exception('Failed to write file');
            }
            
            $response = [
                'success' => true,
                'bytes' => $bytes,
                'path' => $path
            ];
            break;
            
        case 'delete':
            $path = $_POST['path'] ?? $_GET['path'] ?? '';
            $fullPath = validatePath($path);
            
            if (!$fullPath) {
                throw new Exception('Invalid path');
            }
            
            if (!file_exists($fullPath)) {
                throw new Exception('File not found');
            }
            
            if (is_dir($fullPath)) {
                if (!rmdir($fullPath)) {
                    throw new Exception('Failed to delete directory');
                }
            } else {
                if (!unlink($fullPath)) {
                    throw new Exception('Failed to delete file');
                }
            }
            
            $response = [
                'success' => true,
                'deleted' => $path
            ];
            break;
            
        case 'list':
            $path = $_POST['path'] ?? $_GET['path'] ?? '.';
            $fullPath = validatePath($path);
            
            if (!$fullPath || !is_dir($fullPath)) {
                throw new Exception('Invalid directory');
            }
            
            $files = [];
            $items = scandir($fullPath);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                $itemPath = $fullPath . '/' . $item;
                $relativePath = str_replace($BASE_DIR . '/', '', $itemPath);
                
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'type' => is_dir($itemPath) ? 'directory' : 'file',
                    'size' => is_file($itemPath) ? filesize($itemPath) : null,
                    'modified' => filemtime($itemPath)
                ];
            }
            
            $response = [
                'success' => true,
                'path' => $path,
                'files' => $files
            ];
            break;
            
        case 'exists':
            $path = $_POST['path'] ?? $_GET['path'] ?? '';
            $fullPath = validatePath($path);
            
            $response = [
                'success' => true,
                'exists' => $fullPath && file_exists($fullPath),
                'type' => $fullPath && file_exists($fullPath) ? 
                    (is_dir($fullPath) ? 'directory' : 'file') : null
            ];
            break;
            
        case 'mkdir':
            $path = $_POST['path'] ?? '';
            $fullPath = validatePath($path);
            
            if (!$fullPath) {
                throw new Exception('Invalid path');
            }
            
            if (file_exists($fullPath)) {
                throw new Exception('Path already exists');
            }
            
            if (!mkdir($fullPath, 0755, true)) {
                throw new Exception('Failed to create directory');
            }
            
            $response = [
                'success' => true,
                'created' => $path
            ];
            break;
            
        case 'chmod':
            $path = $_POST['path'] ?? '';
            $mode = $_POST['mode'] ?? '0644';
            $fullPath = validatePath($path);
            
            if (!$fullPath) {
                throw new Exception('Invalid path');
            }
            
            if (!file_exists($fullPath)) {
                throw new Exception('File not found');
            }
            
            if (!chmod($fullPath, octdec($mode))) {
                throw new Exception('Failed to change permissions');
            }
            
            $response = [
                'success' => true,
                'path' => $path,
                'mode' => $mode
            ];
            break;
            
        case 'info':
            $response = [
                'success' => true,
                'server' => [
                    'php_version' => phpversion(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                    'base_dir' => $BASE_DIR,
                    'token_date' => date('Y-m-d'),
                    'token' => $VALID_TOKEN
                ]
            ];
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);