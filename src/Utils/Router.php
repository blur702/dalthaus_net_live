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
    private \CMS\Utils\Database $db;
    private \CMS\Utils\Auth $auth;

    public function __construct(array $config, \CMS\Utils\Database $db)
    {
        $this->config = $config;
        $this->db = $db;
        $this->auth = new \CMS\Utils\Auth($this->db, $this->config['security']);
        $this->requestUri = $this->getRequestUri();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addRoute(string $method, string $pattern, string $handler, array $options = []): void
    {
        $prefix = '';
        $namespace = '';
        $middleware = [];

        if (!empty($this->groupStack)) {
            $currentGroup = end($this->groupStack);
            $prefix = $currentGroup['prefix'] ?? '';
            $namespace = $currentGroup['namespace'] ?? '';
            $middleware = isset($currentGroup['middleware']) ? (array)$currentGroup['middleware'] : [];
        }

        if (isset($options['middleware'])) {
            $middleware = array_merge($middleware, (array)$options['middleware']);
        }

        [$controller, $action] = strpos($handler, '@') !== false ? explode('@', $handler, 2) : [$handler, 'index'];

        $fullController = $controller;
        if ($namespace) {
            $fullController = $namespace . '\\' . $controller;
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $this->normalizePattern($prefix . $pattern),
            'controller' => $fullController,
            'action' => $action,
            'middleware' => array_unique($middleware),
        ];
    }

    public function get(string $pattern, string $handler, array $options = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $options);
    }

    public function post(string $pattern, string $handler, array $options = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $options);
    }

    public function dispatch(): void
    {
        $matchedRoute = $this->findMatchingRoute();

        if ($matchedRoute === null) {
            $this->handleNotFound();
            return;
        }

        if (!empty($matchedRoute['middleware'])) {
            foreach ($matchedRoute['middleware'] as $middleware) {
                $this->executeMiddleware($middleware);
            }
        }

        $this->params = $matchedRoute['params'];
        $this->executeController($matchedRoute);
    }

    private function executeMiddleware(string $middleware): void
    {
        if ($middleware === 'auth') {
            $isAuthenticated = $this->auth->check();

            // Log authentication check
            error_log("Router middleware auth check: " . ($isAuthenticated ? 'PASS' : 'FAIL'));
            error_log("Request URI: " . $this->requestUri);
            error_log("Request Method: " . $this->requestMethod);

            if (!$isAuthenticated) {
                // Check if this is an AJAX/API request expecting JSON
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                $isApiRequest = strpos($this->requestUri, '/api/') !== false ||
                               strpos($this->requestUri, '/upload/') !== false ||
                               strpos($this->requestUri, '/autosave') !== false;

                error_log("Is AJAX request: " . ($isAjax ? 'yes' : 'no'));
                error_log("Is API request: " . ($isApiRequest ? 'yes' : 'no'));
                error_log("X-Requested-With header: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));

                if ($isAjax || $isApiRequest) {
                    error_log("Returning 401 JSON response for unauthenticated AJAX/API request");
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Unauthorized. Please log in.']);
                    exit();
                }

                // Regular page request - redirect to login
                error_log("Redirecting to login page");
                header('Location: /admin/login');
                exit();
            } else {
                error_log("Auth middleware passed - user is authenticated");
            }
        }
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

        $controller = new $controllerClass($this->db, $this->auth, $this->config);

        if (!method_exists($controller, $actionName)) {
            throw new Exception("Action {$actionName} not found in controller {$controllerClass}");
        }

        call_user_func_array([$controller, $actionName], $this->params);
    }

    private function handleNotFound(): void
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        http_response_code(404);
        
        try {
            $view = new View($this->config['views']);
            $settings = \CMS\Models\Settings::getAll($this->db);
            $view->layout('default');
            $view->render('errors/404', ['page_title' => 'Page Not Found', 'settings' => $settings, 'current_user' => null]);
        } catch (\Exception $e) {
            echo "<!DOCTYPE html><html><head><title>404 - Page Not Found</title></head><body><h1>404 - Page Not Found</h1></body></html>";
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