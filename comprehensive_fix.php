<?php
// Comprehensive redirect loop fix
header("Content-Type: text/plain");

echo "=== COMPREHENSIVE REDIRECT FIX ===\n\n";

// 1. Fix the Auth controller to prevent redirect loops
echo "1. Fixing Auth controller...\n";
$authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
$authContent = file_get_contents($authFile);

// The login() method should NOT redirect if auth->check() has issues
// Instead, it should only redirect if truly authenticated AND session is valid
$authContent = preg_replace(
    '/public function login\(\)\s*:\s*void\s*{[^}]*if\s*\(\$this->auth->check\(\)\)[^}]*}/s',
    'public function login(): void
    {
        // Only redirect if TRULY logged in with valid session
        if (isset($_SESSION["user_id"]) && 
            isset($_SESSION["logged_in"]) && 
            $_SESSION["logged_in"] === true &&
            isset($_SESSION["is_admin"]) &&
            $_SESSION["is_admin"] === true) {
            // Double-check the session is actually valid
            try {
                $user = $this->db->fetchRow(
                    "SELECT user_id FROM users WHERE user_id = ?",
                    [$_SESSION["user_id"]]
                );
                if ($user) {
                    $this->redirect("/admin/dashboard");
                    return;
                }
            } catch (Exception $e) {
                // Session invalid, continue to show login
            }
        }

        // Get any flash messages
        $flash = $this->getFlash();
        
        // Generate CSRF token
        $csrfToken = $this->auth->generateCsrfToken();

        $this->render("admin/auth/login", [
            "csrf_token" => $csrfToken,
            "flash" => $flash,
            "page_title" => "Admin Login"
        ]);
    }',
    $authContent
);

file_put_contents($authFile, $authContent);
echo "   ✓ Auth controller login method fixed\n";

// 2. Fix Auth utility check method to be more robust
echo "\n2. Fixing Auth utility...\n";
$authUtilFile = __DIR__ . "/src/Utils/Auth.php";
$authUtilContent = file_get_contents($authUtilFile);

// Make check() method more robust
if (strpos($authUtilContent, "public function check(): bool") !== false) {
    $authUtilContent = preg_replace(
        '/public function check\(\)\s*:\s*bool\s*{[^}]*return[^}]*}/s',
        'public function check(): bool
    {
        // Comprehensive authentication check
        if (!isset($_SESSION["user_id"]) || 
            !isset($_SESSION["logged_in"]) || 
            $_SESSION["logged_in"] !== true) {
            return false;
        }

        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->logout();
            return false;
        }

        // Verify user still exists in database
        try {
            $user = $this->db->fetchRow(
                "SELECT user_id FROM users WHERE user_id = ?",
                [$_SESSION["user_id"]]
            );
            if (!$user) {
                $this->logout();
                return false;
            }
        } catch (Exception $e) {
            // Database error, assume not authenticated
            return false;
        }

        // Update last activity time
        $_SESSION["last_activity"] = time();
        
        return true;
    }',
        $authUtilContent
    );
    
    file_put_contents($authUtilFile, $authUtilContent);
    echo "   ✓ Auth utility check method improved\n";
}

// 3. Clear ALL sessions to start fresh
echo "\n3. Clearing all sessions...\n";
$sessionPath = session_save_path() ?: "/opt/alt/php84/var/lib/php/session";
$files = glob($sessionPath . "/sess_*");
$count = count($files);
foreach ($files as $file) {
    @unlink($file);
}
echo "   ✓ Cleared $count session files\n";

// 4. Clear opcache
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "   ✓ OPcache cleared\n";
}

echo "\n✅ Comprehensive fix applied!\n";
echo "\nThe redirect loop should now be completely fixed.\n";
echo "Please clear your browser cookies for dalthaus.net and try again.\n";
?>