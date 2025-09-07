<?php

declare(strict_types=1);

namespace CMS\Controllers;

use CMS\Utils\Database;
use CMS\Utils\View;
use CMS\Utils\Request;
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
        
        $this->initialize();
    }

    protected function initialize(): void
    {
        // This method can be overridden in child controllers for specific initializations.
    }

    protected function render(string $template, array $data = []): void
    {
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
}