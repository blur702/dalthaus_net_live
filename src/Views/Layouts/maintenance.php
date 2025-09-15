<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $this->escape($page_title . ' - ' . ($settings['site_title'] ?? 'CMS')) : $this->escape($settings['site_title'] ?? 'CMS') ?></title>
    
    <meta name="robots" content="noindex, nofollow">
    
    <?php if (!empty($settings['favicon'])): ?>
    <link rel="icon" href="<?= $this->escape('/uploads/settings/' . $settings['favicon']) ?>">
    <?php endif; ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS for maintenance page -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgb(248, 248, 248);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgb(20, 20, 20);
        }
        
        .maintenance-container {
            background: white;
            border-radius: 8px;
            padding: 3rem 2rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e5e5;
        }
        
        .maintenance-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .maintenance-title {
            color: rgb(20, 20, 20);
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .maintenance-message {
            color: #666;
            font-size: 1.125rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .retry-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 8px;
            color: #666;
            font-size: 0.875rem;
            border: 1px solid #e5e5e5;
        }
        
        .site-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 640px) {
            .maintenance-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .maintenance-title {
                font-size: 2rem;
            }
            
            .maintenance-icon {
                font-size: 3rem;
            }
            
        }
    </style>
</head>
<body>
    <?= $content ?>
    
    <script>
        // Auto-refresh page every 5 minutes to check if maintenance mode is disabled
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes
        
    </script>
</body>
</html>