<?php
// Enhanced agent test with debugging capabilities
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$input = json_decode(file_get_contents('php://input'), true);

if ($input['key'] !== 'dalthaus_agent_key_2025') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

switch ($input['action']) {
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'Agent test successful',
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]);
        break;
        
    case 'git_pull':
        $output = [];
        $returnCode = 0;
        exec('cd ' . __DIR__ . ' && git pull origin main 2>&1', $output, $returnCode);
        
        echo json_encode([
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Git pull successful' : 'Git pull failed',
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ]);
        break;
        
    case 'git_force_pull':
        $output = [];
        $returnCode = 0;
        $commands = [
            'cd ' . __DIR__,
            'git fetch origin main',
            'git reset --hard origin/main'
        ];
        
        foreach ($commands as $cmd) {
            exec($cmd . ' 2>&1', $output, $returnCode);
            if ($returnCode !== 0) break;
        }
        
        echo json_encode([
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Force pull successful' : 'Force pull failed',
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ]);
        break;
        
    case 'check_error':
        // Check PHP error log
        $errorLog = ini_get('error_log');
        $errors = [];
        
        if ($errorLog && file_exists($errorLog)) {
            $errors = array_slice(file($errorLog), -20); // Last 20 lines
        }
        
        // Check our application error log
        $appLog = __DIR__ . '/logs/error.log';
        $appErrors = [];
        if (file_exists($appLog)) {
            $appErrors = array_slice(file($appLog), -20);
        }
        
        echo json_encode([
            'success' => true,
            'php_errors' => $errors,
            'app_errors' => $appErrors,
            'error_reporting' => error_reporting(),
            'display_errors' => ini_get('display_errors')
        ]);
        break;
        
    case 'read_file':
        $path = $input['path'] ?? '';
        if (empty($path)) {
            echo json_encode(['success' => false, 'message' => 'Path required']);
            break;
        }
        
        $fullPath = strpos($path, '/') === 0 ? $path : __DIR__ . '/' . $path;
        
        if (!file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'File not found']);
            break;
        }
        
        echo json_encode([
            'success' => true,
            'content' => file_get_contents($fullPath),
            'path' => $path
        ]);
        break;
        
    case 'list_dir':
        $path = $input['path'] ?? '.';
        $fullPath = strpos($path, '/') === 0 ? $path : __DIR__ . '/' . $path;
        
        if (!is_dir($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Not a directory']);
            break;
        }
        
        $files = scandir($fullPath);
        echo json_encode([
            'success' => true,
            'files' => array_diff($files, ['.', '..']),
            'path' => $path
        ]);
        break;
        
    case 'test_db':
        // Test database connection
        try {
            require_once __DIR__ . '/config/config.php';
            $config = require __DIR__ . '/config/config.php';
            
            $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
            $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Database connected successfully',
                'database' => $config['database']['dbname']
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>