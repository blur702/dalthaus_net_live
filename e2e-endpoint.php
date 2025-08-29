<?php
/**
 * E2E Testing Endpoint for Dalthaus CMS
 * Provides comprehensive testing capabilities for the e2e-test-runner agent
 * 
 * SECURITY: Delete this file after testing!
 */

// Security token (changes daily)
$VALID_TOKEN = 'e2e-' . date('Ymd');
$provided_token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($provided_token !== $VALID_TOKEN) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid token. Use: ' . $VALID_TOKEN]));
}

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set JSON header
header('Content-Type: application/json');

// Get test action
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

// Test results storage
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'action' => $action,
    'status' => 'running',
    'tests' => []
];

try {
    switch($action) {
        case 'status':
            // System status check
            $results['tests']['system_status'] = [
                'name' => 'System Status',
                'status' => 'pass',
                'details' => [
                    'php_version' => PHP_VERSION,
                    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'document_root' => $_SERVER['DOCUMENT_ROOT'],
                    'current_dir' => __DIR__,
                    'memory_usage' => memory_get_usage(true),
                    'max_execution_time' => ini_get('max_execution_time'),
                ]
            ];
            
            // Check required extensions
            $required_extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];
            $missing = [];
            foreach ($required_extensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missing[] = $ext;
                }
            }
            
            $results['tests']['extensions'] = [
                'name' => 'Required Extensions',
                'status' => empty($missing) ? 'pass' : 'fail',
                'details' => [
                    'required' => $required_extensions,
                    'missing' => $missing,
                    'loaded' => get_loaded_extensions()
                ]
            ];
            break;

        case 'check_files':
            // Check critical files exist
            $critical_files = [
                'setup.php' => ['exists' => file_exists('setup.php'), 'readable' => is_readable('setup.php')],
                'index.php' => ['exists' => file_exists('index.php'), 'readable' => is_readable('index.php')],
                '.htaccess' => ['exists' => file_exists('.htaccess'), 'readable' => is_readable('.htaccess')],
                'includes/config.php' => ['exists' => file_exists('includes/config.php'), 'readable' => is_readable('includes/config.php')],
                'includes/database.php' => ['exists' => file_exists('includes/database.php'), 'readable' => is_readable('includes/database.php')],
                'admin/login.php' => ['exists' => file_exists('admin/login.php'), 'readable' => is_readable('admin/login.php')],
            ];
            
            $all_exist = true;
            foreach ($critical_files as $file => $status) {
                if (!$status['exists']) {
                    $all_exist = false;
                    break;
                }
            }
            
            $results['tests']['critical_files'] = [
                'name' => 'Critical Files Check',
                'status' => $all_exist ? 'pass' : 'fail',
                'details' => $critical_files
            ];
            break;

        case 'check_directories':
            // Check required directories
            $required_dirs = ['admin', 'includes', 'public', 'assets', 'uploads', 'cache', 'logs'];
            $dir_status = [];
            $all_good = true;
            
            foreach ($required_dirs as $dir) {
                $exists = is_dir($dir);
                $writable = is_writable($dir);
                $dir_status[$dir] = [
                    'exists' => $exists,
                    'writable' => $writable,
                    'permissions' => $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A'
                ];
                
                if (!$exists || (!in_array($dir, ['admin', 'includes', 'public', 'assets']) && !$writable)) {
                    $all_good = false;
                }
            }
            
            $results['tests']['directories'] = [
                'name' => 'Directory Structure',
                'status' => $all_good ? 'pass' : 'fail',
                'details' => $dir_status
            ];
            break;

        case 'test_database':
            // Test database connectivity
            $config_file = 'includes/config.php';
            
            if (!file_exists($config_file)) {
                $results['tests']['database'] = [
                    'name' => 'Database Connection',
                    'status' => 'skip',
                    'message' => 'Config file not found - setup not run yet'
                ];
            } else {
                require_once $config_file;
                
                try {
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $pdo = new PDO($dsn, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Check tables
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $required_tables = ['users', 'content', 'content_versions', 'menus', 'sessions', 'attachments', 'settings'];
                    $missing_tables = array_diff($required_tables, $tables);
                    
                    $results['tests']['database'] = [
                        'name' => 'Database Connection',
                        'status' => empty($missing_tables) ? 'pass' : 'partial',
                        'details' => [
                            'connected' => true,
                            'tables_found' => $tables,
                            'missing_tables' => $missing_tables
                        ]
                    ];
                } catch (Exception $e) {
                    $results['tests']['database'] = [
                        'name' => 'Database Connection',
                        'status' => 'fail',
                        'error' => $e->getMessage()
                    ];
                }
            }
            break;

        case 'test_setup':
            // Test if setup.php is accessible
            $setup_url = 'https://' . $_SERVER['HTTP_HOST'] . '/setup.php';
            $ch = curl_init($setup_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $results['tests']['setup_accessibility'] = [
                'name' => 'Setup Wizard Accessibility',
                'status' => ($http_code === 200) ? 'pass' : 'fail',
                'details' => [
                    'url' => $setup_url,
                    'http_code' => $http_code,
                    'expected' => 200
                ]
            ];
            break;

        case 'test_routes':
            // Test various routes
            $base_url = 'https://' . $_SERVER['HTTP_HOST'];
            $routes_to_test = [
                '/' => 'Homepage',
                '/admin/login.php' => 'Admin Login',
                '/setup.php' => 'Setup Wizard',
                '/nonexistent' => '404 Page (should redirect)'
            ];
            
            $route_results = [];
            foreach ($routes_to_test as $route => $description) {
                $url = $base_url . $route;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $route_results[$route] = [
                    'description' => $description,
                    'http_code' => $http_code,
                    'status' => in_array($http_code, [200, 301, 302]) ? 'ok' : 'error'
                ];
            }
            
            $all_ok = true;
            foreach ($route_results as $result) {
                if ($result['status'] !== 'ok') {
                    $all_ok = false;
                    break;
                }
            }
            
            $results['tests']['routes'] = [
                'name' => 'Route Testing',
                'status' => $all_ok ? 'pass' : 'fail',
                'details' => $route_results
            ];
            break;

        case 'test_security':
            // Security checks
            $security_checks = [];
            
            // Check if setup.php still exists (after setup)
            if (file_exists('includes/config.php')) {
                $security_checks['setup_file'] = [
                    'check' => 'Setup file should be deleted after installation',
                    'status' => !file_exists('setup.php') ? 'pass' : 'warning',
                    'exists' => file_exists('setup.php')
                ];
            }
            
            // Check error display
            $security_checks['error_display'] = [
                'check' => 'Error display should be off in production',
                'status' => ini_get('display_errors') === '0' ? 'pass' : 'warning',
                'current_value' => ini_get('display_errors')
            ];
            
            // Check sensitive files protection
            $sensitive_files = ['.env', '.git/config', 'includes/config.php'];
            foreach ($sensitive_files as $file) {
                if (file_exists($file)) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $file;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    $security_checks['file_' . str_replace(['/', '.'], '_', $file)] = [
                        'check' => "Protection of $file",
                        'status' => ($http_code === 403 || $http_code === 404) ? 'pass' : 'fail',
                        'http_code' => $http_code
                    ];
                }
            }
            
            $all_secure = true;
            foreach ($security_checks as $check) {
                if ($check['status'] === 'fail') {
                    $all_secure = false;
                    break;
                }
            }
            
            $results['tests']['security'] = [
                'name' => 'Security Checks',
                'status' => $all_secure ? 'pass' : 'warning',
                'details' => $security_checks
            ];
            break;

        case 'run_all':
            // Run all tests
            $all_actions = ['status', 'check_files', 'check_directories', 'test_database', 'test_setup', 'test_routes', 'test_security'];
            
            foreach ($all_actions as $test_action) {
                $_GET['action'] = $test_action;
                $test_result = json_decode(file_get_contents('https://' . $_SERVER['HTTP_HOST'] . '/e2e-endpoint.php?token=' . $VALID_TOKEN . '&action=' . $test_action), true);
                if (isset($test_result['tests'])) {
                    foreach ($test_result['tests'] as $key => $test) {
                        $results['tests'][$key] = $test;
                    }
                }
            }
            break;

        case 'git_pull':
            // Pull latest changes from GitHub
            $output = [];
            $return_var = 0;
            
            // Change to the document root
            chdir($_SERVER['DOCUMENT_ROOT']);
            
            // Execute git pull
            exec('git pull origin main 2>&1', $output, $return_var);
            
            $results['tests']['git_pull'] = [
                'name' => 'Git Pull from Repository',
                'status' => ($return_var === 0) ? 'pass' : 'fail',
                'details' => [
                    'command' => 'git pull origin main',
                    'return_code' => $return_var,
                    'output' => $output,
                    'current_branch' => trim(shell_exec('git branch --show-current')),
                    'last_commit' => trim(shell_exec('git log -1 --oneline')),
                    'working_directory' => getcwd()
                ]
            ];
            break;

        case 'git_status':
            // Check git status
            chdir($_SERVER['DOCUMENT_ROOT']);
            
            $status_output = shell_exec('git status --porcelain 2>&1');
            $branch = trim(shell_exec('git branch --show-current'));
            $last_commit = trim(shell_exec('git log -1 --oneline'));
            $remote_url = trim(shell_exec('git remote get-url origin'));
            
            $results['tests']['git_status'] = [
                'name' => 'Git Repository Status',
                'status' => 'info',
                'details' => [
                    'branch' => $branch,
                    'last_commit' => $last_commit,
                    'remote_url' => $remote_url,
                    'has_changes' => !empty($status_output),
                    'changed_files' => $status_output ? explode("\n", trim($status_output)) : [],
                    'working_directory' => getcwd()
                ]
            ];
            break;

        case 'cleanup':
            // Self-destruct option
            $files_to_delete = [
                'e2e-endpoint.php',
                'remote-agent.php',
                'setup-debug.php',
                'test-php.php',
                'remote-debug.py',
                'push-to-github.sh'
            ];
            
            $deleted = [];
            foreach ($files_to_delete as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        $deleted[] = $file;
                    }
                }
            }
            
            $results['tests']['cleanup'] = [
                'name' => 'Cleanup Testing Files',
                'status' => 'complete',
                'deleted_files' => $deleted
            ];
            break;

        default:
            $results['error'] = 'Unknown action: ' . $action;
    }
    
    // Calculate overall status
    $has_fail = false;
    $has_warning = false;
    
    foreach ($results['tests'] as $test) {
        if (isset($test['status'])) {
            if ($test['status'] === 'fail') {
                $has_fail = true;
            } elseif ($test['status'] === 'warning') {
                $has_warning = true;
            }
        }
    }
    
    if ($has_fail) {
        $results['status'] = 'fail';
    } elseif ($has_warning) {
        $results['status'] = 'warning';
    } else {
        $results['status'] = 'pass';
    }
    
} catch (Exception $e) {
    $results['status'] = 'error';
    $results['error'] = $e->getMessage();
    $results['trace'] = $e->getTraceAsString();
}

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);
?>