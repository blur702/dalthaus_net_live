<?php

declare(strict_types=1);

namespace CMS\Utils;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }

    public function get(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return strtok($uri, '?');
    }

    /**
     * Get uploaded file information
     *
     * @param string $key The file input name
     * @return array|null Returns file array or null if not found
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if a file was uploaded
     *
     * @param string $key The file input name
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
               is_array($this->files[$key]) &&
               $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Get all uploaded files
     *
     * @return array
     */
    public function files(): array
    {
        return $this->files;
    }
}