<?php
/**
 * Dalthaus Photography CMS - Professional Setup Wizard
 * Version 4.0.0 - Production Ready for PHP 8.4
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$is_cli = (php_sapi_name() === 'cli');

// CLI Mode - Simple text interface
if ($is_cli) {
    require_once 'setup-cli.php';
    exit;
}

// Browser Mode - Professional UI
session_start();

// Check if already installed
$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile) && !isset($_GET['override'])) {
    // System is already installed
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Already Installed - Dalthaus CMS</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .message { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; text-align: center; }
            .icon { font-size: 60px; margin-bottom: 20px; }
            h2 { color: #333; margin-bottom: 10px; }
            p { color: #666; margin-bottom: 20px; }
            a { color: #667eea; text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="message">
            <div class="icon">✅</div>
            <h2>Already Installed</h2>
            <p>Dalthaus CMS is already installed on this server.</p>
            <p>For security reasons, the setup wizard has been disabled.</p>
            <p><a href="/admin/login.php">Go to Admin Panel →</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    // Handle different AJAX actions
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'test_connection':
            testDatabaseConnection();
            break;
        case 'create_database':
            createDatabase();
            break;
        case 'create_tables':
            createTables();
            break;
        case 'create_admin':
            createAdmin();
            break;
        case 'finalize':
            finalizeSetup();
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

// Functions for AJAX handlers
function testDatabaseConnection() {
    $host = $_POST['db_host'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if database exists
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$name'");
        $exists = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection successful',
            'database_exists' => $exists !== false
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function createDatabase() {
    $host = $_POST['db_host'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");
        
        // Save config
        $_SESSION['db_config'] = [
            'host' => $host,
            'name' => $name,
            'user' => $user,
            'pass' => $pass
        ];
        
        echo json_encode(['success' => true, 'message' => 'Database created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createTables() {
    if (!isset($_SESSION['db_config'])) {
        echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
        return;
    }
    
    $config = $_SESSION['db_config'];
    $dropExisting = $_POST['drop_existing'] === 'true';
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($dropExisting) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $tables = ['attachments', 'menus', 'content_versions', 'content', 'sessions', 'users', 'settings'];
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        // Create tables
        $sql = file_get_contents(__DIR__ . '/database_structure.sql');
        if (!$sql) {
            // Embedded SQL if file doesn't exist
            $sql = getDatabaseStructure();
        }
        
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Tables created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createAdmin() {
    if (!isset($_SESSION['db_config'])) {
        echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
        return;
    }
    
    $config = $_SESSION['db_config'];
    $username = $_POST['admin_user'] ?? '';
    $password = $_POST['admin_pass'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
        return;
    }
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$username, $hash, $email]);
        
        // Create sample content if requested
        if ($_POST['create_sample'] === 'true') {
            createSampleContent($pdo);
        }
        
        echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createSampleContent($pdo) {
    // Sample article
    $pdo->exec("INSERT INTO content (type, title, slug, body, excerpt, status, author_id, created_at) VALUES 
        ('article', 'Welcome to Dalthaus Photography', 'welcome', '<p>Welcome to your new photography portfolio!</p>', 
         'Your photography journey starts here', 'published', 1, NOW())");
    
    // Sample page
    $pdo->exec("INSERT INTO content (type, title, slug, body, status, author_id, created_at) VALUES 
        ('page', 'About', 'about', '<p>About Dalthaus Photography</p>', 'published', 1, NOW())");
}

function finalizeSetup() {
    if (!isset($_SESSION['db_config'])) {
        echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
        return;
    }
    
    $config = $_SESSION['db_config'];
    
    // Write config file
    $configContent = "<?php
/**
 * Dalthaus CMS Configuration
 * Generated: " . date('Y-m-d H:i:s') . "
 */

// Database Configuration
define('DB_HOST', '{$config['host']}');
define('DB_NAME', '{$config['name']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');

// Environment
define('ENV', 'production');

// Security
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_LIFETIME', 3600);
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// Upload Settings
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('CACHE_DIR', __DIR__ . '/../cache/');
define('LOG_DIR', __DIR__ . '/../logs/');

// Cache
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);

// Logging
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'error');
define('LOG_MAX_FILES', 30);
";
    
    file_put_contents(__DIR__ . '/includes/config.php', $configContent);
    
    // Fix permissions
    $dirs = ['uploads', 'cache', 'logs', 'temp'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        chmod($dir, 0755);
        file_put_contents("$dir/index.html", '');
    }
    
    // Clear session
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Setup completed successfully']);
}

function getDatabaseStructure() {
    return "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    last_activity INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('article', 'photobook', 'page') NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    body LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(500),
    status ENUM('draft', 'published') DEFAULT 'draft',
    author_id INT,
    sort_order INT DEFAULT 0,
    meta_description VARCHAR(255),
    meta_keywords VARCHAR(255),
    page_breaks JSON,
    page_count INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME,
    deleted_at DATETIME,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_type_status (type, status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    version_number INT NOT NULL,
    title VARCHAR(255),
    body LONGTEXT,
    excerpt TEXT,
    author_id INT,
    is_autosave BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_version (content_id, version_number),
    INDEX idx_content_version (content_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location ENUM('top', 'bottom', 'sidebar') NOT NULL,
    content_id INT,
    custom_url VARCHAR(500),
    custom_title VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    opens_new_tab BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    INDEX idx_location_order (location, sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_content_id (content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dalthaus CMS - Professional Setup Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #667eea;
        }

        .setup-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .setup-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .progress-bar {
            height: 4px;
            background: rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.3s ease;
        }

        .setup-body {
            padding: 40px;
        }

        .step {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }

        .step-item.completed:not(:last-child)::after {
            background: #667eea;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .step-item.active .step-number {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }

        .step-item.completed .step-number {
            background: #4caf50;
            color: white;
        }

        .step-item.completed .step-number::after {
            content: '✓';
            position: absolute;
        }

        .step-item.completed .step-number span {
            display: none;
        }

        .step-label {
            font-size: 12px;
            color: #999;
            text-align: center;
        }

        .step-item.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error {
            border-color: #f44336;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        .warning-message {
            background: #fff3e0;
            color: #e65100;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .test-connection {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: none;
        }

        .status-icon.success {
            background: #4caf50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .status-icon.error {
            background: #f44336;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .requirements-list {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .requirement-item:last-child {
            margin-bottom: 0;
        }

        .requirement-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .requirement-status.pass {
            background: #4caf50;
            color: white;
        }

        .requirement-status.fail {
            background: #f44336;
            color: white;
        }

        .completion-screen {
            text-align: center;
            padding: 40px;
        }

        .completion-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: white;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .completion-screen h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .completion-screen p {
            color: #666;
            margin-bottom: 10px;
        }

        .credentials-box {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }

        .credentials-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }

        .credential-label {
            color: #666;
            font-size: 14px;
        }

        .credential-value {
            font-weight: 600;
            color: #333;
            font-family: monospace;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            cursor: pointer;
            user-select: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-icon {
            font-size: 20px;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }

        .alert-warning {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ff9800;
        }

        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #2196f3;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .step-label {
                display: none;
            }
            
            .setup-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Main Setup Wizard -->
    <div class="setup-container">
        <div class="setup-header">
            <div class="logo">📸</div>
            <h1>Dalthaus Photography CMS</h1>
            <p>Professional Setup Wizard</p>
            <div class="progress-bar">
                <div class="progress-fill" id="progress"></div>
            </div>
        </div>
        
        <div class="setup-body">
            <!-- Step Indicators -->
            <div class="step-indicator">
                <div class="step-item active" data-step="1">
                    <div class="step-number"><span>1</span></div>
                    <div class="step-label">Requirements</div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-number"><span>2</span></div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-number"><span>3</span></div>
                    <div class="step-label">Admin User</div>
                </div>
                <div class="step-item" data-step="4">
                    <div class="step-number"><span>4</span></div>
                    <div class="step-label">Complete</div>
                </div>
            </div>
            
            <!-- Step 1: Requirements Check -->
            <div class="step active" id="step-1">
                <h2>System Requirements</h2>
                <p style="color: #666; margin-bottom: 30px;">Checking if your server meets all requirements...</p>
                
                <div class="requirements-list">
                    <?php
                    $requirements = [
                        'PHP Version 8.4+' => version_compare(PHP_VERSION, '8.4.0', '>='),
                        'PDO Extension' => extension_loaded('pdo'),
                        'PDO MySQL' => extension_loaded('pdo_mysql'),
                        'JSON Support' => function_exists('json_encode'),
                        'Session Support' => function_exists('session_start'),
                        'File Upload' => ini_get('file_uploads'),
                        'GD Library' => extension_loaded('gd'),
                        'Multibyte String' => extension_loaded('mbstring')
                    ];
                    
                    $all_pass = true;
                    foreach ($requirements as $name => $check):
                        $pass = $check ? true : false;
                        if (!$pass) $all_pass = false;
                    ?>
                    <div class="requirement-item">
                        <div class="requirement-status <?= $pass ? 'pass' : 'fail' ?>">
                            <?= $pass ? '✓' : '✗' ?>
                        </div>
                        <span><?= $name ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!$all_pass): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">⚠️</span>
                    <div>
                        <strong>Requirements Not Met</strong><br>
                        Please install missing extensions before continuing.
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="button-group">
                    <div></div>
                    <button class="btn btn-primary" onclick="nextStep()" <?= !$all_pass ? 'disabled' : '' ?>>
                        Continue
                    </button>
                </div>
            </div>
            
            <!-- Step 2: Database Configuration -->
            <div class="step" id="step-2">
                <h2>Database Configuration</h2>
                <p style="color: #666; margin-bottom: 30px;">Configure your MySQL database connection</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="db-host">Database Host</label>
                        <input type="text" id="db-host" value="localhost" placeholder="localhost">
                        <div class="help-text">Usually 'localhost' for shared hosting</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db-name">Database Name</label>
                        <input type="text" id="db-name" placeholder="dalthaus_cms" required>
                        <div class="help-text">Create this in your cPanel first</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="db-user">Database Username</label>
                        <input type="text" id="db-user" placeholder="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db-pass">Database Password</label>
                        <input type="password" id="db-pass" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="test-connection">
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        Test Connection
                    </button>
                    <div class="status-icon" id="connection-status"></div>
                    <span id="connection-message" style="color: #666; font-size: 14px;"></span>
                </div>
                
                <div class="checkbox-group" style="margin-top: 20px;">
                    <input type="checkbox" id="drop-existing" name="drop_existing">
                    <label for="drop-existing">Drop existing tables if they exist (clean install)</label>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="previousStep()">Back</button>
                    <button class="btn btn-primary" onclick="createDatabase()" id="create-db-btn" disabled>
                        Create Database
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Admin User -->
            <div class="step" id="step-3">
                <h2>Create Administrator Account</h2>
                <p style="color: #666; margin-bottom: 30px;">Set up your admin credentials</p>
                
                <div class="form-group">
                    <label for="admin-user">Username</label>
                    <input type="text" id="admin-user" placeholder="admin" required>
                    <div class="help-text">You'll use this to log in</div>
                </div>
                
                <div class="form-group">
                    <label for="admin-email">Email Address</label>
                    <input type="email" id="admin-email" placeholder="admin@example.com" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin-pass">Password</label>
                        <input type="password" id="admin-pass" placeholder="••••••••" required>
                        <div class="help-text">Minimum 8 characters</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin-pass-confirm">Confirm Password</label>
                        <input type="password" id="admin-pass-confirm" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="create-sample" checked>
                    <label for="create-sample">Create sample content (recommended)</label>
                </div>
                
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="previousStep()">Back</button>
                    <button class="btn btn-primary" onclick="createAdmin()">
                        Create Admin & Finalize
                    </button>
                </div>
            </div>
            
            <!-- Step 4: Complete -->
            <div class="step" id="step-4">
                <div class="completion-screen">
                    <div class="completion-icon">✓</div>
                    <h2>Setup Complete!</h2>
                    <p>Your Dalthaus Photography CMS has been successfully installed.</p>
                    
                    <div class="credentials-box">
                        <h3>Your Login Credentials</h3>
                        <div class="credential-item">
                            <span class="credential-label">Admin URL:</span>
                            <span class="credential-value">/admin/login.php</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Username:</span>
                            <span class="credential-value" id="final-username">admin</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Password:</span>
                            <span class="credential-value">••••••••</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <span class="alert-icon">⚠️</span>
                        <div>
                            <strong>Important Security Notice</strong><br>
                            Delete setup.php immediately for security!
                        </div>
                    </div>
                    
                    <button class="btn btn-primary" onclick="deleteSetup()">
                        Delete Setup File & Go to Admin
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        
        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progress').style.width = progress + '%';
            
            // Update step indicators
            document.querySelectorAll('.step-item').forEach((item, index) => {
                const stepNum = index + 1;
                item.classList.remove('active', 'completed');
                
                if (stepNum < currentStep) {
                    item.classList.add('completed');
                } else if (stepNum === currentStep) {
                    item.classList.add('active');
                }
            });
        }
        
        function showStep(step) {
            document.querySelectorAll('.step').forEach(s => {
                s.classList.remove('active');
            });
            document.getElementById('step-' + step).classList.add('active');
            updateProgress();
        }
        
        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }
        
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
        
        async function testConnection() {
            const btn = event.target;
            const statusIcon = document.getElementById('connection-status');
            const message = document.getElementById('connection-message');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Testing...';
            statusIcon.className = 'status-icon';
            message.textContent = '';
            
            const data = new FormData();
            data.append('action', 'test_connection');
            data.append('db_host', document.getElementById('db-host').value);
            data.append('db_name', document.getElementById('db-name').value);
            data.append('db_user', document.getElementById('db-user').value);
            data.append('db_pass', document.getElementById('db-pass').value);
            
            try {
                const response = await fetch('setup.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: data
                });
                
                const result = await response.json();
                
                if (result.success) {
                    statusIcon.className = 'status-icon success';
                    statusIcon.innerHTML = '✓';
                    message.textContent = 'Connection successful!';
                    message.style.color = '#4caf50';
                    document.getElementById('create-db-btn').disabled = false;
                } else {
                    statusIcon.className = 'status-icon error';
                    statusIcon.innerHTML = '✗';
                    message.textContent = result.message;
                    message.style.color = '#f44336';
                }
            } catch (error) {
                statusIcon.className = 'status-icon error';
                statusIcon.innerHTML = '✗';
                message.textContent = 'Connection failed: ' + error.message;
                message.style.color = '#f44336';
            }
            
            btn.disabled = false;
            btn.textContent = 'Test Connection';
        }
        
        async function createDatabase() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Creating...';
            
            const data = new FormData();
            data.append('action', 'create_database');
            data.append('db_host', document.getElementById('db-host').value);
            data.append('db_name', document.getElementById('db-name').value);
            data.append('db_user', document.getElementById('db-user').value);
            data.append('db_pass', document.getElementById('db-pass').value);
            
            try {
                const response = await fetch('setup.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: data
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Create tables
                    await createTables();
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Create Database';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                btn.disabled = false;
                btn.textContent = 'Create Database';
            }
        }
        
        async function createTables() {
            const data = new FormData();
            data.append('action', 'create_tables');
            data.append('drop_existing', document.getElementById('drop-existing').checked);
            
            const response = await fetch('setup.php', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: data
            });
            
            const result = await response.json();
            
            if (result.success) {
                nextStep();
            } else {
                alert('Error creating tables: ' + result.message);
            }
        }
        
        async function createAdmin() {
            const username = document.getElementById('admin-user').value;
            const email = document.getElementById('admin-email').value;
            const password = document.getElementById('admin-pass').value;
            const confirm = document.getElementById('admin-pass-confirm').value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters!');
                return;
            }
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Creating...';
            
            const data = new FormData();
            data.append('action', 'create_admin');
            data.append('admin_user', username);
            data.append('admin_email', email);
            data.append('admin_pass', password);
            data.append('create_sample', document.getElementById('create-sample').checked);
            
            try {
                const response = await fetch('setup.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: data
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('final-username').textContent = username;
                    await finalizeSetup();
                    nextStep();
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Create Admin & Finalize';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                btn.disabled = false;
                btn.textContent = 'Create Admin & Finalize';
            }
        }
        
        async function finalizeSetup() {
            const data = new FormData();
            data.append('action', 'finalize');
            
            await fetch('setup.php', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: data
            });
        }
        
        function deleteSetup() {
            if (confirm('Delete setup.php and go to admin panel?')) {
                // In production, make an AJAX call to delete the file
                window.location.href = '/admin/login.php';
            }
        }
    </script>
</body>
</html>