<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();

$pdo = Database::getInstance();
$message = '';
$error = '';
$userId = Auth::getUserId();

// Get current user info
$stmt = $pdo->prepare("SELECT username, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!password_verify($currentPassword, $user['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                if (Auth::updatePassword($userId, $newPassword)) {
                    $message = 'Password updated successfully';
                    logMessage('User ' . $userInfo['username'] . ' changed password', 'info');
                } else {
                    $error = 'Failed to update password';
                }
            }
        }
    }
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .profile-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .profile-info h3 {
            margin-top: 0;
            color: #333;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .password-form {
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .password-form h3 {
            margin-top: 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: #f0f8ff;
            border-left: 3px solid #3498db;
            border-radius: 4px;
        }
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 14px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: #555;
        }
        .password-requirements li {
            margin-bottom: 5px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s, background-color 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <nav class="admin-nav">
            <h1>CMS Admin</h1>
            <ul>
                <li><a href="/admin/">Dashboard</a></li>
                <li><a href="/admin/articles.php">Articles</a></li>
                <li><a href="/admin/photobooks.php">Photobooks</a></li>
                <li><a href="/admin/pages.php">Pages</a></li>
                <li><a href="/admin/menus.php">Menus</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/admin/profile.php" class="active">Profile</a></li>
                <li><a href="/admin/sort.php">Sort Content</a></li>
                <li><a href="/admin/import.php">Import Documents</a></li>
                <li><a href="/admin/logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-content">
            <h2>User Profile</h2>
            
            <div class="profile-container">
                <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="profile-info">
                    <h3>Account Information</h3>
                    <div class="info-row">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?= htmlspecialchars($userInfo['username']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Account Created:</span>
                        <span class="info-value"><?= date('F j, Y', strtotime($userInfo['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Login:</span>
                        <span class="info-value"><?= date('F j, Y g:i A', $_SESSION['last_activity'] ?? time()) ?></span>
                    </div>
                </div>
                
                <form method="post" class="password-form" id="password-form">
                    <h3>Change Password</h3>
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <div class="password-strength" id="strength-meter" style="display: none;">
                            <div class="password-strength-bar" id="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strength-text"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        <span id="password-match" style="display: none; font-size: 12px;"></span>
                    </div>
                    
                    <div class="password-requirements">
                        <h4>Password Requirements:</h4>
                        <ul>
                            <li>Minimum 8 characters long</li>
                            <li>Recommended: Mix of uppercase and lowercase letters</li>
                            <li>Recommended: Include numbers and special characters</li>
                            <li>Avoid common words or patterns</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                        <a href="/admin/" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
    // Password strength meter
    document.getElementById('new_password').addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthMeter = document.getElementById('strength-meter');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        if (password.length === 0) {
            strengthMeter.style.display = 'none';
            strengthText.textContent = '';
            return;
        }
        
        strengthMeter.style.display = 'block';
        
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Character variety checks
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        // Remove all strength classes
        strengthBar.className = 'password-strength-bar';
        
        if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#dc3545';
        } else if (strength <= 4) {
            strengthBar.classList.add('strength-medium');
            strengthText.textContent = 'Medium strength';
            strengthText.style.color = '#ffc107';
        } else {
            strengthBar.classList.add('strength-strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#28a745';
        }
    });
    
    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = e.target.value;
        const matchIndicator = document.getElementById('password-match');
        
        if (confirmPassword.length === 0) {
            matchIndicator.style.display = 'none';
            return;
        }
        
        matchIndicator.style.display = 'inline';
        
        if (newPassword === confirmPassword) {
            matchIndicator.textContent = '✓ Passwords match';
            matchIndicator.style.color = '#28a745';
        } else {
            matchIndicator.textContent = '✗ Passwords do not match';
            matchIndicator.style.color = '#dc3545';
        }
    });
    
    // Form validation
    document.getElementById('password-form').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }
    });
    </script>
</body>
</html>