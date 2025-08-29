<?php
// Manual deployment script - pulls latest changes from GitHub
// Access with token: deploy-20250829

$token = $_GET['token'] ?? '';
$expectedToken = 'deploy-' . date('Ymd');

if ($token !== $expectedToken) {
    http_response_code(403);
    die("Invalid token. Use: $expectedToken");
}

echo "<pre>";
echo "Manual Deployment Script\n";
echo "========================\n\n";

// Git pull
echo "Pulling latest changes from GitHub...\n";
exec('cd /home/dalthaus/public_html && git pull origin main 2>&1', $output, $return_var);
echo implode("\n", $output) . "\n\n";

if ($return_var === 0) {
    echo "✅ Git pull successful!\n\n";
    
    // Run the maintenance fix
    echo "Running maintenance fix...\n";
    include __DIR__ . '/fix-maintenance.php';
} else {
    echo "❌ Git pull failed with code: $return_var\n";
}

echo "</pre>";