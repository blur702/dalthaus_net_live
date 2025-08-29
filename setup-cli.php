<?php
/**
 * CLI Setup Script for Dalthaus Photography CMS
 * Run: php setup.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   DALTHAUS PHOTOGRAPHY CMS                        ║\n";
echo "║                    Professional Setup Wizard                      ║\n";
echo "║                         Version 4.0.0                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.4.0', '<')) {
    echo "❌ Error: PHP 8.4+ is required (current: " . PHP_VERSION . ")\n";
    exit(1);
}

echo "✅ PHP " . PHP_VERSION . " detected\n";
echo "✅ Running in CLI mode\n\n";

// Check required extensions
$required = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missing = [];

foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    echo "❌ Missing required extensions: " . implode(', ', $missing) . "\n";
    exit(1);
}

echo "✅ All required extensions installed\n\n";

// Step 1: Database Configuration
echo "═══════════════════════════════════════════════════════════════════\n";
echo "STEP 1: DATABASE CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Check for existing config
$configFile = __DIR__ . '/includes/config.php';
$useExisting = false;

if (file_exists($configFile)) {
    echo "📝 Existing configuration found.\n";
    echo "Use existing database configuration? (y/n): ";
    $useExisting = strtolower(trim(fgets(STDIN))) === 'y';
}

if ($useExisting) {
    require_once $configFile;
    $dbHost = DB_HOST;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;
} else {
    echo "Database Host [localhost]: ";
    $dbHost = trim(fgets(STDIN)) ?: 'localhost';
    
    echo "Database Name: ";
    $dbName = trim(fgets(STDIN));
    
    echo "Database Username: ";
    $dbUser = trim(fgets(STDIN));
    
    echo "Database Password: ";
    system('stty -echo');
    $dbPass = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
}

// Test connection
echo "\n🔄 Testing database connection...\n";
try {
    $dsn = "mysql:host=$dbHost;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connection successful!\n";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "⚠️  Database '$dbName' already exists.\n";
        echo "Drop existing database and start fresh? (y/n): ";
        if (strtolower(trim(fgets(STDIN))) === 'y') {
            $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
            echo "✅ Existing database dropped.\n";
        }
    }
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    echo "✅ Database '$dbName' ready.\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Create Tables
echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "STEP 2: CREATE DATABASE TABLES\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Check for existing tables
$stmt = $pdo->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($existingTables)) {
    echo "⚠️  Found existing tables: " . implode(', ', $existingTables) . "\n";
    echo "Drop all existing tables? (y/n): ";
    if (strtolower(trim(fgets(STDIN))) === 'y') {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($existingTables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "✅ Existing tables dropped.\n";
    }
}

echo "🔄 Creating tables...\n";

// Create tables
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'content' => "CREATE TABLE IF NOT EXISTS content (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'content_versions' => "CREATE TABLE IF NOT EXISTS content_versions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'menus' => "CREATE TABLE IF NOT EXISTS menus (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'attachments' => "CREATE TABLE IF NOT EXISTS attachments (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'settings' => "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $name => $sql) {
    $pdo->exec($sql);
    echo "  ✅ Table '$name' created\n";
}

// Step 3: Create Admin User
echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "STEP 3: CREATE ADMINISTRATOR ACCOUNT\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Admin Username [admin]: ";
$adminUser = trim(fgets(STDIN)) ?: 'admin';

echo "Admin Email: ";
$adminEmail = trim(fgets(STDIN));

echo "Admin Password (min 8 chars): ";
system('stty -echo');
$adminPass = trim(fgets(STDIN));
system('stty echo');
echo "\n";

if (strlen($adminPass) < 8) {
    echo "❌ Password must be at least 8 characters!\n";
    exit(1);
}

$hash = password_hash($adminPass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
$stmt->execute([$adminUser, $hash, $adminEmail]);
echo "✅ Admin user created successfully!\n";

// Sample content
echo "\nCreate sample content? (y/n): ";
if (strtolower(trim(fgets(STDIN))) === 'y') {
    $pdo->exec("INSERT INTO content (type, title, slug, body, excerpt, status, author_id, created_at) VALUES 
        ('article', 'Welcome to Dalthaus Photography', 'welcome', '<p>Welcome to your new photography portfolio!</p>', 
         'Your photography journey starts here', 'published', 1, NOW())");
    
    $pdo->exec("INSERT INTO content (type, title, slug, body, status, author_id, created_at) VALUES 
        ('page', 'About', 'about', '<p>About Dalthaus Photography</p>', 'published', 1, NOW())");
    
    echo "✅ Sample content created!\n";
}

// Step 4: Write Configuration
echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "STEP 4: FINALIZE SETUP\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "🔄 Writing configuration file...\n";

$configContent = "<?php
/**
 * Dalthaus CMS Configuration
 * Generated: " . date('Y-m-d H:i:s') . "
 */

// Database Configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

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

file_put_contents($configFile, $configContent);
echo "✅ Configuration file written!\n";

// Fix permissions
echo "🔄 Setting directory permissions...\n";
$dirs = ['uploads', 'cache', 'logs', 'temp'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    chmod($dir, 0755);
    file_put_contents("$dir/index.html", '');
    echo "  ✅ $dir/ - permissions set\n";
}

// Final message
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    🎉 SETUP COMPLETE! 🎉                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Your Dalthaus Photography CMS has been successfully installed!\n";
echo "\n";
echo "📋 LOGIN CREDENTIALS:\n";
echo "   URL:      /admin/login.php\n";
echo "   Username: $adminUser\n";
echo "   Password: ••••••••\n";
echo "\n";
echo "⚠️  IMPORTANT SECURITY STEPS:\n";
echo "   1. Delete setup.php immediately: rm setup.php\n";
echo "   2. Delete setup-cli.php: rm setup-cli.php\n";
echo "   3. Change admin password after first login\n";
echo "\n";
echo "🚀 Ready to launch!\n";
echo "\n";

// Self-destruct option
echo "Delete setup files now? (y/n): ";
if (strtolower(trim(fgets(STDIN))) === 'y') {
    unlink(__FILE__);
    if (file_exists('setup.php')) {
        unlink('setup.php');
    }
    echo "✅ Setup files deleted for security!\n";
}

echo "\n";