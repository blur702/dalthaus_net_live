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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .maintenance-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem 2rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            color: #333;
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
        
        .login-link {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .login-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        .retry-info {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            color: #666;
            font-size: 0.875rem;
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
            
            .login-link {
                display: block;
                margin-bottom: 1rem;
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
        
        // Show loading indicator when clicking admin login
        document.addEventListener('DOMContentLoaded', function() {
            const loginLinks = document.querySelectorAll('.login-link');
            loginLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    this.innerHTML = 'Loading...';
                    this.style.opacity = '0.7';
                });
            });
        });
    </script>
</body>
</html>