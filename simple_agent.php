<?php
// Ultra-simple deployment agent - no sessions, just git pull
header('Content-Type: application/json');

// Simple authentication
$key = $_POST['key'] ?? $_GET['key'] ?? '';
if ($key !== 'dalthaus_agent_key_2025') {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'git_pull') {
    $output = [];
    $returnCode = 0;
    
    // Change to the website directory and pull
    $command = 'cd ' . __DIR__ . ' && git pull origin main 2>&1';
    exec($command, $output, $returnCode);
    
    echo json_encode([
        'success' => $returnCode === 0,
        'message' => $returnCode === 0 ? 'Git pull successful' : 'Git pull failed',
        'output' => $output,
        'return_code' => $returnCode
    ]);
} elseif ($action === 'test') {
    echo json_encode([
        'success' => true,
        'message' => 'Agent is working',
        'php_version' => PHP_VERSION,
        'directory' => __DIR__
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Use: git_pull or test'
    ]);
}
?>