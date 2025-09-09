<?php

declare(strict_types=1);

namespace CMS\Controllers;

use CMS\Utils\Database;
use CMS\Utils\View;
use CMS\Utils\Request;
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
    protected Database $db;
    protected View $view;
    protected Request $request;
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
        $this->db = Database::getInstance($this->config['database']);
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
        // Add site settings to all views
        if (!isset($data['settings'])) {
            $data['settings'] = Settings::getAll();
        }
        
        // Add current_user to all admin views
        if (!isset($data['current_user']) && $this->getCurrentUserId()) {
            $userModel = new \CMS\Models\User();
            $user = $userModel->find($this->getCurrentUserId());
            $data['current_user'] = $user ? $user->toArray() : null;
        }
        
        // Add CSRF token if not already set
        if (!isset($data['csrf_token'])) {
            $data['csrf_token'] = $this->generateCsrfToken();
        }
        
        // Add flash messages if not already set
        if (!isset($data['flash'])) {
            $data['flash'] = $this->getFlash();
        }
        
        echo $this->view->render($template, $data);
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
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

    protected function requireAuth(): void
    {
        if (!($this->getCurrentUserId())) {
            $this->setFlash('error', 'You must be logged in to view this page.');
            $this->redirect('/admin/login');
        }
    }

    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
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
        
        // Check if maintenance mode is enabled
        $maintenanceMode = Settings::getBool('maintenance_mode', false);
        
        if ($maintenanceMode) {
            $this->showMaintenancePage();
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0; padding: 0; min-height: 100vh; 
            display: flex; align-items: center; justify-content: center;
            color: #333;
        }
        .maintenance-container { 
            background: white; border-radius: 10px; padding: 40px; 
            max-width: 600px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .maintenance-icon { font-size: 4rem; color: #667eea; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; margin-bottom: 30px; }
        .login-link { 
            display: inline-block; padding: 12px 24px; background: #667eea;
            color: white; text-decoration: none; border-radius: 5px; 
            transition: background 0.3s;
        }
        .login-link:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <div class=\"maintenance-container\">
        <div class=\"maintenance-icon\">🔧</div>
        <h1>Site Maintenance</h1>
        <p>" . htmlspecialchars($message) . "</p>
        <a href=\"/admin/login\" class=\"login-link\">Admin Login</a>
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