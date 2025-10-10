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
            foreach ($this->groupStack as $group) {
                $prefix .= $group['prefix'] ?? '';
                if (isset($group['namespace'])) {
                    $namespace = $namespace ? $namespace . '\\' . $group['namespace'] : $group['namespace'];
                }
                if (isset($group['middleware'])) {
                    $middleware = array_merge($middleware, (array)$group['middleware']);
                }
            }
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

    public function post(string $pattern, string $handler, array $options = []):
    {
        $this->addRoute('POST', $pattern, $handler, $options);
    }

    public function dispatch(): void
    {
        $matchedRoute = $this->findMatchingRoute();

        // DEBUGGING
        var_dump($matchedRoute);
        exit();

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
            if (!$this->auth->check()) {
                header('Location: /admin/login');
                exit();
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
        $controllerClass = 'CMS\Controllers\' . $route['controller'];
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