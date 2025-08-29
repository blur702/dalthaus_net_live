<?php
/**
 * PRODUCTION CLEANUP SCRIPT
 * 
 * Removes development files, optimizes for production, and secures the installation
 * 
 * ⚠️  WARNING: This will permanently delete files. Run only after confirming 
 * the system is working correctly.
 * 
 * Usage: https://dalthaus.net/PRODUCTION_CLEANUP.php
 */
declare(strict_types=1);

// Security: Only allow execution if setup was successful
if (!file_exists('PRODUCTION_SETUP.php') && !file_exists('QUICK_TEST.php')) {
    die('Cleanup can only be run if setup scripts are present. Please run PRODUCTION_SETUP.php first.');
}

echo "<!DOCTYPE html><html><head><title>Production Cleanup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:20px auto;padding:20px;}";
echo ".success{color:green;}.warning{color:orange;}.error{color:red;}.info{color:blue;}";
echo ".cleanup-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}";
echo "button{background:#007cba;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;margin:5px;}";
echo "button:hover{background:#005a87;}";
echo ".danger{background:#dc3545;}.danger:hover{background:#c82333;}";
echo "</style></head><body>";

echo "<h1>🧹 Production Cleanup</h1>";
echo "<p>This script will optimize your Dalthaus CMS installation for production use.</p>";

// Check if this is a POST request (user confirmed cleanup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cleanup'])) {
    echo "<div class='cleanup-section'>";
    echo "<h2>🔧 Performing Cleanup...</h2>";
    
    $deleted = [];
    $errors = [];
    
    // Files to delete (development/setup files)
    $filesToDelete = [
        // Setup and test files
        'PRODUCTION_SETUP.php',
        'QUICK_TEST.php',
        'PRODUCTION_CLEANUP.php', // This file will delete itself
        'COMPREHENSIVE_DEBUG_REPORT.md',
        'test-php.php',
        'test-css.php',
        'test-settings.php',
        'debug-index.php',
        'simple-index.php',
        
        // Development files
        'auto-deploy.php',
        'deploy-and-test.php',
        'deploy.sh',
        'emergency-fix.php',
        'emergency-fix-production.php',
        'production-fix.php',
        'quick-fix.php',
        'master-deploy.php',
        'manual-deploy.php',
        'git-pull.php',
        'file-agent.php',
        'remote-agent.php',
        'remote-debug.py',
        'enhance-error-handling.php',
        'fix-maintenance.php',
        'fix-database-schema.php',
        'final-validation.php',
        'feature-checklist.php',
        'validate-deployment.php',
        'capture-screenshots.php',
        'create-havasu-article.php',
        'setup-cli.php',
        'setup-debug.php',
        
        // Test and report files
        'production-http-test.js',
        'production-test-suite.php',
        'e2e-endpoint.php',
        
        // Documentation (keep essential ones)
        'DEPLOYMENT_COMPLETE.md',
        'DEPLOY_THIS_FILE.php',
        'UPLOAD_AND_RUN_THIS.php',
        'FINAL_DEPLOYMENT_REPORT.md',
        'FINAL_E2E_TEST_REPORT.md',
        'FINAL_E2E_VALIDATION_REPORT.md',
        'FINAL_SECURITY_REPORT.md',
        'PRODUCTION_DEPLOYMENT_GUIDE.md',
        'SECURITY_FIXES_REPORT.md',
        'SHARED_HOSTING_DEPLOYMENT.md',
        
        // Development configuration
        'nginx.conf',
        'database_fixes.sql',
        'cookies.txt',
        'cleanup-for-production.sh',
        'fix-permissions.sh',
        'set_permissions.sh',
        'push-to-github.sh',
        '.env.example',
        
        // Windows development files
        'install-mysql.ps1',
        'install-mysql-admin.bat',
        
        // Various test reports
        'test-report-1756488024080.md',
        'test-report-1756491150118.md',
        'test-report-1756491837926.md',
        'test-report-1756500285534.md',
        'test-report-1756501707899.md',
        
        // SSH and debugging
        'ssh-debug-commands.md',
        'remote-debug-workflow.md',
        'set-maintenance.php'
    ];
    
    // Directories to clean up
    $directoriesToClean = [
        'screenshots',
        'test-results',
        '__MACOSX',
        'node_modules' // If not needed for production
    ];
    
    // Delete individual files
    foreach ($filesToDelete as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $deleted[] = $file;
                echo "<p class='success'>✅ Deleted: $file</p>";
            } else {
                $errors[] = $file;
                echo "<p class='error'>❌ Failed to delete: $file</p>";
            }
        }
    }
    
    // Clean up directories
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }
    
    foreach ($directoriesToClean as $dir) {
        if (is_dir($dir)) {
            if (deleteDirectory($dir)) {
                $deleted[] = "$dir/ (directory)";
                echo "<p class='success'>✅ Deleted directory: $dir/</p>";
            } else {
                $errors[] = "$dir/ (directory)";
                echo "<p class='error'>❌ Failed to delete directory: $dir/</p>";
            }
        }
    }
    
    // Optimize .htaccess for production (uncomment setup.php protection)
    $htaccessFile = '.htaccess';
    if (file_exists($htaccessFile)) {
        $htaccess = file_get_contents($htaccessFile);
        
        // Uncomment the setup.php protection
        $htaccess = str_replace(
            '# <Files "setup.php">',
            '<Files "setup.php">',
            $htaccess
        );
        $htaccess = str_replace(
            '# </Files>',
            '</Files>',
            $htaccess
        );
        
        // Uncomment other commented protections
        $htaccess = preg_replace('/^# (<Files|#     |# <\/Files>)/m', '$1', $htaccess);
        
        if (file_put_contents($htaccessFile, $htaccess)) {
            echo "<p class='success'>✅ Updated .htaccess security settings</p>";
        } else {
            echo "<p class='error'>❌ Failed to update .htaccess</p>";
        }
    }
    
    // Clear cache directory (but keep the directory itself)
    $cacheDir = 'cache';
    if (is_dir($cacheDir)) {
        $cacheFiles = glob($cacheDir . '/*.cache');
        foreach ($cacheFiles as $file) {
            if (unlink($file)) {
                echo "<p class='success'>✅ Cleared cache: " . basename($file) . "</p>";
            }
        }
    }
    
    // Clear logs (but keep the directory)
    $logsDir = 'logs';
    if (is_dir($logsDir)) {
        $logFiles = glob($logsDir . '/*.log');
        foreach ($logFiles as $file) {
            if (file_put_contents($file, '')) { // Clear content, don't delete
                echo "<p class='success'>✅ Cleared log: " . basename($file) . "</p>";
            }
        }
    }
    
    // Summary
    echo "<h3>📊 Cleanup Summary</h3>";
    echo "<p><strong>Files/directories deleted:</strong> " . count($deleted) . "</p>";
    echo "<p><strong>Errors:</strong> " . count($errors) . "</p>";
    
    if (count($errors) === 0) {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "<h3>🎉 Cleanup Complete!</h3>";
        echo "<p>Your Dalthaus CMS is now optimized for production use:</p>";
        echo "<ul>";
        echo "<li>✅ Development files removed</li>";
        echo "<li>✅ Security settings hardened</li>";
        echo "<li>✅ Cache and logs cleared</li>";
        echo "<li>✅ Ready for production traffic</li>";
        echo "</ul>";
        echo "<p><strong>Admin Login:</strong> <a href='/admin/login.php'>/admin/login.php</a></p>";
        echo "<p><strong>Public Site:</strong> <a href='/'>/</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "<h3>⚠️ Cleanup Issues</h3>";
        echo "<p>Some files could not be deleted. Please manually remove them or check permissions.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} else {
    // Show confirmation form
    echo "<div class='cleanup-section'>";
    echo "<h2>⚠️ Pre-Cleanup Checklist</h2>";
    echo "<p>Before proceeding, ensure:</p>";
    echo "<ul>";
    echo "<li>✅ Admin login is working correctly</li>";
    echo "<li>✅ Public site displays properly</li>";  
    echo "<li>✅ CSS and JavaScript are loading</li>";
    echo "<li>✅ Database connection is stable</li>";
    echo "<li>✅ You have changed the default admin password</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='cleanup-section'>";
    echo "<h2>🗑️ Files to be Removed</h2>";
    echo "<p>This cleanup will remove approximately <strong>50+ development files</strong> including:</p>";
    echo "<ul>";
    echo "<li>Setup and testing scripts</li>";
    echo "<li>Development configuration files</li>";
    echo "<li>Debug and deployment tools</li>";
    echo "<li>Test reports and screenshots</li>";
    echo "<li>Documentation files (keeping essentials)</li>";
    echo "</ul>";
    echo "<p class='warning'>⚠️ <strong>This action cannot be undone!</strong></p>";
    echo "</div>";
    
    echo "<div class='cleanup-section'>";
    echo "<h2>🔧 Security Optimizations</h2>";
    echo "<p>The cleanup will also:</p>";
    echo "<ul>";
    echo "<li>Enable setup.php access protection</li>";
    echo "<li>Clear application cache</li>";
    echo "<li>Clear log files</li>";
    echo "<li>Optimize .htaccess for production</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<form method='post' style='text-align:center;margin:30px 0;'>";
    echo "<p>Are you sure you want to proceed with production cleanup?</p>";
    echo "<button type='submit' name='confirm_cleanup' value='1' class='danger'>";
    echo "🧹 Yes, Clean Up for Production";
    echo "</button>";
    echo "<br><small>This will permanently delete development files</small>";
    echo "</form>";
}

echo "<p><em>Cleanup script generated: " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>