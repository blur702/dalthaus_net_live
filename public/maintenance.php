<?php
/**
 * Maintenance Mode Page
 * 
 * Displays a maintenance message to visitors when the site is in maintenance mode.
 * This page is shown for all public routes when maintenance mode is enabled.
 * Admin area remains accessible via direct URLs.
 */

// Set appropriate HTTP status code
http_response_code(503);

// Set retry header to suggest checking back in an hour
header('Retry-After: 3600');

// Get the maintenance message from settings
require_once __DIR__ . '/../includes/database.php';
$pdo = Database::getInstance();

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['maintenance_message']);
$message = $stmt->fetchColumn();

// Default message if none is set
if (empty($message)) {
    $message = '<p>The site is currently undergoing maintenance. Please check back soon.</p>';
}

// Get site title for the page
$stmt->execute(['site_title']);
$siteTitle = $stmt->fetchColumn() ?: 'Dalthaus.net';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - <?= htmlspecialchars($siteTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?= time() ?>">
    <style>
        /* Maintenance-specific styles that complement public.css */
        .maintenance-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .maintenance-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .maintenance-box {
            max-width: 600px;
            width: 100%;
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .maintenance-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: #ecf0f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #7f8c8d;
        }
        
        .maintenance-title {
            font-family: 'Arimo', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .maintenance-message {
            font-family: 'Gelasio', serif;
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }
        
        .maintenance-actions {
            margin-top: 30px;
        }
        
        .maintenance-button {
            display: inline-block;
            margin: 0 10px;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-family: 'Arimo', sans-serif;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .maintenance-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .maintenance-button.secondary {
            background: #95a5a6;
        }
        
        .maintenance-button.secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="page-wrapper maintenance-wrapper">
        <!-- Use the standard site header -->
        <header class="site-header">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="site-title">
                        <a href="/"><?= htmlspecialchars($siteTitle) ?></a>
                    </h1>
                    <p class="site-motto">Professional Photography Portfolio</p>
                </div>
            </div>
        </header>
        
        <!-- Maintenance content -->
        <main class="maintenance-content">
            <div class="maintenance-box">
                <div class="maintenance-icon">
                    🔧
                </div>
                <h2 class="maintenance-title">Under Maintenance</h2>
                <div class="maintenance-message">
                    <?= $message ?>
                </div>
                <div class="maintenance-actions">
                    <a href="/" class="maintenance-button">Try Again</a>
                    <a href="/admin/login.php" class="maintenance-button secondary">Admin Login</a>
                </div>
            </div>
        </main>
        
        <!-- Use the standard site footer -->
        <footer class="site-footer">
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>