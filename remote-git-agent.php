<?php
/**
 * Remote Git Agent for Shared Hosting
 * Allows secure git operations on the server
 * 
 * SECURITY: Uses daily rotating token + IP whitelist
 */

// Security Configuration
$VALID_TOKEN = 'agent-' . date('Ymd'); // Changes daily
$ALLOWED_IPS = []; // Add your IP if needed
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

// Execute git command safely
function executeGitCommand($command, $args = []) {
    global $BASE_DIR;
    
    // Whitelist of allowed git commands
    $allowedCommands = [
        'status', 'pull', 'fetch', 'log', 'diff', 'branch', 
        'checkout', 'add', 'commit', 'push', 'stash', 'reset',
        'remote', 'show', 'rev-parse', 'describe', 'tag'
    ];
    
    if (!in_array($command, $allowedCommands)) {
        throw new Exception("Git command '$command' is not allowed");
    }
    
    // Build the command
    $gitCmd = 'cd ' . escapeshellarg($BASE_DIR) . ' && git ' . escapeshellcmd($command);
    
    // Add arguments safely
    foreach ($args as $arg) {
        // Skip empty arguments
        if (empty($arg)) continue;
        
        // Special handling for certain arguments
        if (strpos($arg, '-') === 0) {
            // It's a flag/option
            $gitCmd .= ' ' . escapeshellcmd($arg);
        } else {
            // It's a parameter
            $gitCmd .= ' ' . escapeshellarg($arg);
        }
    }
    
    // Add 2>&1 to capture stderr
    $gitCmd .= ' 2>&1';
    
    // Execute command
    exec($gitCmd, $output, $returnCode);
    
    return [
        'command' => $command,
        'output' => $output,
        'return_code' => $returnCode,
        'success' => $returnCode === 0
    ];
}

// Main handler
authenticate();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false];

try {
    switch ($action) {
        case 'git':
            $command = $_POST['command'] ?? $_GET['command'] ?? '';
            $args = $_POST['args'] ?? $_GET['args'] ?? [];
            
            // Handle args as string or array
            if (is_string($args)) {
                $args = explode(' ', $args);
            }
            
            $result = executeGitCommand($command, $args);
            $response = [
                'success' => $result['success'],
                'command' => $result['command'],
                'output' => implode("\n", $result['output']),
                'return_code' => $result['return_code']
            ];
            break;
            
        case 'status':
            $result = executeGitCommand('status', ['--short']);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output']),
                'files' => $result['output']
            ];
            break;
            
        case 'pull':
            $remote = $_POST['remote'] ?? 'origin';
            $branch = $_POST['branch'] ?? 'main';
            
            // First do a fetch to get latest
            $fetchResult = executeGitCommand('fetch', [$remote]);
            
            // Then pull
            $result = executeGitCommand('pull', [$remote, $branch]);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output']),
                'updated' => strpos(implode(' ', $result['output']), 'Already up to date') === false,
                'files_changed' => []
            ];
            
            // Get list of changed files if updated
            if ($response['updated']) {
                $diffResult = executeGitCommand('diff', ['--name-only', 'HEAD@{1}', 'HEAD']);
                if ($diffResult['success']) {
                    $response['files_changed'] = $diffResult['output'];
                }
            }
            break;
            
        case 'push':
            $remote = $_POST['remote'] ?? 'origin';
            $branch = $_POST['branch'] ?? 'main';
            
            $result = executeGitCommand('push', [$remote, $branch]);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'commit':
            $message = $_POST['message'] ?? '';
            if (empty($message)) {
                throw new Exception('Commit message is required');
            }
            
            // First add all changes
            $addResult = executeGitCommand('add', ['-A']);
            if (!$addResult['success']) {
                throw new Exception('Failed to add changes: ' . implode("\n", $addResult['output']));
            }
            
            // Then commit
            $result = executeGitCommand('commit', ['-m', $message]);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'diff':
            $file = $_POST['file'] ?? '';
            $args = $file ? [$file] : [];
            
            $result = executeGitCommand('diff', $args);
            $response = [
                'success' => true,
                'diff' => implode("\n", $result['output'])
            ];
            break;
            
        case 'log':
            $limit = $_POST['limit'] ?? '10';
            $result = executeGitCommand('log', ['--oneline', '-n', $limit]);
            
            $commits = [];
            foreach ($result['output'] as $line) {
                if (preg_match('/^([a-f0-9]+)\s+(.+)$/', $line, $matches)) {
                    $commits[] = [
                        'hash' => $matches[1],
                        'message' => $matches[2]
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'commits' => $commits,
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'branch':
            $result = executeGitCommand('branch', ['-a']);
            
            $branches = [];
            foreach ($result['output'] as $line) {
                $line = trim($line);
                $current = false;
                if (strpos($line, '*') === 0) {
                    $current = true;
                    $line = trim(substr($line, 1));
                }
                $branches[] = [
                    'name' => $line,
                    'current' => $current
                ];
            }
            
            $response = [
                'success' => true,
                'branches' => $branches,
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'checkout':
            $branch = $_POST['branch'] ?? '';
            if (empty($branch)) {
                throw new Exception('Branch name is required');
            }
            
            $result = executeGitCommand('checkout', [$branch]);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'stash':
            $subcommand = $_POST['subcommand'] ?? 'save';
            $args = [$subcommand];
            
            if ($subcommand === 'save' && isset($_POST['message'])) {
                $args[] = $_POST['message'];
            }
            
            $result = executeGitCommand('stash', $args);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'reset':
            $mode = $_POST['mode'] ?? '--soft';
            $commit = $_POST['commit'] ?? 'HEAD';
            
            // Safety check for hard reset
            if ($mode === '--hard') {
                $confirm = $_POST['confirm'] ?? '';
                if ($confirm !== 'yes-hard-reset') {
                    throw new Exception('Hard reset requires confirmation');
                }
            }
            
            $result = executeGitCommand('reset', [$mode, $commit]);
            $response = [
                'success' => $result['success'],
                'output' => implode("\n", $result['output'])
            ];
            break;
            
        case 'info':
            // Get various git info
            $status = executeGitCommand('status', ['--short']);
            $branch = executeGitCommand('rev-parse', ['--abbrev-ref', 'HEAD']);
            $remote = executeGitCommand('remote', ['-v']);
            $lastCommit = executeGitCommand('log', ['-1', '--oneline']);
            
            $response = [
                'success' => true,
                'info' => [
                    'current_branch' => trim(implode('', $branch['output'])),
                    'status' => $status['output'],
                    'remotes' => $remote['output'],
                    'last_commit' => trim(implode('', $lastCommit['output'])),
                    'working_directory' => $BASE_DIR
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