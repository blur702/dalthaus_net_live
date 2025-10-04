<?php

declare(strict_types=1);

namespace CMS\Controllers;

use CMS\Utils\Database;
use CMS\Utils\View;
use CMS\Utils\Request;
use CMS\Utils\Auth;
use CMS\Models\Settings;
use Exception;

/**
 * Base Controller Class (Refactored)
 * 
 * Provides common functionality for all controllers including
 * database access, view rendering, and request handling via a dedicated Request object.
 * 
 * @package CMS\Controllers
 * @author  Kevin
 * @version 1.1.0
 */
abstract class BaseController
{
    protected ?Database $db;
    protected View $view;
    protected Request $request;
    protected array $config;
    protected ?Auth $auth;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
        
        // Apply aggressive no-cache headers for ALL admin pages immediately
        $this->setAdminNoCacheHeaders();
        
        // Try to initialize database connection, but allow graceful degradation
        try {
            $this->db = Database::getInstance($this->config['database']);
            // Initialize Auth instance only if database is available
            $this->auth = new Auth($this->db, $this->config["security"]);
        } catch (Exception $e) {
            // In debug mode, allow the app to continue without database
            if ($this->config['app']['debug']) {
                error_log("Database connection failed in controller: " . $e->getMessage());
                // Set a null database - controllers will need to handle this gracefully
                $this->db = null;
                $this->auth = null;
            } else {
                // In production mode, re-throw the exception
                throw $e;
            }
        }
        
        $this->view = new View($this->config['views']);
        $this->request = new Request();
        
        // Check maintenance mode for public pages (not admin)
        $this->checkMaintenanceMode();
        
        $this->initialize();
    }

    protected function initialize(): void
    {
        // This method can be overridden in child controllers for specific initializations.
    }

    protected function render(string $template, array $data = []): void
    {
        // DEBUG: Log render start
        error_log("BaseController::render() - Starting render for template: $template");
        
        // Add site settings to all views
        if (!isset($data['settings'])) {
            error_log("BaseController::render() - Getting settings");
            if ($this->db !== null) {
                $data['settings'] = Settings::getAll();
                error_log("BaseController::render() - Settings loaded successfully");
            } else {
                // Provide default settings when database is unavailable
                $data['settings'] = [
                    'site_name' => 'Development Mode - Database Unavailable',
                    'site_description' => 'Running in development mode without database connection',
                    'site_url' => 'http://localhost',
                    'admin_email' => 'admin@localhost'
                ];
                error_log("BaseController::render() - Using default settings (no database)");
            }
        }
        
        // Add current_user to all admin views
        if (!isset($data['current_user'])) {
            $currentUserId = $this->getCurrentUserId();
            error_log("BaseController::render() - Current user ID: " . ($currentUserId ?? 'null'));
            
            if ($currentUserId && $this->db !== null) {
                error_log("BaseController::render() - Loading user model");
                try {
                    $userModel = new \CMS\Models\User();
                    $user = $userModel->find($currentUserId);
                    $data['current_user'] = $user ? $user->toArray() : null;
                    error_log("BaseController::render() - User loaded successfully");
                } catch (Exception $e) {
                    error_log("BaseController::render() - User loading failed: " . $e->getMessage());
                    $data['current_user'] = null;
                }
            } else {
                $data['current_user'] = null;
            }
        }
        
        // Add CSRF token if not already set
        if (!isset($data['csrf_token'])) {
            error_log("BaseController::render() - Generating CSRF token");
            $data['csrf_token'] = $this->generateCsrfToken();
            error_log("BaseController::render() - CSRF token generated");
        }
        
        // Add flash messages if not already set
        if (!isset($data['flash'])) {
            error_log("BaseController::render() - Getting flash messages");
            $data['flash'] = $this->getFlash();
            error_log("BaseController::render() - Flash messages retrieved");
        }
        
        error_log("BaseController::render() - About to call view->render()");
        echo $this->view->render($template, $data);
        error_log("BaseController::render() - View rendered successfully");
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        // Check if headers have already been sent
        if (headers_sent($filename, $linenum)) {
            // Log the error for debugging
            error_log("Headers already sent in {$filename} on line {$linenum}. Cannot redirect to {$url}");
            
            // Fallback to JavaScript redirect
            echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'></noscript>";
            exit;
        }
        
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    protected function renderJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function getParam(string $key, $default = null, string $method = 'get')
    {
        if (strtolower($method) === 'post') {
            return $this->request->post($key, $default);
        }
        return $this->request->get($key, $default);
    }

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }

    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    protected function validateCsrfToken(): bool
    {
        $token = $this->request->post('_token');
        $sessionToken = $_SESSION['_token'] ?? '';
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Apply aggressive no-cache headers for admin pages
     * This ensures admin pages are NEVER cached by browsers or proxies
     */
    private function setAdminNoCacheHeaders(): void
    {
        // Only apply to admin routes
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUri, '/admin') !== 0) {
            return;
        }
        
        // Comprehensive no-cache headers for admin pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        
        // Prevent any form of caching
        header('Vary: *');
        header('X-Accel-Expires: 0');
        header('X-Cache-Control: no-cache');
        
        // Remove ETag headers that can cause caching
        if (function_exists('header_remove')) {
            header_remove('ETag');
        }
        
        // Debug headers for troubleshooting
        header('X-Admin-No-Cache: true');
        header('X-Cache-Buster: ' . time());
        header('X-Load-Time: ' . date('Y-m-d H:i:s'));
    }

    protected function requireAuth(): void
    {
        // Check both user_id and logged_in flag for consistency
        if (!$this->isAuthenticated()) {
            $this->setFlash('error', 'You must be logged in to view this page.');
            $this->redirect('/admin/login');
        }
    }

    protected function getCurrentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
    
    protected function isAuthenticated(): bool
    {
        // If Auth instance is available, use its comprehensive check method
        // which includes Remember Me cookie validation
        if ($this->auth !== null) {
            return $this->auth->check();
        }
        
        // Fallback to session-only check if Auth is not available
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               !empty($_SESSION['logged_in']); // Accept both true and 1
    }

    protected function isAjax(): bool
    {
        return $this->request->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Check if maintenance mode is enabled and show maintenance page
     * Skips check for admin controllers and logged-in admins
     * 
     * @return void
     */
    protected function checkMaintenanceMode(): void
    {
        // Skip check for admin controllers
        if (str_contains(static::class, '\\Admin\\')) {
            return;
        }
        
        // Skip check if user is logged in as admin
        if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            return;
        }
        
        // Check if maintenance mode is enabled - wrapped in try-catch to prevent 503 on DB errors
        try {
            $maintenanceMode = Settings::getBool('maintenance_mode', false);
            
            if ($maintenanceMode) {
                $this->showMaintenancePage();
            }
        } catch (Exception $e) {
            // Log the error but don't trigger maintenance mode on database errors
            error_log('Maintenance mode check failed: ' . $e->getMessage());
            // Continue normally - assume maintenance mode is OFF if we can't check
        }
    }

    /**
     * Display maintenance page and exit
     * 
     * @return void
     */
    protected function showMaintenancePage(): void
    {
        $maintenanceMessage = Settings::get('maintenance_message', 'We are currently performing maintenance on our site. Please check back shortly.');
        
        // Set 503 Service Unavailable status
        http_response_code(503);
        header('Retry-After: 3600'); // Retry after 1 hour
        
        // Use simple view layout for maintenance page
        $this->view->layout('maintenance');
        
        try {
            echo $this->view->render('maintenance/index', [
                'maintenance_message' => $maintenanceMessage,
                'page_title' => 'Site Maintenance'
            ]);
        } catch (Exception $e) {
            // Fallback to basic HTML if template fails
            $this->showBasicMaintenancePage($maintenanceMessage);
        }
        
        exit;
    }

    /**
     * Show basic maintenance page as fallback
     * 
     * @param string $message Maintenance message
     * @return void
     */
    protected function showBasicMaintenancePage(string $message): void
    {
        $siteName = Settings::get('site_title', 'Website');
        
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Site Maintenance - {$siteName}</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background-color: rgb(248, 248, 248);
            margin: 0; padding: 0; min-height: 100vh; 
            display: flex; align-items: center; justify-content: center;
            color: rgb(20, 20, 20);
        }
        .maintenance-container { 
            background: white; 
            border-radius: 8px; 
            padding: 3rem 2rem; 
            max-width: 600px; 
            width: 90%;
            text-align: center; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e5e5;
        }
        .maintenance-icon { 
            font-size: 4rem; 
            margin-bottom: 2rem; 
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        h1 { 
            color: rgb(20, 20, 20); 
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem; 
            line-height: 1.2;
        }
        p { 
            color: #666; 
            font-size: 1.125rem;
            line-height: 1.6; 
            margin-bottom: 2rem; 
        }
        .retry-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 8px;
            color: #666;
            font-size: 0.875rem;
            border: 1px solid #e5e5e5;
        }
    </style>
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</head>
<body>
    <div class=\"maintenance-container\">
        <div class=\"maintenance-icon\">🔧</div>
        <h1>Site Under Maintenance</h1>
        <p>" . htmlspecialchars($message) . "</p>
        <div class=\"retry-info\">
            <strong>For visitors:</strong> This page will automatically refresh every 5 minutes to check if maintenance is complete.
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Log error message
     * 
     * @param string $message Error message
     * @param Exception $exception Optional exception
     * @return void
     */
    protected function logError(string $message, ?Exception $exception = null): void
    {
        $logMessage = date('Y-m-d H:i:s') . " - {$message}";
        if ($exception) {
            $logMessage .= " - " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
        }
        error_log($logMessage);
    }
}