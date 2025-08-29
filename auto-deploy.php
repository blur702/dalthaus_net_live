<?php
/**
 * Auto-Deploy Script - Pulls latest code from GitHub
 * Also enables/disables maintenance mode
 */

$action = $_GET['action'] ?? 'status';
$token = $_GET['token'] ?? '';

// Verify token
if ($token !== 'deploy-' . date('Ymd')) {
    die('Invalid token. Use: deploy-' . date('Ymd'));
}

// Set up basic page
echo "<!DOCTYPE html>
<html>
<head>
    <title>Deployment Control</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
        pre { background: #000; padding: 20px; border: 1px solid #0f0; overflow-x: auto; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .button { display: inline-block; padding: 10px 20px; background: #0f0; color: #000; text-decoration: none; margin: 10px; }
        .button:hover { background: #0a0; }
    </style>
</head>
<body>
<h1>Deployment Control Panel</h1>
<pre>";

// Handle actions
switch ($action) {
    case 'pull':
        echo "=== PULLING LATEST CODE FROM GITHUB ===\n\n";
        
        // Try to pull from git
        $commands = [
            'pwd' => 'Current directory',
            'git remote -v' => 'Git remotes',
            'git fetch origin main' => 'Fetching latest',
            'git reset --hard origin/main' => 'Resetting to origin/main',
            'git pull origin main' => 'Pulling changes'
        ];
        
        foreach ($commands as $cmd => $desc) {
            echo "$desc:\n";
            exec("cd /home/dalthaus/public_html && $cmd 2>&1", $output, $return);
            foreach ($output as $line) {
                echo "  $line\n";
            }
            $output = [];
            echo "\n";
        }
        
        echo "✅ Deployment complete!\n\n";
        echo "The site should now have the latest code from GitHub.\n";
        break;
        
    case 'maintenance_on':
        echo "=== ENABLING MAINTENANCE MODE ===\n\n";
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'maintenance_mode'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '1')");
                $stmt->execute();
            }
            
            echo "✅ Maintenance mode ENABLED\n";
            echo "Visitors will see the maintenance page.\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        break;
        
    case 'maintenance_off':
        echo "=== DISABLING MAINTENANCE MODE ===\n\n";
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
                $stmt->execute();
            }
            
            // Clear cache
            $cacheDir = __DIR__ . '/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== 'index.html') {
                        unlink($file);
                    }
                }
            }
            
            echo "✅ Maintenance mode DISABLED\n";
            echo "✅ Cache cleared\n";
            echo "The site is now live.\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        break;
        
    default:
        echo "=== DEPLOYMENT STATUS ===\n\n";
        
        // Check git status
        echo "Git Status:\n";
        exec('cd /home/dalthaus/public_html && git status --short 2>&1', $output);
        if (empty($output)) {
            echo "  ✅ Working directory clean\n";
        } else {
            foreach ($output as $line) {
                echo "  $line\n";
            }
        }
        echo "\n";
        
        // Check maintenance mode
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
            $stmt->execute();
            $maintenance = $stmt->fetchColumn();
            
            echo "Maintenance Mode: " . ($maintenance === '1' ? '🔴 ENABLED' : '🟢 DISABLED') . "\n\n";
        } catch (Exception $e) {
            echo "Database Error: " . $e->getMessage() . "\n\n";
        }
        
        // Show last commit
        echo "Last Commit:\n";
        exec('cd /home/dalthaus/public_html && git log -1 --oneline 2>&1', $output);
        foreach ($output as $line) {
            echo "  $line\n";
        }
        break;
}

echo "</pre>

<div style='margin-top: 30px;'>
    <h2>Actions:</h2>
    <a href='?action=status&token=$token' class='button'>Check Status</a>
    <a href='?action=pull&token=$token' class='button'>Pull Latest Code</a>
    <a href='?action=maintenance_on&token=$token' class='button'>Enable Maintenance</a>
    <a href='?action=maintenance_off&token=$token' class='button'>Disable Maintenance</a>
    <a href='/' class='button'>View Site</a>
</div>

</body>
</html>";
?>