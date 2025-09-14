<?php
// Fix to disable maintenance mode check causing 503 errors
$fixCode = '<?php

declare(strict_types=1);

namespace CMS\Controllers;

use CMS\Utils\Database;
use CMS\Utils\View;
use CMS\Utils\Request;
use CMS\Models\Settings;
use Exception;

abstract class BaseController
{
    protected Database $db;
    protected View $view;
    protected Request $request;
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . "/../../config/config.php";
        $this->db = Database::getInstance($this->config["database"]);
        $this->view = new View($this->config["views"]);
        $this->request = new Request();
        
        // Skip maintenance check for admin routes
        if (!str_contains($_SERVER["REQUEST_URI"] ?? "", "/admin")) {
            $this->checkMaintenanceMode();
        }
        
        $this->initialize();
    }

    protected function initialize(): void
    {
        // Override in child controllers
    }

    protected function checkMaintenanceMode(): void
    {
        // Skip for admin users
        if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) {
            return;
        }
        
        // Try to check maintenance mode, but fail silently
        try {
            $maintenanceMode = Settings::getBool("maintenance_mode", false);
            if ($maintenanceMode) {
                $this->showMaintenancePage();
            }
        } catch (Exception $e) {
            // Log error but don\'t show 503
            error_log("Maintenance check failed: " . $e->getMessage());
        }
    }

    protected function showMaintenancePage(): void
    {
        http_response_code(503);
        header("Retry-After: 3600");
        
        $message = Settings::get("maintenance_message", "Site under maintenance");
        
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Maintenance</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>Site Maintenance</h1>
        <p>' . htmlspecialchars($message) . '</p>
    </div>
</body>
</html>";
        exit;
    }

    protected function render(string $template, array $data = []): void
    {
        if (!isset($data["settings"])) {
            try {
                $data["settings"] = Settings::getAll();
            } catch (Exception $e) {
                $data["settings"] = [];
            }
        }
        
        if (!isset($data["current_user"]) && $this->getCurrentUserId()) {
            $userModel = new \CMS\Models\User();
            $user = $userModel->find($this->getCurrentUserId());
            $data["current_user"] = $user ? $user->toArray() : null;
        }
        
        if (!isset($data["csrf_token"])) {
            $data["csrf_token"] = $this->generateCsrfToken();
        }
        
        if (!isset($data["flash"])) {
            $data["flash"] = $this->getFlash();
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
        header("Content-Type: application/json");
        echo json_encode($data);
        exit;
    }

    protected function getParam(string $key, $default = null, string $method = "get")
    {
        if (strtolower($method) === "post") {
            return $this->request->post($key, $default);
        }
        return $this->request->get($key, $default);
    }

    protected function isPost(): bool
    {
        return $this->request->isPost();
    }

    protected function validateCsrfToken(): bool
    {
        if (!$this->isPost()) {
            return true;
        }

        $token = $this->getParam("_token", "", "post");
        $sessionToken = $_SESSION["_token"] ?? "";

        return hash_equals($sessionToken, $token);
    }

    protected function generateCsrfToken(): string
    {
        if (!isset($_SESSION["_token"])) {
            $_SESSION["_token"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["_token"];
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION["flash"] = [
            "type" => $type,
            "message" => $message
        ];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION["flash"] ?? null;
        unset($_SESSION["flash"]);
        return $flash;
    }

    protected function getCurrentUserId(): ?int
    {
        return isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : null;
    }

    protected function isAuthenticated(): bool
    {
        return $this->getCurrentUserId() !== null;
    }

    protected function isAdmin(): bool
    {
        return isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->setFlash("error", "Please login to continue");
            $this->redirect("/admin/login");
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            $this->setFlash("error", "Admin access required");
            $this->redirect("/admin/dashboard");
        }
    }
}
';

// Save the fixed BaseController
file_put_contents('src/Controllers/BaseController_fixed.php', $fixCode);
echo "Fixed BaseController created\n";

// Now let's commit and deploy
exec('cp src/Controllers/BaseController_fixed.php src/Controllers/BaseController.php');
echo "BaseController replaced\n";
?>