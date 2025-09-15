<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Utils\Auth as AuthUtil;
use CMS\Utils\Security;

/**
 * Admin Authentication Controller - BULLETPROOF VERSION
 * This version NEVER redirects from the login page to prevent loops
 */
class Auth extends BaseController
{
    private AuthUtil $auth;

    protected function initialize(): void
    {
        $this->auth = new AuthUtil($this->db, $this->config["security"]);
        $this->view->layout("auth");
    }

    /**
     * Handle /admin root route - redirect to dashboard if logged in, or to login
     */
    public function handleAdminRoot(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect("/admin/dashboard");
        } else {
            $this->redirect("/admin/login");
        }
    }

    /**
     * Show login form - NEVER REDIRECTS
     */
    public function login(): void
    {
        // BULLETPROOF: Never check authentication, never redirect
        // Always show the login form no matter what
        
        // If user is already logged in, they can still see the login page
        // This prevents ANY possibility of redirect loops
        
        $this->render("admin/auth/login", [
            "csrf_token" => $this->auth->generateCsrfToken(),
            "flash" => $this->getFlash(),
            "page_title" => "Admin Login"
        ]);
    }

    /**
     * Process login attempt
     */
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

        // Debug: Log the attempt result
        $attemptResult = $this->auth->attempt($username, $password);
        error_log("Login attempt for user '{$username}': " . ($attemptResult ? 'SUCCESS' : 'FAILED'));
        
        if ($attemptResult) {
            // TEMPORARY DEBUG: Show success instead of redirecting
            if (isset($_GET['debug'])) {
                echo "<h1>Login Successful!</h1>";
                echo "<p>User authenticated successfully.</p>";
                echo "<p>Session data:</p><pre>";
                print_r($_SESSION);
                echo "</pre>";
                echo "<p>Headers sent: " . (headers_sent($f, $l) ? "Yes at $f:$l" : "No") . "</p>";
                echo "<p><a href='/admin/dashboard'>Click here to go to dashboard</a></p>";
                exit;
            }
            
            error_log("Setting flash message and redirecting to /admin/dashboard");
            $this->setFlash("success", "Welcome back!");
            
            // Debug: Check if headers already sent
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in {$file} at line {$line} before redirect");
            }
            
            error_log("About to redirect to /admin/dashboard");
            $this->redirect("/admin/dashboard");
            error_log("This should never be logged - after redirect");
            return; // Ensure execution stops after redirect
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

    /**
     * Process logout
     */
    public function logout(): void
    {
        $this->auth->logout();
        $this->setFlash("success", "You have been logged out successfully.");
        $this->redirect("/admin/login");
    }
}
