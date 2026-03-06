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
    protected function initialize(): void
    {
        // No need to redeclare $auth - it's inherited from BaseController
        // Just make sure it's initialized if not already done in parent
        if ($this->auth === null) {
            $this->auth = new AuthUtil($this->db, $this->config["security"]);
        }
        $this->view->layout("auth");
    }

    /**
     * Handle /admin root route - redirect to dashboard if logged in, or to login
     */
    public function handleAdminRoot(): void
    {
        if ($this->auth->check()) {
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
        // Set cache control headers to prevent caching of login page
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

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
     * Process login attempt - WITH EXTENSIVE LOGGING
     */
    public function authenticate(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        error_log("========== AUTH START: $timestamp ==========");
        error_log("[AUTH] Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        error_log("[AUTH] Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
        error_log("[AUTH] Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        error_log("[AUTH] Session ID: " . session_id());
        error_log("[AUTH] Session data: " . json_encode($_SESSION ?? []));

        if (!$this->request->isPost()) {
            error_log("[AUTH] ❌ FAIL: Not a POST request");
            $this->redirect("/admin/login");
            return;
        }
        error_log("[AUTH] ✓ Request is POST");

        $token = $this->request->post('_token', '');
        error_log("[AUTH] CSRF token received: " . substr($token, 0, 20) . "...");

        if (!$this->auth->validateCsrfToken($token)) {
            error_log("[AUTH] ❌ FAIL: Invalid CSRF token");
            $this->setFlash("error", "Invalid security token. Please try again.");
            $this->redirect("/admin/login");
            return;
        }
        error_log("[AUTH] ✓ CSRF token valid");

        $username = $this->request->post("username", "");
        $password = $this->request->post("password", "");
        $rememberMe = (bool)$this->request->post("remember_me", false);

        error_log("[AUTH] Username: " . $username);
        error_log("[AUTH] Password length: " . strlen($password));
        error_log("[AUTH] Remember me: " . ($rememberMe ? 'YES' : 'NO'));

        if (empty($username) || empty($password)) {
            error_log("[AUTH] ❌ FAIL: Empty credentials");
            $this->setFlash("error", "Username and password are required.");
            $this->redirect("/admin/login");
            return;
        }
        error_log("[AUTH] ✓ Credentials provided");

        $rateLimitKey = "admin_login_" . ($_SERVER["REMOTE_ADDR"] ?? "unknown");
        error_log("[AUTH] Rate limit key: $rateLimitKey");

        if (!Security::checkRateLimit($rateLimitKey, 5, 300)) {
            error_log("[AUTH] ❌ FAIL: Rate limit exceeded");
            $this->setFlash("error", "Too many login attempts. Please wait 5 minutes.");
            $this->redirect("/admin/login");
            return;
        }
        error_log("[AUTH] ✓ Rate limit check passed");

        $remainingLockout = $this->auth->getRemainingLockoutTime($username);
        error_log("[AUTH] Remaining lockout time: $remainingLockout seconds");

        if ($remainingLockout > 0) {
            $minutes = ceil($remainingLockout / 60);
            error_log("[AUTH] ❌ FAIL: Account locked for $minutes minute(s)");
            $this->setFlash("error", "Account locked. Please wait {$minutes} minute(s).");
            $this->redirect("/admin/login");
            return;
        }
        error_log("[AUTH] ✓ Account not locked");

        try {
            error_log("[AUTH] → Calling AuthUtil::attempt()...");
            $attemptResult = $this->auth->attempt($username, $password, $rememberMe);
            error_log("[AUTH] ← AuthUtil::attempt() returned: " . ($attemptResult ? 'TRUE' : 'FALSE'));

            if ($attemptResult) {
                error_log("[AUTH] ✓✓✓ LOGIN SUCCESSFUL ✓✓✓");
                error_log("[AUTH] Session after login: " . json_encode($_SESSION ?? []));
                error_log("[AUTH] Redirecting to: /admin/dashboard");
                error_log("[AUTH] Headers sent: " . (headers_sent() ? 'YES' : 'NO'));

                // Use proper HTTP redirect - most reliable method
                $this->redirect("/admin/dashboard");
                error_log("[AUTH] After redirect() call");
                return;
            } else {
                error_log("[AUTH] ❌ LOGIN FAILED - Invalid credentials");
                $remainingLockout = $this->auth->getRemainingLockoutTime($username);
                if ($remainingLockout > 0) {
                    $minutes = ceil($remainingLockout / 60);
                    error_log("[AUTH] Account now locked for $minutes minute(s)");
                    $this->setFlash("error", "Invalid credentials. Locked for {$minutes} minute(s).");
                } else {
                    $this->setFlash("error", "Invalid username or password.");
                }
                $this->redirect("/admin/login");
            }
        } catch (\Exception $e) {
            error_log("[AUTH] ❌❌❌ EXCEPTION THROWN ❌❌❌");
            error_log("[AUTH] Exception: " . $e->getMessage());
            error_log("[AUTH] File: " . $e->getFile() . ":" . $e->getLine());
            error_log("[AUTH] Stack trace: " . $e->getTraceAsString());

            $this->setFlash("error", "Login system temporarily unavailable. Please try again later.");
            $this->redirect("/admin/login");
        }

        error_log("========== AUTH END ==========");
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
