<?php

declare(strict_types=1);

namespace CMS\Utils;

use Exception;

class Router
{
    private array $routes = [];
    private array $params = [];
    private string $requestUri;
    private string $requestMethod;
    private array $config;
    private array $groupStack = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->requestUri = $this->getRequestUri();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addRoute(string $method, string $pattern, string $controller, string $action = 'index'): void
    {
        $prefix = '';
        $namespace = '';

        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            $prefix = $group['prefix'] ?? '';
            $namespace = $group['namespace'] ?? '';
        }

        // Build the full controller path
        $fullController = $controller;
        if ($namespace) {
            $fullController = $namespace . '\\' . $controller;
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->normalizePattern($prefix . $pattern),
            'controller' => $fullController,
            'action' => $action,
        ];
    }

    public function get(string $pattern, string $controller, string $action = 'index'): void
    {
        $this->addRoute('GET', $pattern, $controller, $action);
    }

    public function post(string $pattern, string $controller, string $action = 'index'): void
    {
        $this->addRoute('POST', $pattern, $controller, $action);
    }

    public function dispatch(): void
    {
        $matchedRoute = $this->findMatchingRoute();

        if ($matchedRoute === null) {
            $this->handleNotFound();
            return;
        }

        $this->params = $matchedRoute['params'];
        $this->executeController($matchedRoute);
    }

    private function findMatchingRoute(): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->requestMethod) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $this->requestUri);
            if ($params !== false) {
                $route['params'] = $params;
                return $route;
            }
        }
        return null;
    }

    private function matchPattern(string $pattern, string $uri): array|false
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#i';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            return $matches;
        }
        return false;
    }

    private function executeController(array $route): void
    {
        $controllerClass = 'CMS\\Controllers\\' . $route['controller'];
        $actionName = $route['action'];

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionName)) {
            throw new Exception("Action {$actionName} not found in controller {$controllerClass}");
        }

        call_user_func_array([$controller, $actionName], $this->params);
    }

    private function handleNotFound(): void
    {
        // Prevent Cloudflare from caching 404 responses
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        http_response_code(404);
        
        // Try to render the proper 404 page using the View system
        try {
            $config = require __DIR__ . '/../../config/config.php';
            $view = new View($config['views']);
            
            // Get site settings if database is available
            $settings = [];
            try {
                $db = Database::getInstance($config['database']);
                if ($db !== null) {
                    $settings = \CMS\Models\Settings::getAll();
                }
            } catch (\Exception $e) {
                // Use default settings if database is unavailable
                $settings = [
                    'site_name' => 'Site',
                    'site_description' => '',
                    'site_url' => '',
                    'admin_email' => ''
                ];
            }
            
            $view->layout('default');
            $view->render('errors/404', [
                'page_title' => 'Page Not Found',
                'settings' => $settings,
                'current_user' => null
            ]);
        } catch (\Exception $e) {
            // Fallback to simple 404 if view rendering fails
            echo "<!DOCTYPE html><html><head><title>404 - Page Not Found</title></head>";
            echo "<body><h1>404 - Page Not Found</h1>";
            echo "<p>The page you requested could not be found.</p>";
            echo "<p><a href='/'>Return to Homepage</a></p></body></html>";
        }
    }

    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return rtrim($uri, '/') ?: '/';
    }

    private function normalizePattern(string $pattern): string
    {
        return rtrim($pattern, '/') ?: '/';
    }
}