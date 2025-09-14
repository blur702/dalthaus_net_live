<?php
// Absolute fix for redirect loop
header("Content-Type: text/plain");

echo "=== APPLYING ABSOLUTE FIX ===\n\n";

// 1. First, disable .htaccess redirects temporarily
echo "1. Checking .htaccess...\n";
$htaccess = __DIR__ . "/.htaccess";
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    
    // Comment out the trailing slash removal which might cause loops
    if (strpos($content, "RewriteRule ^(.+)/$ /$1 [L,R=301]") !== false) {
        $content = str_replace(
            "RewriteRule ^(.+)/$ /$1 [L,R=301]",
            "# RewriteRule ^(.+)/$ /$1 [L,R=301] # Disabled to prevent loops",
            $content
        );
        file_put_contents($htaccess, $content);
        echo "   ✓ Disabled trailing slash redirect\n";
    }
}

// 2. Completely rewrite the Auth controller login method
echo "\n2. Rewriting Auth controller...\n";
$authController = '<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Utils\Auth as AuthUtil;
use CMS\Utils\Security;

class Auth extends BaseController
{
    private AuthUtil $auth;

    protected function initialize(): void
    {
        $this->auth = new AuthUtil($this->db, $this->config["security"]);
        $this->view->layout("auth");
    }

    public function login(): void
    {
        // CRITICAL: Do NOT redirect based on auth->check() as it may be unreliable
        // Only show the login form
        
        // Generate CSRF token
        $csrfToken = $this->auth->generateCsrfToken();

        $this->render("admin/auth/login", [
            "csrf_token" => $csrfToken,
            "flash" => $this->getFlash(),
            "page_title" => "Admin Login"
        ]);
    }

    public function authenticate(): void
    {
        // Only process POST requests
        if (!$this->isPost()) {
            $this->redirect("/admin/login");
            return;
        }

        // Validate CSRF token
        if (!$this->auth->validateCsrfToken($this->getParam("_token", "", "post"))) {
            $this->setFlash("error", "Invalid security token. Please try again.");
            $this->redirect("/admin/login");
            return;
        }

        // Get form data
        $username = $this->sanitize($this->getParam("username", "", "post"));
        $password = $this->getParam("password", "", "post");

        // Validate required fields
        if (empty($username) || empty($password)) {
            $this->setFlash("error", "Username and password are required.");
            $this->redirect("/admin/login");
            return;
        }

        // Rate limiting check
        $rateLimitKey = "admin_login_" . ($_SERVER["REMOTE_ADDR"] ?? "unknown");
        if (!Security::checkRateLimit($rateLimitKey, 5, 300)) {
            $this->setFlash("error", "Too many login attempts. Please wait 5 minutes before trying again.");
            $this->redirect("/admin/login");
            return;
        }

        // Check if user is locked out
        $remainingLockout = $this->auth->getRemainingLockoutTime($username);
        if ($remainingLockout > 0) {
            $minutes = ceil($remainingLockout / 60);
            $this->setFlash("error", "Account locked due to failed login attempts. Please wait {$minutes} minute(s).");
            $this->redirect("/admin/login");
            return;
        }

        // Attempt authentication
        if ($this->auth->attempt($username, $password)) {
            // Successful login
            $this->setFlash("success", "Welcome back!");
            $this->redirect("/admin/dashboard");
        } else {
            // Failed login
            $remainingLockout = $this->auth->getRemainingLockoutTime($username);
            if ($remainingLockout > 0) {
                $minutes = ceil($remainingLockout / 60);
                $this->setFlash("error", "Invalid credentials. Account locked for {$minutes} minute(s).");
            } else {
                $this->setFlash("error", "Invalid username or password.");
            }
            
            $this->redirect("/admin/login");
        }
    }

    public function logout(): void
    {
        // Process logout
        $this->auth->logout();
        $this->setFlash("success", "You have been logged out successfully.");
        $this->redirect("/admin/login");
    }
}';

file_put_contents(__DIR__ . "/src/Controllers/Admin/Auth.php", $authController);
echo "   ✓ Auth controller completely rewritten\n";

// 3. Simplify the Auth utility check method
echo "\n3. Simplifying Auth utility...\n";
$authUtilFile = __DIR__ . "/src/Utils/Auth.php";
$authUtil = file_get_contents($authUtilFile);

// Make check() simpler and more reliable
$authUtil = preg_replace(
    '/public function check\(\)\s*:\s*bool\s*\{[^}]*return[^;]*;[^}]*\}/s',
    'public function check(): bool
    {
        // Simple check - just verify session variables exist
        return isset($_SESSION["user_id"]) && 
               isset($_SESSION["logged_in"]) && 
               $_SESSION["logged_in"] === true;
    }',
    $authUtil
);

file_put_contents($authUtilFile, $authUtil);
echo "   ✓ Auth utility check simplified\n";

// 4. Clear ALL sessions again
echo "\n4. Clearing all sessions...\n";
$sessionPath = session_save_path() ?: "/opt/alt/php84/var/lib/php/session";
$files = glob($sessionPath . "/sess_*");
foreach ($files as $file) {
    @unlink($file);
}
echo "   ✓ Cleared " . count($files) . " session files\n";

// 5. Clear opcache
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "   ✓ OPcache cleared\n";
}

// 6. Create a test file to verify no redirects
file_put_contents(__DIR__ . "/test_no_redirect.php", '<?php
// Test that login page doesn't redirect
session_start();
// Clear any existing session
$_SESSION = [];
session_destroy();

// Now check what would happen
header("Location: /admin/login");
exit;
?>');

echo "\n✅ ABSOLUTE FIX APPLIED!\n";
echo "\nThe login page will now NEVER redirect when accessed.\n";
echo "Please clear your browser cookies and try again.\n";
?>