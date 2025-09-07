<?php

declare(strict_types=1);

/**
 * Production Configuration File
 * 
 * This file contains secure production settings for the CMS application.
 * Copy this file to config.php and update with your production values.
 * 
 * IMPORTANT: Store sensitive credentials in environment variables
 * 
 * @package CMS
 * @author  Security Auditor
 * @version 1.0.0
 */

return [
    /**
     * Database Configuration
     * Use environment variables for sensitive data
     */
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'dbname' => $_ENV['DB_NAME'] ?? 'cms_db',
        'username' => $_ENV['DB_USER'] ?? 'cms_user',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            // Enable SSL/TLS for database connection
            PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ]
    ],

    /**
     * Application Configuration
     */
    'app' => [
        'name' => 'CMS Application',
        'version' => '1.0.0',
        'timezone' => 'America/New_York',
        'debug' => false, // MUST be false in production
        'base_url' => 'https://yourdomain.com', // Use HTTPS in production
        'upload_path' => __DIR__ . '/../uploads/',
        'max_upload_size' => 10485760, // 10MB in bytes
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'items_per_page' => 10,
        'maintenance_mode' => false,
        'maintenance_ips' => [] // IPs allowed during maintenance
    ],

    /**
     * Security Configuration
     */
    'security' => [
        'session_name' => 'cms_session_' . hash('sha256', $_SERVER['HTTP_HOST'] ?? 'default'),
        'session_lifetime' => 3600, // 1 hour
        'csrf_token_name' => '_token',
        'password_min_length' => 12, // Increased from 8
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => true,
        'login_max_attempts' => 5,
        'login_lockout_time' => 900, // 15 minutes
        'secure_cookies' => true, // MUST be true for HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'force_https' => true,
        'enable_2fa' => false, // Enable when 2FA is implemented
        'api_rate_limit' => 100, // Requests per minute
        'api_key_required' => true
    ],

    /**
     * View Configuration
     */
    'views' => [
        'cache_enabled' => true, // Enable view caching in production
        'cache_path' => __DIR__ . '/../cache/views/',
        'cache_ttl' => 3600, // 1 hour
        'default_layout' => 'default',
        'admin_layout' => 'admin',
        'minify_html' => true
    ],

    /**
     * Routing Configuration
     */
    'routing' => [
        'default_controller' => 'Home',
        'default_action' => 'index',
        'admin_prefix' => 'admin',
        'url_suffix' => '',
        'case_sensitive' => false,
        'trailing_slash' => false
    ],

    /**
     * TinyMCE Configuration
     */
    'tinymce' => [
        'api_key' => $_ENV['TINYMCE_API_KEY'] ?? '', // Use cloud version with API key
        'plugins' => [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
            'fullscreen', 'insertdatetime', 'media', 'table', 'help',
            'wordcount', 'pagebreak'
        ],
        'toolbar' => 'undo redo | blocks | bold italic forecolor | ' .
                    'alignleft aligncenter alignright alignjustify | ' .
                    'bullist numlist outdent indent | removeformat | ' .
                    'pagebreak | help',
        'height' => 400,
        'content_css' => '/assets/css/editor.css',
        'images_upload_url' => '/admin/upload',
        'automatic_uploads' => true,
        'file_picker_types' => 'image'
    ],

    /**
     * Email Configuration
     */
    'email' => [
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
        'smtp_port' => (int)($_ENV['SMTP_PORT'] ?? 587),
        'smtp_username' => $_ENV['SMTP_USER'] ?? '',
        'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
        'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@example.com',
        'from_name' => 'CMS Application',
        'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com'
    ],

    /**
     * Cache Configuration
     */
    'cache' => [
        'enabled' => true, // Enable caching in production
        'driver' => 'file', // Options: file, memcached, redis
        'default_ttl' => 3600,
        'path' => __DIR__ . '/../cache/',
        'file_extension' => '.cache',
        'memcached_servers' => [
            ['127.0.0.1', 11211]
        ],
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        'redis_password' => $_ENV['REDIS_PASSWORD'] ?? null
    ],

    /**
     * Error Handling
     */
    'errors' => [
        'display_errors' => false, // MUST be false in production
        'log_errors' => true,
        'error_log_path' => __DIR__ . '/../logs/error.log',
        'exception_handler' => true,
        'error_reporting_level' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
        'send_error_emails' => true,
        'error_email_recipient' => $_ENV['ERROR_EMAIL'] ?? 'admin@example.com'
    ],

    /**
     * Logging Configuration
     */
    'logging' => [
        'enabled' => true,
        'level' => 'warning', // Options: debug, info, warning, error, critical
        'path' => __DIR__ . '/../logs/',
        'max_files' => 30, // Keep 30 days of logs
        'log_security_events' => true,
        'log_database_queries' => false, // Only enable for debugging
        'log_performance' => true
    ],

    /**
     * Security Monitoring
     */
    'monitoring' => [
        'enable_intrusion_detection' => true,
        'alert_on_suspicious_activity' => true,
        'block_suspicious_ips' => true,
        'blocked_ip_file' => __DIR__ . '/../logs/blocked_ips.txt',
        'suspicious_patterns' => [
            'sql_injection' => '/(\bunion\b|\bselect\b.*\bfrom\b|\binsert\b.*\binto\b|\bdelete\b.*\bfrom\b|\bdrop\b.*\btable\b)/i',
            'xss_attempt' => '/<script|javascript:|onerror=|onload=/i',
            'path_traversal' => '/\.\.\/|\.\.\\\\/',
            'command_injection' => '/;\s*(ls|cat|wget|curl|bash|sh)\s/i'
        ]
    ],

    /**
     * Backup Configuration
     */
    'backup' => [
        'enabled' => true,
        'path' => __DIR__ . '/../backups/',
        'database_backup' => true,
        'files_backup' => true,
        'backup_schedule' => 'daily', // Options: hourly, daily, weekly
        'retention_days' => 30,
        'encrypt_backups' => true,
        'backup_key' => $_ENV['BACKUP_ENCRYPTION_KEY'] ?? null
    ],

    /**
     * File Upload Security
     */
    'upload_security' => [
        'scan_uploads' => true,
        'reprocess_images' => true,
        'strip_metadata' => true,
        'randomize_filenames' => true,
        'quarantine_suspicious' => true,
        'quarantine_path' => __DIR__ . '/../quarantine/',
        'virus_scan_command' => '/usr/bin/clamscan', // Path to ClamAV
        'max_upload_size' => 10485760, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        'blocked_extensions' => ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi', 'exe', 'com', 'bat', 'cmd', 'vbs', 'js']
    ]
];