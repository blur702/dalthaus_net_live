#!/usr/bin/env php
<?php
/**
 * EMERGENCY FIX #3: CLEANUP DEBUG FILES
 * Priority: P0 - SECURITY EXPOSURE
 * Remove all debug, test, and deployment files from production
 */

echo "\n=== EMERGENCY CLEANUP - REMOVING DEBUG FILES ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$rootDir = __DIR__;
$deletedCount = 0;
$backupDir = __DIR__ . '/backup_debug_files_' . time();

// Create backup directory for critical files
echo "1. Creating backup directory: " . basename($backupDir) . "\n";
mkdir($backupDir);

// List of files to remove (based on directory listing)
$filesToDelete = [
    // Debug and test files
    'debug-auth-response.js',
    'debug-css-loading.php',
    'debug-index.php',
    'setup-debug.php',
    'test-authentication.js',
    'test-css-loading.php',
    'test-css.php',
    'test-php.php',
    'test-settings.php',
    'QUICK_TEST.php',
    
    // Deployment files
    'DEPLOY_THIS_FILE.php',
    'FINAL_DEPLOYMENT_SCRIPT.php',
    'UPLOAD_AND_RUN_THIS.php',
    'auto-deploy.php',
    'deploy-and-test.php',
    'deploy-with-css-fix.php',
    'manual-deploy.php',
    'master-deploy.php',
    'production-fix.php',
    'PRODUCTION_CLEANUP.php',
    'PRODUCTION_SETUP.php',
    
    // Emergency and fix files (except our current ones)
    'emergency-fix.php',
    'emergency-fix-production.php',
    'enhance-error-handling.php',
    'fix-database-schema.php',
    'fix-maintenance.php',
    'FORCE_CSS_FIX.php',
    'css-fix.php',
    'css-delivery.php',
    'quick-fix.php',
    
    // Remote access files (CRITICAL SECURITY RISK)
    'remote-agent.php',
    'file-agent.php',
    'git-pull.php',
    
    // Test endpoint files
    'e2e-endpoint.php',
    'simple-index.php',
    
    // Feature test files
    'feature-checklist.php',
    'final-validation.php',
    'validate-deployment.php',
    'production-test-suite.php',
    
    // Screenshot and capture files
    'capture-screenshots.php',
    
    // Database modification files
    'database_fixes.sql',
    'create-havasu-article.php',
    
    // Setup CLI (alternative setup)
    'setup-cli.php',
    
    // Maintenance control files
    'set-maintenance.php',
    
    // Shell scripts (shouldn't be web accessible)
    'deploy.sh',
    'cleanup-for-production.sh',
    'fix-permissions.sh',
    'push-to-github.sh',
    'set_permissions.sh',
    
    // Python scripts in root (should be in scripts/)
    'remote-debug.py',
    
    // Windows batch files
    'install-mysql-admin.bat',
    'install-mysql.ps1',
    
    // JavaScript test files in root
    'comprehensive-e2e-test.js',
    'comprehensive-production-test.js',
    'detailed-feature-test.js',
    'extract-error-message.js',
    'final-comprehensive-test.js',
    'investigate-homepage.js',
    'production-http-test.js',
    'production-test-suite.js',
    
    // Test reports (can contain sensitive info)
    'test-report-1756488024080.md',
    'test-report-1756491150118.md',
    'test-report-1756491837926.md',
    'test-report-1756500285534.md',
    'test-report-1756501707899.md',
    
    // Documentation that shouldn't be public
    'COMPREHENSIVE_DEBUG_REPORT.md',
    'COMPREHENSIVE_E2E_VALIDATION_REPORT.md',
    'DEPLOYMENT_COMPLETE.md',
    'DEPLOYMENT_COMPLETE_README.md',
    'FINAL_CSS_FIX_REPORT.md',
    'FINAL_DEPLOYMENT_REPORT.md',
    'FINAL_E2E_TEST_REPORT.md',
    'FINAL_E2E_VALIDATION_REPORT.md',
    'FINAL_SECURITY_REPORT.md',
    'PRODUCTION_DEPLOYMENT_GUIDE.md',
    'SECURITY_FIXES_REPORT.md',
    'SHARED_HOSTING_DEPLOYMENT.md',
    'remote-debug-workflow.md',
    'ssh-debug-commands.md',
    
    // JSON reports
    'FINAL_E2E_PRODUCTION_REPORT.json',
    
    // Temporary cookies file
    'cookies.txt',
    
    // Index.html in root (should use index.php)
    'index.html'
];

echo "\n2. Processing " . count($filesToDelete) . " files for removal...\n\n";

foreach ($filesToDelete as $file) {
    $filePath = $rootDir . '/' . $file;
    if (file_exists($filePath)) {
        // Backup certain critical files before deletion
        if (strpos($file, 'setup') !== false || strpos($file, 'database') !== false) {
            echo "   Backing up: $file\n";
            copy($filePath, $backupDir . '/' . $file);
        }
        
        // Delete the file
        if (unlink($filePath)) {
            echo "   ✓ Deleted: $file\n";
            $deletedCount++;
        } else {
            echo "   ✗ Failed to delete: $file\n";
        }
    }
}

// Clean up test-results directory
$testResultsDir = $rootDir . '/test-results';
if (is_dir($testResultsDir)) {
    echo "\n3. Cleaning test-results directory...\n";
    $files = glob($testResultsDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "   ✓ Deleted: " . basename($file) . "\n";
            $deletedCount++;
        }
    }
    rmdir($testResultsDir);
    echo "   ✓ Removed test-results directory\n";
}

// Remove node_modules if exists (shouldn't be in production)
$nodeModulesDir = $rootDir . '/node_modules';
if (is_dir($nodeModulesDir)) {
    echo "\n4. Removing node_modules directory (this may take a moment)...\n";
    exec("rm -rf " . escapeshellarg($nodeModulesDir));
    echo "   ✓ Removed node_modules directory\n";
}

// Remove package files (not needed in production)
$packageFiles = ['package.json', 'package-lock.json'];
foreach ($packageFiles as $file) {
    $filePath = $rootDir . '/' . $file;
    if (file_exists($filePath)) {
        copy($filePath, $backupDir . '/' . $file);
        unlink($filePath);
        echo "   ✓ Removed: $file\n";
        $deletedCount++;
    }
}

// Remove tests directory (if not needed in production)
$testsDir = $rootDir . '/tests';
if (is_dir($testsDir)) {
    echo "\n5. Backing up and removing tests directory...\n";
    exec("cp -r " . escapeshellarg($testsDir) . " " . escapeshellarg($backupDir . '/tests'));
    exec("rm -rf " . escapeshellarg($testsDir));
    echo "   ✓ Tests directory backed up and removed\n";
}

// Remove screenshots directory
$screenshotsDir = $rootDir . '/screenshots';
if (is_dir($screenshotsDir)) {
    echo "\n6. Removing screenshots directory...\n";
    exec("rm -rf " . escapeshellarg($screenshotsDir));
    echo "   ✓ Screenshots directory removed\n";
}

// Clean up __MACOSX directory
$macosxDir = $rootDir . '/__MACOSX';
if (is_dir($macosxDir)) {
    echo "\n7. Removing __MACOSX directory...\n";
    exec("rm -rf " . escapeshellarg($macosxDir));
    echo "   ✓ __MACOSX directory removed\n";
}

// Check for any remaining suspicious files
echo "\n8. Scanning for remaining suspicious files...\n";
$patterns = [
    '*.log' => 'Log files (except in /logs)',
    'test*.php' => 'Test PHP files',
    'debug*.php' => 'Debug PHP files',
    '*deploy*.php' => 'Deployment files',
    'setup*.php' => 'Setup files (except main setup.php)',
];

foreach ($patterns as $pattern => $description) {
    $files = glob($rootDir . '/' . $pattern);
    foreach ($files as $file) {
        $basename = basename($file);
        // Skip legitimate files
        if ($basename === 'setup.php' || strpos($file, '/logs/') !== false) {
            continue;
        }
        echo "   ⚠ Found: $basename ($description)\n";
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "Total files deleted: $deletedCount\n";
echo "Backup directory: " . basename($backupDir) . "\n";
echo "\nIMPORTANT: After verifying the site works, delete the backup directory:\n";
echo "   rm -rf " . basename($backupDir) . "\n";
echo "\nNext: Run EMERGENCY_FIX_04_VERIFY.php\n\n";