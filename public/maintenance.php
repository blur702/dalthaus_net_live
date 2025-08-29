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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .maintenance-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .maintenance-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .maintenance-message {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }
        
        .maintenance-message p {
            margin-bottom: 15px;
        }
        
        .maintenance-message p:last-child {
            margin-bottom: 0;
        }
        
        .home-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .home-link:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 600px) {
            .maintenance-container {
                padding: 40px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            🔧
        </div>
        <h1>Under Maintenance</h1>
        <div class="maintenance-message">
            <?= $message ?>
        </div>
        <a href="/" class="home-link">Try Again</a>
    </div>
</body>
</html>