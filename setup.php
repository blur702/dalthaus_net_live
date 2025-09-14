<?php

declare(strict_types=1);

/**
 * CMS Setup Script
 * 
 * Automated setup for database installation and configuration
 * Designed for easy deployment on shared hosting environments
 * 
 * @package CMS
 * @author  Kevin
 * @version 1.0.0
 */

session_start();

// Security check - disable after setup
define('SETUP_ENABLED', true);
define('SETUP_PASSWORD', 'setup2025'); // Change this!

// Check if already configured
$configFile = __DIR__ . '/config/config.php';
$isConfigured = false;
$currentConfig = [];

if (file_exists($configFile)) {
    $currentConfig = require $configFile;
    // Check if database is already configured
    if (!empty($currentConfig['database']['host']) && 
        $currentConfig['database']['host'] !== 'localhost') {
        $isConfigured = true;
    }
}

// Authentication for setup
$authenticated = $_SESSION['setup_authenticated'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'authenticate':
                if ($_POST['password'] === SETUP_PASSWORD) {
                    $_SESSION['setup_authenticated'] = true;
                    $authenticated = true;
                    $message = ['type' => 'success', 'text' => 'Authentication successful!'];
                } else {
                    $message = ['type' => 'error', 'text' => 'Invalid password!'];
                }
                break;
                
            case 'test_connection':
                if (!$authenticated) {
                    die('Unauthorized');
                }
                $result = testDatabaseConnection($_POST);
                echo json_encode($result);
                exit;
                
            case 'install':
                if (!$authenticated) {
                    die('Unauthorized');
                }
                $result = installDatabase($_POST);
                echo json_encode($result);
                exit;
                
            case 'save_config':
                if (!$authenticated) {
                    die('Unauthorized');
                }
                $result = saveConfiguration($_POST);
                echo json_encode($result);
                exit;
        }
    }
}

function testDatabaseConnection($data) {
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if database exists
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$data['db_name']}'");
        $dbExists = $stmt->fetch() !== false;
        
        if (!$dbExists) {
            // Try to create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $dbExists = true;
        }
        
        // Connect to specific database
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
        
        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tablesExist = count($tables) > 0;
        
        return [
            'success' => true,
            'message' => 'Connection successful!',
            'database_exists' => $dbExists,
            'tables_exist' => $tablesExist,
            'table_count' => count($tables)
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ];
    }
}

function installDatabase($data) {
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read database schema
        $schemaFile = __DIR__ . '/database.sql';
        if (!file_exists($schemaFile)) {
            return ['success' => false, 'message' => 'Database schema file not found!'];
        }
        
        $schema = file_get_contents($schemaFile);
        
        // Split by delimiter and execute each statement
        $statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $schema)));
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Create default admin user if not exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['kevin']);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, is_admin, created_at, updated_at) 
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([
                'kevin',
                $data['admin_email'] ?? 'admin@dalthaus.net',
                password_hash('(130Bpm)', PASSWORD_DEFAULT)
            ]);
        } else {
            // Update existing kevin user to ensure it's an admin with correct password
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, is_admin = 1, email = ?
                WHERE username = ?
            ");
            $stmt->execute([
                password_hash('(130Bpm)', PASSWORD_DEFAULT),
                $data['admin_email'] ?? 'admin@dalthaus.net',
                'kevin'
            ]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Database installed successfully!'
        ];
        
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Installation failed: ' . $e->getMessage()
        ];
    }
}

function saveConfiguration($data) {
    try {
        // Prepare configuration array
        $config = [
            'app' => [
                'name' => $data['app_name'] ?? 'CMS',
                'base_url' => $data['app_url'] ?? '',
                'debug' => false,
                'timezone' => 'America/New_York',
                'items_per_page' => 10,
                'upload_path' => '/uploads',
                'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'max_upload_size' => 20971520, // 20MB
                'maintenance_mode' => false,
                'maintenance_message' => 'We are currently performing maintenance. Please check back soon.'
            ],
            'database' => [
                'host' => $data['db_host'],
                'port' => (int)$data['db_port'],
                'name' => $data['db_name'],
                'user' => $data['db_user'],
                'pass' => $data['db_pass'],
                'charset' => 'utf8mb4'
            ],
            'session' => [
                'name' => 'cms_session',
                'lifetime' => 86400, // 24 hours
                'secure' => !empty($data['use_https']),
                'httponly' => true,
                'samesite' => 'Lax'
            ],
            'security' => [
                'csrf_token_name' => '_token',
                'csrf_token_lifetime' => 3600
            ]
        ];
        
        // Generate configuration file content
        $configContent = "<?php\n\n";
        $configContent .= "/**\n";
        $configContent .= " * CMS Configuration\n";
        $configContent .= " * Generated by setup script on " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " */\n\n";
        $configContent .= "return " . var_export($config, true) . ";\n";
        
        // Backup existing config
        $configFile = __DIR__ . '/config/config.php';
        if (file_exists($configFile)) {
            copy($configFile, $configFile . '.backup.' . date('YmdHis'));
        }
        
        // Write new configuration
        if (file_put_contents($configFile, $configContent) === false) {
            return [
                'success' => false,
                'message' => 'Failed to write configuration file. Please check permissions.'
            ];
        }
        
        // Try to disable setup script
        $setupFile = __FILE__;
        $setupContent = file_get_contents($setupFile);
        $setupContent = str_replace(
            "define('SETUP_ENABLED', true);",
            "define('SETUP_ENABLED', false);",
            $setupContent
        );
        file_put_contents($setupFile, $setupContent);
        
        return [
            'success' => true,
            'message' => 'Configuration saved successfully!'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Configuration save failed: ' . $e->getMessage()
        ];
    }
}

// Detect environment
$isSharedHosting = !empty($_SERVER['SERVER_SOFTWARE']) && 
                   (stripos($_SERVER['SERVER_SOFTWARE'], 'cpanel') !== false ||
                    stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false ||
                    file_exists('/usr/local/cpanel'));

$suggestedUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$suggestedUrl = rtrim($suggestedUrl, '/setup.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-active { background-color: #3b82f6; color: white; }
        .step-complete { background-color: #10b981; color: white; }
        .step-inactive { background-color: #e5e7eb; color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg">
                <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
                    <h1 class="text-2xl font-bold">CMS Installation Setup</h1>
                    <p class="text-blue-100 mt-1">Configure your database and application settings</p>
                </div>
                
                <?php if (!SETUP_ENABLED): ?>
                <div class="p-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <p class="text-yellow-700">
                            Setup has been disabled. To re-enable, edit setup.php and change SETUP_ENABLED to true.
                        </p>
                    </div>
                </div>
                <?php elseif (!$authenticated): ?>
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Authentication Required</h2>
                    <form method="POST" class="max-w-md">
                        <input type="hidden" name="action" value="authenticate">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Setup Password</label>
                            <input type="password" name="password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter setup password">
                            <p class="text-sm text-gray-600 mt-1">Default: setup2025</p>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Authenticate
                        </button>
                    </form>
                    <?php if (isset($message)): ?>
                    <div class="mt-4 p-3 rounded-md <?= $message['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                        <?= htmlspecialchars($message['text']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <!-- Progress Steps -->
                <div class="px-6 py-4 border-b">
                    <div class="flex justify-between">
                        <div class="flex-1 text-center">
                            <div class="step-circle step-active inline-block w-8 h-8 rounded-full text-center leading-8">1</div>
                            <p class="text-sm mt-1">Database</p>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="step-circle step-inactive inline-block w-8 h-8 rounded-full text-center leading-8">2</div>
                            <p class="text-sm mt-1">Install</p>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="step-circle step-inactive inline-block w-8 h-8 rounded-full text-center leading-8">3</div>
                            <p class="text-sm mt-1">Configure</p>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="step-circle step-inactive inline-block w-8 h-8 rounded-full text-center leading-8">4</div>
                            <p class="text-sm mt-1">Complete</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Environment Detection -->
                    <?php if ($isSharedHosting): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <p class="text-blue-700">
                            <strong>Shared Hosting Detected:</strong> We've detected you're on a shared hosting environment. 
                            Default settings have been adjusted accordingly.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($isConfigured): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <p class="text-yellow-700">
                            <strong>Already Configured:</strong> The system appears to be already configured. 
                            You can still test the connection or reconfigure if needed.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Step 1: Database Configuration -->
                    <div id="step1" class="step-content">
                        <h2 class="text-xl font-semibold mb-4">Step 1: Database Configuration</h2>
                        
                        <form id="dbForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Host</label>
                                    <input type="text" name="db_host" value="<?= htmlspecialchars($currentConfig['database']['host'] ?? 'localhost') ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="localhost">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Port</label>
                                    <input type="text" name="db_port" value="<?= htmlspecialchars($currentConfig['database']['port'] ?? '3306') ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="3306">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Name</label>
                                    <input type="text" name="db_name" value="<?= htmlspecialchars($currentConfig['database']['dbname'] ?? 'dalthaus_maincms') ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="cms_db">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Database User</label>
                                    <input type="text" name="db_user" value="<?= htmlspecialchars($currentConfig['database']['username'] ?? 'dalthaus_maincms') ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="database_user">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Database Password</label>
                                    <input type="password" name="db_pass" value="<?= htmlspecialchars($currentConfig['database']['password'] ?? 'f4!,Wpds=w6*=~+1') ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="••••••••">
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="testConnection()" 
                                        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                                    Test Connection
                                </button>
                                <button type="button" onclick="proceedToStep2()" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Next Step →
                                </button>
                            </div>
                        </form>
                        
                        <div id="testResult" class="mt-4 hidden"></div>
                    </div>
                    
                    <!-- Step 2: Install Database -->
                    <div id="step2" class="step-content hidden">
                        <h2 class="text-xl font-semibold mb-4">Step 2: Install Database Schema</h2>
                        
                        <div class="bg-gray-50 p-4 rounded-md mb-4">
                            <p class="text-gray-700 mb-2">This will create all necessary database tables and initial data.</p>
                            <ul class="list-disc list-inside text-sm text-gray-600">
                                <li>Create database tables</li>
                                <li>Set up initial configuration</li>
                                <li>Create admin user account</li>
                            </ul>
                        </div>
                        
                        <form id="installForm" class="space-y-4">
                            <h3 class="font-semibold text-gray-700">Admin Account</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Admin Username</label>
                                    <input type="text" name="admin_user" value="kevin" readonly
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Admin Email</label>
                                    <input type="email" name="admin_email" value="admin@dalthaus.net" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Admin Password</label>
                                    <input type="password" name="admin_pass" value="(130Bpm)" readonly
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                                    <p class="text-sm text-gray-600 mt-1">Default: (130Bpm)</p>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="showStep(1)" 
                                        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                                    ← Previous
                                </button>
                                <button type="button" onclick="installDatabase()" 
                                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                    Install Database
                                </button>
                            </div>
                        </form>
                        
                        <div id="installResult" class="mt-4 hidden"></div>
                    </div>
                    
                    <!-- Step 3: Application Configuration -->
                    <div id="step3" class="step-content hidden">
                        <h2 class="text-xl font-semibold mb-4">Step 3: Application Configuration</h2>
                        
                        <form id="configForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Application Name</label>
                                    <input type="text" name="app_name" value="CMS" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Application URL</label>
                                    <input type="url" name="app_url" value="<?= htmlspecialchars($suggestedUrl) ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="use_https" <?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'checked' : '' ?> 
                                               class="mr-2">
                                        <span class="text-gray-700 text-sm font-bold">Use HTTPS</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="showStep(2)" 
                                        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                                    ← Previous
                                </button>
                                <button type="button" onclick="saveConfig()" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Save Configuration
                                </button>
                            </div>
                        </form>
                        
                        <div id="configResult" class="mt-4 hidden"></div>
                    </div>
                    
                    <!-- Step 4: Complete -->
                    <div id="step4" class="step-content hidden">
                        <div class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Installation Complete!</h2>
                            <p class="text-gray-600 mb-6">Your CMS has been successfully installed and configured.</p>
                            
                            <div class="bg-gray-50 p-6 rounded-md text-left max-w-md mx-auto">
                                <h3 class="font-semibold mb-3">Next Steps:</h3>
                                <ol class="list-decimal list-inside space-y-2 text-sm">
                                    <li>Delete or rename setup.php for security</li>
                                    <li>Login to the admin panel at <a href="/admin" class="text-blue-600 hover:underline">/admin</a></li>
                                    <li>Update your admin password if you used the default</li>
                                    <li>Configure your site settings</li>
                                </ol>
                            </div>
                            
                            <div class="mt-6">
                                <a href="/admin" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 inline-block">
                                    Go to Admin Panel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let dbConfig = {};
        
        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + step).classList.remove('hidden');
            
            // Update progress indicators
            document.querySelectorAll('.step-circle').forEach((el, index) => {
                if (index + 1 < step) {
                    el.classList.remove('step-active', 'step-inactive');
                    el.classList.add('step-complete');
                } else if (index + 1 === step) {
                    el.classList.remove('step-complete', 'step-inactive');
                    el.classList.add('step-active');
                } else {
                    el.classList.remove('step-complete', 'step-active');
                    el.classList.add('step-inactive');
                }
            });
            
            currentStep = step;
        }
        
        async function testConnection() {
            const form = document.getElementById('dbForm');
            const formData = new FormData(form);
            formData.append('action', 'test_connection');
            
            // Store config for later
            dbConfig = Object.fromEntries(formData);
            
            const resultDiv = document.getElementById('testResult');
            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = '<div class="bg-blue-100 text-blue-700 p-3 rounded">Testing connection...</div>';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-100 text-green-700 p-3 rounded">
                            <strong>Success!</strong> ${result.message}<br>
                            Database exists: ${result.database_exists ? 'Yes' : 'No'}<br>
                            Tables exist: ${result.tables_exist ? 'Yes (' + result.table_count + ' tables)' : 'No'}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-100 text-red-700 p-3 rounded">
                            <strong>Error:</strong> ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="bg-red-100 text-red-700 p-3 rounded">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        function proceedToStep2() {
            // Copy database config to install form
            const form = document.getElementById('dbForm');
            const formData = new FormData(form);
            dbConfig = Object.fromEntries(formData);
            showStep(2);
        }
        
        async function installDatabase() {
            const form = document.getElementById('installForm');
            const formData = new FormData(form);
            
            // Add database config
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'install');
            
            const resultDiv = document.getElementById('installResult');
            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = '<div class="bg-blue-100 text-blue-700 p-3 rounded">Installing database...</div>';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-100 text-green-700 p-3 rounded">
                            <strong>Success!</strong> ${result.message}
                        </div>
                    `;
                    setTimeout(() => showStep(3), 2000);
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-100 text-red-700 p-3 rounded">
                            <strong>Error:</strong> ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="bg-red-100 text-red-700 p-3 rounded">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function saveConfig() {
            const form = document.getElementById('configForm');
            const formData = new FormData(form);
            
            // Add database config
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'save_config');
            
            const resultDiv = document.getElementById('configResult');
            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = '<div class="bg-blue-100 text-blue-700 p-3 rounded">Saving configuration...</div>';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-100 text-green-700 p-3 rounded">
                            <strong>Success!</strong> ${result.message}
                        </div>
                    `;
                    setTimeout(() => showStep(4), 2000);
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-100 text-red-700 p-3 rounded">
                            <strong>Error:</strong> ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="bg-red-100 text-red-700 p-3 rounded">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            }
        }
    </script>
</body>
</html>