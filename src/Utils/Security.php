<?php

declare(strict_types=1);

namespace CMS\Utils;

/**
 * Security Utilities Class
 * 
 * Provides security utilities including input sanitization, 
 * XSS protection, and other security-related functions.
 * 
 * @package CMS\Utils
 * @author  Kevin
 * @version 1.0.0
 */
class Security
{
    /**
     * Sanitize input string
     * 
     * @param string $input Input string
     * @param bool $trim Whether to trim whitespace
     * @return string
     */
    public static function sanitize(string $input, bool $trim = true): string
    {
        if ($trim) {
            $input = trim($input);
        }
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of inputs
     * 
     * @param array $inputs Input array
     * @param bool $trim Whether to trim whitespace
     * @return array
     */
    public static function sanitizeArray(array $inputs, bool $trim = true): array
    {
        $sanitized = [];
        
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $trim);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::sanitize($value, $trim);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Clean HTML content (for rich text editor output)
     * 
     * @param string $html HTML content
     * @return string
     */
    public static function cleanHtml(string $html): string
    {
        // List of allowed HTML tags for content
        $allowedTags = [
            'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'blockquote', 'a', 'img', 'table', 'thead', 'tbody',
            'tr', 'td', 'th', 'div', 'span', 'hr'
        ];

        // List of allowed attributes
        $allowedAttributes = [
            'href', 'src', 'alt', 'title', 'class', 'id', 'target', 'width', 'height'
        ];

        // Create allowed tags string
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        
        // Strip unwanted tags
        $html = strip_tags($html, $allowedTagsString);
        
        // Remove potentially dangerous attributes
        $html = preg_replace_callback(
            '/<([^>]+)>/',
            function ($matches) use ($allowedAttributes) {
                $tag = $matches[1];
                
                // Extract tag name
                preg_match('/^(\w+)/', $tag, $tagMatches);
                $tagName = $tagMatches[1] ?? '';
                
                // Extract attributes
                preg_match_all('/(\w+)\s*=\s*["\']([^"\']*)["\']/', $tag, $attrMatches);
                
                $cleanedTag = $tagName;
                
                for ($i = 0; $i < count($attrMatches[0]); $i++) {
                    $attrName = strtolower($attrMatches[1][$i]);
                    $attrValue = $attrMatches[2][$i];
                    
                    // Only allow safe attributes
                    if (in_array($attrName, $allowedAttributes)) {
                        // Additional validation for specific attributes
                        if ($attrName === 'href' || $attrName === 'src') {
                            // Block javascript: and data: URLs
                            if (!preg_match('/^(https?:\/\/|\/|\#)/i', $attrValue)) {
                                continue;
                            }
                        }
                        
                        $cleanedTag .= ' ' . $attrName . '="' . htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8') . '"';
                    }
                }
                
                return '<' . $cleanedTag . '>';
            },
            $html
        );
        
        return $html;
    }

    /**
     * Generate secure random token
     * 
     * @param int $length Token length
     * @return string
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate email address
     * 
     * @param string $email Email address
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     * 
     * @param string $url URL to validate
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create secure URL alias from title
     * 
     * @param string $title Title to convert
     * @param int $maxLength Maximum length
     * @return string
     */
    public static function createUrlAlias(string $title, int $maxLength = 100): string
    {
        // Convert to lowercase
        $alias = strtolower($title);
        
        // Replace spaces and special characters with hyphens
        $alias = preg_replace('/[^a-z0-9]+/', '-', $alias);
        
        // Remove leading/trailing hyphens
        $alias = trim($alias, '-');
        
        // Limit length
        if (strlen($alias) > $maxLength) {
            $alias = substr($alias, 0, $maxLength);
            $alias = rtrim($alias, '-');
        }
        
        return $alias;
    }

    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed file types
     * @param int $maxSize Maximum file size in bytes
     * @return array Validation result
     */
    public static function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 0): array
    {
        $result = [
            'valid' => false,
            'errors' => []
        ];

        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $result['errors'][] = 'No file was uploaded';
            return $result;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['errors'][] = 'File upload error: ' . $file['error'];
            return $result;
        }

        // Check file size
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $result['errors'][] = 'File size exceeds maximum allowed size';
            return $result;
        }

        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                $result['errors'][] = 'File type not allowed';
                return $result;
            }
        }

        // Additional security check: verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Basic MIME type validation for images
        if (!empty($allowedTypes) && in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp'
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                $result['errors'][] = 'Invalid file content';
                return $result;
            }
        }

        $result['valid'] = true;
        return $result;
    }

    /**
     * Hash sensitive data
     * 
     * @param string $data Data to hash
     * @param string $algorithm Hash algorithm
     * @return string
     */
    public static function hash(string $data, string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $data);
    }

    /**
     * Constant-time string comparison
     * 
     * @param string $known Known string
     * @param string $user User provided string
     * @return bool
     */
    public static function compareStrings(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Rate limiting check (simple implementation)
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @param int $windowSeconds Time window in seconds
     * @return bool True if rate limit not exceeded
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 10, int $windowSeconds = 60): bool
    {
        $rateLimitKey = 'rate_limit_' . md5($key);
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'count' => 0,
                'window_start' => time()
            ];
        }
        
        $data = $_SESSION[$rateLimitKey];
        
        // Reset if window has passed
        if ((time() - $data['window_start']) > $windowSeconds) {
            $_SESSION[$rateLimitKey] = [
                'count' => 1,
                'window_start' => time()
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Increment count
        $_SESSION[$rateLimitKey]['count']++;
        
        return true;
    }

    /**
     * Remove XSS vectors from input
     * 
     * @param string $input Input string
     * @return string
     */
    public static function removeXss(string $input): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Remove dangerous patterns
        $patterns = [
            '/<script[^>]*?>.*?<\/script>/si',
            '/<iframe[^>]*?>.*?<\/iframe>/si',
            '/<object[^>]*?>.*?<\/object>/si',
            '/<embed[^>]*?>.*?<\/embed>/si',
            '/<applet[^>]*?>.*?<\/applet>/si',
            '/<form[^>]*?>.*?<\/form>/si',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/on\w+\s*=/i'
        ];
        
        return preg_replace($patterns, '', $input);
    }

    /**
     * Validate and sanitize sort parameters
     * 
     * @param string $sortBy Sort field
     * @param string $sortDir Sort direction
     * @param array $allowedFields Allowed sort fields
     * @return array
     */
    public static function validateSort(string $sortBy, string $sortDir, array $allowedFields): array
    {
        // Validate sort field
        if (!in_array($sortBy, $allowedFields)) {
            $sortBy = $allowedFields[0] ?? 'id';
        }
        
        // Validate sort direction
        $sortDir = strtoupper($sortDir);
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'ASC';
        }
        
        return [$sortBy, $sortDir];
    }

    /**
     * Generate secure filename
     * 
     * @param string $originalName Original filename
     * @return string
     */
    public static function generateSecureFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        
        // Generate unique filename
        $filename = uniqid() . '_' . bin2hex(random_bytes(8));
        
        return $filename . '.' . $extension;
    }
}
