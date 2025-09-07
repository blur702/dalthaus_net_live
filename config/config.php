<?php

declare(strict_types=1);

/**
 * CMS Configuration File
 * 
 * This file contains all configuration settings for the CMS application.
 * Modify these settings according to your environment and requirements.
 * 
 * @package CMS
 * @author  Kevin
 * @version 1.0.0
 */

return [
    /**
     * Database Configuration
     */
    'database' => [
        'host' => 'localhost',
        'dbname' => 'cms_db',
        'username' => 'cms_user',
        'password' => 'cms_password',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],

    /**
     * Application Configuration
     */
    'app' => [
        'name' => 'CMS Application',
        'version' => '1.0.0',
        'timezone' => 'America/New_York',
        'debug' => false,
        'base_url' => 'http://localhost',
        'upload_path' => __DIR__ . '/../uploads/',
        'max_upload_size' => 10485760, // 10MB in bytes
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'items_per_page' => 10
    ],

    /**
     * Security Configuration
     */
    'security' => [
        'session_name' => 'cms_session',
        'session_lifetime' => 3600, // 1 hour
        'csrf_token_name' => '_token',
        'password_min_length' => 8,
        'login_max_attempts' => 5,
        'login_lockout_time' => 900, // 15 minutes
        'secure_cookies' => false, // Set to true for HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ],

    /**
     * View Configuration
     */
    'views' => [
        'cache_enabled' => false,
        'cache_path' => __DIR__ . '/../cache/views/',
        'default_layout' => 'default',
        'admin_layout' => 'admin'
    ],

    /**
     * Routing Configuration
     */
    'routing' => [
        'default_controller' => 'Home',
        'default_action' => 'index',
        'admin_prefix' => 'admin',
        'url_suffix' => '',
        'case_sensitive' => false
    ],

    /**
     * TinyMCE Configuration
     */
    'tinymce' => [
        'api_key' => '', // Add your TinyMCE API key here for cloud version
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
        'content_css' => '/assets/css/editor.css'
    ],

    /**
     * Email Configuration (for future use)
     */
    'email' => [
        'smtp_host' => 'localhost',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_email' => 'noreply@example.com',
        'from_name' => 'CMS Application'
    ],

    /**
     * Cache Configuration
     */
    'cache' => [
        'enabled' => false,
        'default_ttl' => 3600,
        'path' => __DIR__ . '/../cache/',
        'file_extension' => '.cache'
    ],

    /**
     * Error Handling
     */
    'errors' => [
        'display_errors' => true,
        'log_errors' => true,
        'error_log_path' => __DIR__ . '/../logs/error.log',
        'exception_handler' => true
    ]
];