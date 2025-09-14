<?php
// Simple agent test that doesn't require database
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($input['key'] !== 'dalthaus_agent_key_2025') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

if ($input['action'] === 'test') {
    echo json_encode([
        'success' => true,
        'message' => 'Agent test successful',
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]);
    exit;
}

if ($input['action'] === 'git_pull') {
    $output = [];
    $returnCode = 0;
    exec('cd ' . __DIR__ . ' && git pull origin main 2>&1', $output, $returnCode);
    
    echo json_encode([
        'success' => $returnCode === 0,
        'message' => $returnCode === 0 ? 'Git pull successful' : 'Git pull failed',
        'output' => implode("\n", $output),
        'return_code' => $returnCode
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
?>