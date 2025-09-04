<?php
/**
 * Admin Settings Page
 */

session_start();

$isLoggedIn = isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true;

if (!$isLoggedIn && isset($_POST["login"])) {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    
    if ($username === "admin" && $password === "dalthaus2024") {
        $_SESSION["admin_logged_in"] = true;
        $isLoggedIn = true;
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    } else {
        $loginError = "Invalid credentials";
    }
}

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$message = "";
if ($isLoggedIn && isset($_POST["update_settings"])) {
    $message = "Settings updated successfully!";
}

$page_title = "Admin Settings - Dalthaus Photography";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-logout {
            background: #e74c3c;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .login-form {
            max-width: 400px;
            margin: 100px auto;
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .nav-links a {
            color: #3498db;
            text-decoration: none;
            margin-right: 20px;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .admin-nav {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <div class="container">
            <div class="login-form">
                <div class="card">
                    <h2>Admin Login</h2>
                    
                    <?php if (isset($loginError)): ?>
                        <div class="message error"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" name="login" class="btn">Login</button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="header">
                <div class="admin-nav">
                    <div>
                        <h1>Admin Settings</h1>
                        <p>Manage your photography website settings</p>
                    </div>
                    <div>
                        <div class="nav-links">
                            <a href="/">View Site</a>
                            <a href="/admin">Dashboard</a>
                        </div>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="logout" class="btn btn-logout">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Site Settings</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="site_title">Site Title:</label>
                        <input type="text" id="site_title" name="site_title" value="Dalthaus Photography">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Site Description:</label>
                        <textarea id="site_description" name="site_description">Professional photography services and artistic visual storytelling</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Contact Email:</label>
                        <input type="email" id="contact_email" name="contact_email" value="info@dalthaus.net">
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn">Update Settings</button>
                </form>
            </div>
            
            <div class="card">
                <h2>System Information</h2>
                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Server:</strong> <?php echo $_SERVER["SERVER_SOFTWARE"] ?? "Unknown"; ?></p>
                <p><strong>Document Root:</strong> <?php echo $_SERVER["DOCUMENT_ROOT"]; ?></p>
                <p><strong>Current Time:</strong> <?php echo date("Y-m-d H:i:s T"); ?></p>
                <p><strong>Memory Usage:</strong> <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?> MB</p>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>