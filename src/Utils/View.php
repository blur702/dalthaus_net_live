<?php

declare(strict_types=1);

namespace CMS\Utils;

use Exception;

/**
 * View Class
 * 
 * Handles view rendering with template support and data passing.
 * Provides a simple template system with layouts and partial support.
 * 
 * @package CMS\Utils
 * @author  Kevin
 * @version 1.0.0
 */
class View
{
    /**
     * View data
     */
    private array $data = [];

    /**
     * Current layout
     */
    private ?string $layout = null;

    /**
     * View configuration
     */
    private array $config;

    /**
     * Views base path
     */
    private string $viewsPath;

    /**
     * Constructor
     * 
     * @param array $config View configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->viewsPath = __DIR__ . '/../Views/';
    }

    /**
     * Set view data
     * 
     * @param string|array $key Data key or array of data
     * @param mixed $value Data value (ignored if $key is array)
     * @return self
     */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Set layout template
     * 
     * @param string $layout Layout name
     * @return self
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Render view template
     * 
     * @param string $template Template name
     * @param array $data Additional data
     * @return string Rendered HTML
     * @throws Exception When template file not found
     */
    public function render(string $template, array $data = []): string
    {
        // Merge additional data
        $viewData = array_merge($this->data, $data);
        
        // Render the main template
        $content = $this->renderTemplate($template, $viewData);
        
        // If layout is set, wrap content in layout
        if ($this->layout !== null) {
            $layoutData = array_merge($viewData, ['content' => $content]);
            $content = $this->renderLayout($this->layout, $layoutData);
        }
        
        return $content;
    }

    /**
     * Render template and return output
     * 
     * @param string $template Template name
     * @param array $data Template data
     * @return string
     * @throws Exception When template file not found
     */
    private function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = $this->getTemplatePath($template);
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template {$template} not found at {$templatePath}");
        }
        
        // Extract data for template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        try {
            include $templatePath;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Render layout template
     * 
     * @param string $layout Layout name
     * @param array $data Layout data
     * @return string
     * @throws Exception When layout file not found
     */
    private function renderLayout(string $layout, array $data = []): string
    {
        $layoutPath = $this->viewsPath . 'Layouts/' . $layout . '.php';
        
        if (!file_exists($layoutPath)) {
            throw new Exception("Layout {$layout} not found at {$layoutPath}");
        }
        
        // Extract data for layout
        extract($data);
        
        // Start output buffering
        ob_start();
        
        try {
            include $layoutPath;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Include a partial template
     * 
     * @param string $partial Partial name
     * @param array<string, mixed> $data Partial data
     * @return void
     * @throws Exception When partial file not found
     */
    public function partial(string $partial, array $data = []): void
    {
        echo $this->renderPartial($partial, $data);
    }

    /**
     * Render a partial template
     * 
     * @param string $partial Partial name
     * @param array<string, mixed> $data Partial data
     * @return string
     * @throws Exception When partial file not found
     */
    public function renderPartial(string $partial, array $data = []): string
    {
        $partialPath = $this->viewsPath . 'Partials/' . $partial . '.php';
        
        if (!file_exists($partialPath)) {
            throw new Exception("Partial {$partial} not found at {$partialPath}");
        }
        
        // Merge with current view data
        $partialData = array_merge($this->data, $data);
        
        // Extract data for partial
        extract($partialData);
        
        // Start output buffering
        ob_start();
        
        try {
            include $partialPath;
            $content = ob_get_clean();
            return $content !== false ? $content : '';
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Get full template path
     * 
     * @param string $template Template name
     * @return string
     */
    private function getTemplatePath(string $template): string
    {
        // Handle admin vs public templates
        if (str_contains($template, 'admin/')) {
            return $this->viewsPath . 'Admin/' . str_replace('admin/', '', $template) . '.php';
        }
        
        return $this->viewsPath . 'Public/' . $template . '.php';
    }

    /**
     * Escape HTML output
     * 
     * @param string $string String to escape
     * @return string
     */
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Magic method to get view data
     * 
     * @param string $key Data key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Magic method to set view data
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic method to check if view data exists
     * 
     * @param string $key Data key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Format date for display
     * 
     * @param string $date Date string
     * @param string $format Date format
     * @return string
     */
    public function formatDate(string $date, string $format = 'Y-m-d'): string
    {
        $timestamp = strtotime($date);
        return $timestamp !== false ? date($format, $timestamp) : $date;
    }

    /**
     * Format date for display with time
     * 
     * @param string $date Date string
     * @return string
     */
    public function formatDateTime(string $date): string
    {
        $timestamp = strtotime($date);
        return $timestamp !== false ? date('F j, Y \a\t g:i A', $timestamp) : $date;
    }

    /**
     * Format time ago string
     * 
     * @param string $date Date string
     * @return string
     */
    public function formatTimeAgo(string|int $date): string
    {
        if (empty($date)) {
            return '';
        }
        
        // If already a timestamp, use it directly
        $timestamp = is_int($date) ? $date : strtotime($date);
        if ($timestamp === false) {
            return is_string($date) ? $date : ''; // Return original if can't parse
        }
        
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }

    /**
     * Truncate text with ellipsis
     * 
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to add
     * @return string
     */
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Generate CSRF token field
     * 
     * @return string
     */
    public function csrfField(): string
    {
        $token = $_SESSION['_token'] ?? '';
        return '<input type="hidden" name="_token" value="' . $this->escape($token) . '">';
    }

    /**
     * Generate URL
     * 
     * @param string $path URL path
     * @return string
     */
    public function url(string $path): string
    {
        $baseUrl = $this->config['base_url'] ?? '';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Generate asset URL
     * 
     * @param string $path Asset path
     * @return string
     */
    public function asset(string $path): string
    {
        return $this->url('assets/' . ltrim($path, '/'));
    }
}
