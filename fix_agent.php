<?php
/**
 * Fixed Deployment Agent - No sessions, minimal dependencies
 * This replaces agent.php with a working version
 */

// Disable error display but log them
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set JSON header
header('Content-Type: application/json');

// Simple API key check
$providedKey = $_POST['key'] ?? $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$validKey = 'dalthaus_agent_key_2025';

if ($providedKey !== $validKey) {
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]));
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle actions
switch ($action) {
    case 'git_pull':
        $output = [];
        $returnCode = 0;
        
        // Execute git pull
        $command = 'cd ' . escapeshellarg(__DIR__) . ' && git pull origin main 2>&1';
        exec($command, $output, $returnCode);
        
        echo json_encode([
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Git pull completed' : 'Git pull failed',
            'output' => $output,
            'return_code' => $returnCode,
            'timestamp' => date('c')
        ]);
        break;
        
    case 'git_status':
        $output = [];
        exec('cd ' . escapeshellarg(__DIR__) . ' && git status --short 2>&1', $output);
        
        echo json_encode([
            'success' => true,
            'output' => $output,
            'timestamp' => date('c')
        ]);
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
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Available: git_pull, git_status, test',
            'timestamp' => date('c')
        ]);
}
?>