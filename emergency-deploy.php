<?php
/**
 * EMERGENCY DEPLOYMENT SCRIPT
 * Pulls latest code from GitHub and executes emergency fixes
 * 
 * Security: Only runs with correct token
 * URL: /emergency-deploy.php?token=emergency-dalthaus-2025
 */

// Security check
$validToken = 'emergency-dalthaus-2025';
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $validToken) {
    http_response_code(401);
    die("ACCESS DENIED - Invalid deployment token");
}

// Set content type for proper output
header('Content-Type: text/plain; charset=utf-8');

echo "EMERGENCY DEPLOYMENT STARTING\n";
echo str_repeat("=", 50) . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . $_SERVER['HTTP_HOST'] . "\n\n";

// Change to web root directory
$webRoot = __DIR__;
chdir($webRoot);

echo "Working directory: " . getcwd() . "\n\n";

// Step 1: Pull latest code from GitHub
echo "STEP 1: Pulling latest code from GitHub\n";
echo str_repeat("-", 40) . "\n";

$gitOutput = [];
$gitReturn = 0;

// Check if git is available and we're in a git repo
exec('git status 2>&1', $gitOutput, $gitReturn);
if ($gitReturn !== 0) {
    echo "ERROR: Not a git repository or git not available\n";
    echo "Attempting to download files directly...\n\n";
    
    // Download the emergency fix files directly from GitHub
    $files = [
        'EMERGENCY_MASTER_FIX.php',
        'EMERGENCY_FIX_01_DATABASE.php',
        'EMERGENCY_FIX_02_SECURITY.php', 
        'EMERGENCY_FIX_03_CLEANUP.php',
        'EMERGENCY_FIX_04_VERIFY.php'
    ];
    
    foreach ($files as $file) {
        $url = "https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/$file";
        echo "Downloading: $file\n";
        
        $content = file_get_contents($url);
        if ($content === false) {
            echo "ERROR: Failed to download $file\n";
            continue;
        }
        
        file_put_contents($file, $content);
        echo "✓ Downloaded: $file\n";
    }
} else {
    echo "Git repository detected, pulling latest changes...\n";
    
    // Pull latest changes
    exec('git pull origin main 2>&1', $pullOutput, $pullReturn);
    echo implode("\n", $pullOutput) . "\n";
    
    if ($pullReturn !== 0) {
        echo "WARNING: Git pull failed, but continuing...\n";
    }
}

echo "\n";

// Step 2: Execute the emergency master fix
echo "STEP 2: Executing Emergency Master Fix\n";
echo str_repeat("-", 40) . "\n";

if (!file_exists('EMERGENCY_MASTER_FIX.php')) {
    echo "ERROR: EMERGENCY_MASTER_FIX.php not found!\n";
    echo "Files in directory:\n";
    $files = glob('EMERGENCY_*.php');
    foreach ($files as $file) {
        echo "  - $file\n";
    }
    exit(1);
}

// Make sure the fix script is executable
chmod('EMERGENCY_MASTER_FIX.php', 0755);

// Execute the master fix
echo "Running EMERGENCY_MASTER_FIX.php...\n\n";

$fixOutput = [];
$fixReturn = 0;
exec('php EMERGENCY_MASTER_FIX.php 2>&1', $fixOutput, $fixReturn);

echo implode("\n", $fixOutput) . "\n";

echo "\n" . str_repeat("=", 50) . "\n";

if ($fixReturn === 0) {
    echo "✓ EMERGENCY DEPLOYMENT COMPLETED SUCCESSFULLY\n";
    echo "\nNext steps:\n";
    echo "1. Test site: https://dalthaus.net\n";
    echo "2. Test admin: https://dalthaus.net/admin/\n";
    echo "3. Verify setup.php blocked: https://dalthaus.net/setup.php\n";
} else {
    echo "✗ EMERGENCY DEPLOYMENT FAILED\n";
    echo "Exit code: $fixReturn\n";
    echo "Check the output above for errors\n";
}

echo "\nDeployment finished at: " . date('Y-m-d H:i:s') . "\n";