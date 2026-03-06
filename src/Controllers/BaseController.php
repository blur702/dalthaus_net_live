<?php

declare(strict_types=1);

namespace CMS\Controllers;

use CMS\Utils\Database;
use CMS\Utils\View;
use CMS\Utils\Request;
use CMS\Utils\Auth;
use CMS\Models\Settings;
use Exception;

abstract class BaseController
{
    protected Database $db;
    protected Auth $auth;
    protected array $config;
    protected View $view;
    protected Request $request;

    public function __construct(Database $db, Auth $auth, array $config)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->config = $config;

        $this->setAdminNoCacheHeaders();

        $this->view = new View($this->config['views']);
        $this->request = new Request();
        
        $this->checkMaintenanceMode();
        
        $this->initialize();
    }

    protected function initialize(): void
    {
        // This method can be overridden in child controllers for specific initializations.
    }

    protected function render(string $template, array $data = []): void
    {
        if (!isset($data['settings'])) {
            $data['settings'] = Settings::getAll($this->db);
        }
        
        if (!isset($data['current_user'])) {
            $data['current_user'] = $this->auth->user();
        }
        
        if (!isset($data['csrf_token'])) {
            $data['csrf_token'] = $this->auth->generateCsrfToken();
        }
        
        if (!isset($data['flash'])) {
            $data['flash'] = $this->getFlash();
        }
        
        echo $this->view->render($template, $data);
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        if (headers_sent($filename, $linenum)) {
            error_log("Headers already sent in {$filename} on line {$linenum}. Cannot redirect to {$url}");
            echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';</script>";
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

    private function setAdminNoCacheHeaders(): void
    {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== 0) {
            return;
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    protected function checkMaintenanceMode(): void
    {
        if (str_contains(static::class, '\\Admin\\') || ($this->auth->check() && $this->auth->user()['is_admin'])) {
            return;
        }
        
        try {
            if (Settings::getBool('maintenance_mode', false, $this->db)) {
                $this->showMaintenancePage();
            }
        } catch (Exception $e) {
            error_log('Maintenance mode check failed: ' . $e->getMessage());
        }
    }

    protected function showMaintenancePage(): void
    {
        $maintenanceMessage = Settings::get('maintenance_message', 'We are currently performing maintenance.', $this->db);
        http_response_code(503);
        header('Retry-After: 3600');
        $this->view->layout('maintenance');
        echo $this->view->render('maintenance/index', ['maintenance_message' => $maintenanceMessage, 'page_title' => 'Site Maintenance']);
        exit;
    }
}
