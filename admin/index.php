<?php
// Admin area entry point for Dalthaus Photography
session_start();
error_reporting(0); // Suppress errors for production

// Include files safely and initialize database
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
}
if (file_exists('../includes/database.php')) {
    require_once '../includes/database.php';
}
if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
} else if (file_exists('../functions-fixed.php')) {
    require_once '../functions-fixed.php';
}

// Initialize database connection if not already done
if (!isset($pdo) && class_exists('Database')) {
    try {
        $pdo = Database::getInstance();
    } catch (Exception $e) {
        // Database connection failed, continue with defaults
        $pdo = null;
    }
}

// Simple authentication check
$is_authenticated = false;
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    $is_authenticated = true;
}

// Handle login attempt
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_authenticated) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Basic authentication (in production, use proper password hashing)
    if ($username === 'admin' && $password === 'admin2024') {
        $_SESSION['admin_authenticated'] = true;
        $is_authenticated = true;
        header('Location: /admin');
        exit;
    } else {
        $login_error = 'Invalid username or password.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin');
    exit;
}

// Set default values
$site_title = 'Dalthaus Photography - Admin';
$site_motto = 'Capturing moments, telling stories through light and shadow';

// Try to get from settings if available
if (function_exists('getSetting') && isset($pdo) && $pdo) {
    try {
        $title_from_db = getSetting('site_title', '');
        if ($title_from_db) {
            $site_title = $title_from_db . ' - Admin';
        }
        
        $motto_from_db = getSetting('site_motto', '');
        if ($motto_from_db) {
            $site_motto = $motto_from_db;
        }
    } catch (Exception $e) {
        // Error getting settings, use defaults
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&family=Gelasio:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Gelasio', serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        /* Header Styles */
        .header {
            padding: 30px 20px;
            text-align: center;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .site-title {
            font-family: 'Arimo', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .site-slogan {
            font-size: 0.9rem;
            color: #7f8c8d;
            font-style: italic;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .admin-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .page-title {
            font-family: 'Arimo', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }
        
        /* Login Form */
        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-family: 'Arimo', sans-serif;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-submit {
            width: 100%;
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            font-family: 'Arimo', sans-serif;
        }
        
        .form-submit:hover {
            background: #2980b9;
        }
        
        /* Admin Dashboard */
        .admin-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .admin-nav-item {
            display: block;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: #2c3e50;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .admin-nav-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .admin-nav-item h3 {
            font-family: 'Arimo', sans-serif;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .admin-nav-item p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .logout-link {
            display: inline-block;
            color: #dc3545;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 20px;
            padding: 8px 16px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .logout-link:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Footer */
        .footer {
            background: white;
            color: #7f8c8d;
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .admin-container {
                padding: 20px;
            }
            
            .site-title {
                font-size: 1.6rem;
            }
            
            .admin-nav {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <h1 class="site-title">Dalthaus Photography</h1>
        <p class="site-slogan"><?php echo htmlspecialchars($site_motto); ?></p>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="admin-container">
            <?php if (!$is_authenticated): ?>
                <!-- Login Form -->
                <h1 class="page-title">Admin Login</h1>
                
                <?php if ($login_error): ?>
                    <div class="message error">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="/admin">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="form-submit">Login</button>
                </form>
                
                <div style="text-align: center; margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 6px;">
                    <small style="color: #666;">
                        <strong>Demo Login:</strong> username: admin, password: admin2024
                    </small>
                </div>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <h1 class="page-title">Admin Dashboard</h1>
                
                <div class="message success">
                    Welcome to the admin area! The admin functionality is now accessible.
                </div>
                
                <div class="admin-nav">
                    <a href="../" class="admin-nav-item">
                        <h3>View Website</h3>
                        <p>Return to the main website</p>
                    </a>
                    
                    <a href="#" class="admin-nav-item" onclick="alert('Content management coming soon!');">
                        <h3>Manage Content</h3>
                        <p>Add and edit articles, photobooks</p>
                    </a>
                    
                    <a href="#" class="admin-nav-item" onclick="alert('Settings management coming soon!');">
                        <h3>Site Settings</h3>
                        <p>Update site title, motto, and configuration</p>
                    </a>
                    
                    <a href="#" class="admin-nav-item" onclick="alert('Media management coming soon!');">
                        <h3>Media Library</h3>
                        <p>Upload and manage images</p>
                    </a>
                </div>
                
                <div style="text-align: center;">
                    <a href="/admin?logout=1" class="logout-link">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Dalthaus Photography. All rights reserved.</p>
    </footer>
</body>
</html>