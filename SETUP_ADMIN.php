<?php
/**
 * Setup Admin User and Required Tables
 * This ensures the admin login works properly
 */

echo "<h1>Setting Up Admin Access</h1><pre>";

// Direct database config
$db_host = 'localhost';
$db_name = 'dalthaus_photocms';
$db_user = 'dalthaus_photocms';
$db_pass = 'f-I*GSo^Urt*k*&#';

// Connect to database
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

echo "✅ Database connected successfully\n\n";

// Create users table if it doesn't exist
echo "Creating users table...\n";
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'editor') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "✅ Users table ready\n";
} else {
    echo "❌ Error creating users table: " . mysqli_error($conn) . "\n";
}

// Create sessions table for auth
echo "\nCreating sessions table...\n";
$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "✅ Sessions table ready\n";
} else {
    echo "❌ Error creating sessions table: " . mysqli_error($conn) . "\n";
}

// Check if admin user exists
echo "\nChecking for admin user...\n";
$result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'admin'");

if ($result && mysqli_num_rows($result) > 0) {
    echo "✅ Admin user already exists\n";
    
    // Update password to ensure it matches
    $hashed_password = password_hash('130Bpm', PASSWORD_DEFAULT);
    $update_sql = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, 's', $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Admin password updated to: 130Bpm\n";
    }
    mysqli_stmt_close($stmt);
    
} else {
    // Create admin user
    echo "Creating admin user...\n";
    $username = 'admin';
    $password = password_hash('130Bpm', PASSWORD_DEFAULT);
    $email = 'admin@dalthaus.net';
    
    $insert_sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, 'sss', $username, $password, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Admin user created successfully!\n";
        echo "   Username: admin\n";
        echo "   Password: 130Bpm\n";
    } else {
        echo "❌ Error creating admin user: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
}

// Also create kevin user as backup
echo "\nChecking for kevin user...\n";
$result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'kevin'");

if ($result && mysqli_num_rows($result) == 0) {
    $username = 'kevin';
    $password = password_hash('(130Bpm)', PASSWORD_DEFAULT);
    $email = 'kevin@dalthaus.net';
    
    $insert_sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, 'sss', $username, $password, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Kevin user created as backup\n";
        echo "   Username: kevin\n";
        echo "   Password: (130Bpm)\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "✅ Kevin user already exists\n";
}

// Create other required tables
echo "\nEnsuring all required tables exist...\n";

// Content table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('article', 'photobook', 'page') NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    author VARCHAR(100) DEFAULT 'Don Althaus',
    body LONGTEXT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL
)");
echo "✅ Content table ready\n";

// Settings table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
echo "✅ Settings table ready\n";

// Menus table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS menus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location ENUM('top', 'bottom') NOT NULL,
    content_id INT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
)");
echo "✅ Menus table ready\n";

// Insert default settings
mysqli_query($conn, "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
    ('site_title', 'Dalthaus Photography'),
    ('maintenance_mode', '0'),
    ('timezone', 'America/New_York')");
echo "✅ Default settings configured\n";

// Test the admin authentication
echo "\n==================================\n";
echo "TESTING AUTHENTICATION\n";
echo "==================================\n";

// Test password verification
$test_result = mysqli_query($conn, "SELECT password FROM users WHERE username = 'admin'");
if ($test_result && $row = mysqli_fetch_assoc($test_result)) {
    if (password_verify('130Bpm', $row['password'])) {
        echo "✅ Admin password verification: WORKING\n";
    } else {
        echo "❌ Admin password verification: FAILED\n";
    }
}

mysqli_close($conn);

echo "\n==================================\n";
echo "🎉 SETUP COMPLETE!\n";
echo "==================================\n\n";

echo "Admin users created:\n";
echo "1. Username: admin / Password: 130Bpm\n";
echo "2. Username: kevin / Password: (130Bpm)\n\n";

echo "You can now login at:\n";
echo "<a href='/admin/login.php' style='font-size: 20px; font-weight: bold;'>🔐 Admin Login</a>\n\n";

echo "Homepage:\n";
echo "<a href='/' style='font-size: 20px; font-weight: bold;'>🏠 View Site</a>\n";

echo "</pre>";
?>