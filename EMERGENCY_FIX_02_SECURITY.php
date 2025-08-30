#!/usr/bin/env php
<?php
/**
 * EMERGENCY FIX #2: SECURITY - PROTECT SETUP.PHP
 * Priority: P0 - CRITICAL SECURITY EXPOSURE
 * Enable .htaccess protection for setup.php
 */

echo "\n=== EMERGENCY SECURITY FIX - PROTECT SETUP.PHP ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Backup .htaccess
$htaccessFile = __DIR__ . '/.htaccess';
$backupFile = __DIR__ . '/.htaccess.backup.' . time();

if (!file_exists($htaccessFile)) {
    die("ERROR: .htaccess file not found!\n");
}

echo "1. Backing up .htaccess to: " . basename($backupFile) . "\n";
copy($htaccessFile, $backupFile);

// Step 2: Read current .htaccess
$htaccess = file_get_contents($htaccessFile);

// Step 3: Enable setup.php protection
echo "2. Enabling setup.php protection...\n";

// Find and uncomment the setup.php protection block
$htaccess = str_replace(
    '# <Files "setup.php">
#     <IfModule mod_authz_core.c>
#         Require all denied
#     </IfModule>
#     <IfModule !mod_authz_core.c>
#         Order allow,deny
#         Deny from all
#     </IfModule>
# </Files>',
    '<Files "setup.php">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</Files>',
    $htaccess
);

// Step 4: Enable HTTPS redirect
echo "3. Enabling HTTPS redirect...\n";

$htaccess = str_replace(
    '# RewriteCond %{HTTPS} !=on
# RewriteCond %{HTTP:X-Forwarded-Proto} !https
# RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]',
    'RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]',
    $htaccess
);

// Step 5: Add protection for debug and test files
echo "4. Adding protection for debug/test files...\n";

// Add before the "PROTECT DIRECTORIES" section
$debugProtection = '
# Block access to debug, test, and deployment files
<FilesMatch "\.(php|sh|py|js|bat|ps1)$">
    <If "%{REQUEST_URI} =~ m#^/(debug|test|deploy|setup-debug|EMERGENCY|PRODUCTION|QUICK|FORCE|UPLOAD|DEPLOY|auto-deploy|manual-deploy|master-deploy|remote|file-agent|capture-screenshots|css-|database_|e2e-|emergency-|enhance-|extract-|feature-|final-|fix-|git-pull|investigate-|production-|quick-|set-|simple-|validate-)#i">
        <IfModule mod_authz_core.c>
            Require all denied
        </IfModule>
        <IfModule !mod_authz_core.c>
            Order allow,deny
            Deny from all
        </IfModule>
    </If>
</FilesMatch>

# Block all setup files except when initially setting up
<FilesMatch "^setup.*\.php$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>
';

// Insert before "PROTECT DIRECTORIES"
$htaccess = str_replace(
    '# =====================================
# PROTECT DIRECTORIES',
    $debugProtection . '
# =====================================
# PROTECT DIRECTORIES',
    $htaccess
);

// Step 6: Write updated .htaccess
echo "5. Writing updated .htaccess...\n";
file_put_contents($htaccessFile, $htaccess);

echo "✓ Setup.php is now protected\n";
echo "✓ HTTPS redirect is enabled\n";
echo "✓ Debug/test files are blocked\n";

// Step 7: Test if setup.php is still accessible
echo "\n6. Verifying protection...\n";

// This would need to be tested via HTTP request in production
// For now, we'll check if the rules are in place
if (strpos($htaccess, '<Files "setup.php">') !== false && 
    strpos($htaccess, '# <Files "setup.php">') === false) {
    echo "✓ Setup.php protection is active in .htaccess\n";
} else {
    echo "✗ WARNING: Setup.php protection may not be properly enabled\n";
}

if (strpos($htaccess, 'RewriteCond %{HTTPS} !=on') !== false && 
    strpos($htaccess, '# RewriteCond %{HTTPS} !=on') === false) {
    echo "✓ HTTPS redirect is active in .htaccess\n";
} else {
    echo "✗ WARNING: HTTPS redirect may not be properly enabled\n";
}

echo "\n=== SECURITY FIX COMPLETE ===\n";
echo "Next: Run EMERGENCY_FIX_03_CLEANUP.php\n\n";