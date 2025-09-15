<?php
// Simple Auth.php fix script - upload this manually
if (isset($_GET["key"]) && $_GET["key"] === "dalthaus_agent_key_2025") {
    header("Content-Type: text/plain");
    
    echo "=== FIXING AUTH.PHP FILE ===\n\n";
    
    // The working Auth.php content
    $workingAuthContent = '<?php

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
        $this->render("admin/auth/login", [
            "csrf_token" => $this->auth->generateCsrfToken(),
            "flash" => $this->getFlash(),
            "page_title" => "Admin Login"
        ]);
    }

    public function authenticate(): void
    {
        if (!$this->isPost()) {
            $this->redirect("/admin/login");
            return;
        }

        if (!$this->auth->validateCsrfToken($this->getParam("_token", "", "post"))) {
            $this->setFlash("error", "Invalid security token. Please try again.");
            $this->redirect("/admin/login");
            return;
        }

        $username = $this->sanitize($this->getParam("username", "", "post"));
        $password = $this->getParam("password", "", "post");

        if (empty($username) || empty($password)) {
            $this->setFlash("error", "Username and password are required.");
            $this->redirect("/admin/login");
            return;
        }

        $rateLimitKey = "admin_login_" . ($_SERVER["REMOTE_ADDR"] ?? "unknown");
        if (!Security::checkRateLimit($rateLimitKey, 5, 300)) {
            $this->setFlash("error", "Too many login attempts. Please wait 5 minutes.");
            $this->redirect("/admin/login");
            return;
        }

        $remainingLockout = $this->auth->getRemainingLockoutTime($username);
        if ($remainingLockout > 0) {
            $minutes = ceil($remainingLockout / 60);
            $this->setFlash("error", "Account locked. Please wait {$minutes} minute(s).");
            $this->redirect("/admin/login");
            return;
        }

        if ($this->auth->attempt($username, $password)) {
            $this->setFlash("success", "Welcome back!");
            $this->redirect("/admin/dashboard");
        } else {
            $remainingLockout = $this->auth->getRemainingLockoutTime($username);
            if ($remainingLockout > 0) {
                $minutes = ceil($remainingLockout / 60);
                $this->setFlash("error", "Invalid credentials. Locked for {$minutes} minute(s).");
            } else {
                $this->setFlash("error", "Invalid username or password.");
            }
            $this->redirect("/admin/login");
        }
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->setFlash("success", "You have been logged out successfully.");
        $this->redirect("/admin/login");
    }
}
';
    
    $authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
    
    // Backup the broken file
    if (file_exists($authFile)) {
        $backup = $authFile . ".broken_backup_" . date("YmdHis");
        copy($authFile, $backup);
        echo "Backed up broken file to: $backup\n";
    }
    
    // Write the working version
    file_put_contents($authFile, $workingAuthContent);
    echo "✅ Wrote working Auth.php file\n";
    
    // Clear opcache
    if (function_exists("opcache_invalidate")) {
        opcache_invalidate($authFile, true);
        echo "✅ Cleared opcache for Auth.php\n";
    }
    
    echo "\n🎯 AUTH FILE FIXED!\n";
    echo "The redirect loop should now be resolved.\n";
    echo "Try accessing: https://dalthaus.net/admin/login\n";
    
} else {
    http_response_code(403);
    echo "Access denied";
}
?>