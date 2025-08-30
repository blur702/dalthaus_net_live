#!/usr/bin/env php
<?php
/**
 * EMERGENCY MASTER FIX SCRIPT
 * Runs all emergency fixes in sequence
 * 
 * USAGE: php EMERGENCY_MASTER_FIX.php
 * 
 * This will:
 * 1. Fix database connection
 * 2. Secure setup.php and enable HTTPS
 * 3. Remove all debug/test files
 * 4. Verify all fixes
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║          EMERGENCY PRODUCTION FIX - DALTHAUS.NET          ║\n";
echo "║                    CRITICAL P0 INCIDENT                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Starting emergency fixes at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

$startTime = microtime(true);

// Run each fix in sequence
$fixes = [
    'EMERGENCY_FIX_01_DATABASE.php' => 'Database Connection Fix',
    'EMERGENCY_FIX_02_SECURITY.php' => 'Security Protection Fix',
    'EMERGENCY_FIX_03_CLEANUP.php' => 'Debug File Cleanup',
    'EMERGENCY_FIX_04_VERIFY.php' => 'Verification'
];

foreach ($fixes as $file => $description) {
    echo "▶ Running: $description\n";
    echo str_repeat("-", 60) . "\n";
    
    $fixFile = __DIR__ . '/' . $file;
    if (!file_exists($fixFile)) {
        echo "ERROR: $file not found!\n";
        echo "Please ensure all EMERGENCY_FIX_*.php files are present.\n";
        exit(1);
    }
    
    // Execute the fix
    $output = [];
    $returnCode = 0;
    exec("php " . escapeshellarg($fixFile) . " 2>&1", $output, $returnCode);
    
    // Display output
    echo implode("\n", $output) . "\n";
    
    if ($returnCode !== 0) {
        echo "\n✗ ERROR: $description failed with code $returnCode\n";
        echo "Fix the issue above before continuing.\n";
        exit($returnCode);
    }
    
    echo "\n";
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                 EMERGENCY FIX COMPLETED                   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total execution time: {$duration} seconds\n";
echo "\n";
echo "NEXT STEPS:\n";
echo "═══════════\n";
echo "1. Test the site immediately: https://dalthaus.net\n";
echo "2. Verify admin login: https://dalthaus.net/admin/\n";
echo "3. Check that setup.php is blocked: https://dalthaus.net/setup.php\n";
echo "4. Once confirmed working, clean up:\n";
echo "   php -r \"array_map('unlink', glob('EMERGENCY_*.php'));\"\n";
echo "   rm -rf backup_debug_files_*\n";
echo "\n";
echo "If issues persist, check the verification report above.\n";
echo str_repeat("=", 60) . "\n\n";