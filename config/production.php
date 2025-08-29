<?php
/**
 * Production Configuration Override
 * 
 * This file contains production-specific settings that override
 * the defaults in includes/config.php
 * 
 * To use: Copy this file to includes/config.local.php on your production server
 * and update the values for your environment.
 */

// Database Configuration
define('DB_HOST', 'localhost');  // Your production DB host
define('DB_NAME', 'dalthaus_photocms');  // Production database name
define('DB_USER', 'dalthaus_photocms');  // Production database user
define('DB_PASS', 'f-I*GSo^Urt*k*&#');  // Production database password

// Environment Setting
define('ENV', 'production');  // Always 'production' for live sites

// Security Settings
define('SECURE_COOKIES', true);  // Enable secure cookies (requires HTTPS)
define('SESSION_LIFETIME', 3600);  // 1 hour session timeout

// Path Configuration  
define('SITE_URL', 'https://dalthaus.net');  // Your production URL
define('UPLOAD_PATH', '/home/username/public_html/uploads');  // Absolute path to uploads
define('CACHE_PATH', '/home/username/public_html/cache');  // Absolute path to cache

// Error Handling
define('DISPLAY_ERRORS', false);  // Never show errors in production
define('LOG_ERRORS', true);  // Always log errors
define('ERROR_LOG_PATH', '/home/username/public_html/logs/error.log');

// Cache Settings
define('CACHE_ENABLED', true);  // Enable caching in production
define('CACHE_TTL', 3600);  // 1 hour cache lifetime

// File Upload Limits
define('UPLOAD_MAX_SIZE', 10485760);  // 10MB max upload
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Python Path (for document converter)
define('PYTHON_PATH', '/usr/bin/python3');  // Verify with: which python3

// Default Admin Credentials (CHANGE IMMEDIATELY after first login!)
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'ChangeThisPassword123!');

// Email Configuration (optional)
define('ADMIN_EMAIL', 'your-email@domain.com');
define('FROM_EMAIL', 'noreply@dalthaus.net');

// Performance Settings
define('COMPRESSION_ENABLED', true);  // Enable output compression
define('MINIFY_HTML', true);  // Minify HTML output

// Maintenance Mode (set to true to enable)
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'Site is currently under maintenance. Please check back soon.');

// Security Headers (added via .htaccess, but defined here for reference)
define('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tiny.cloud; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;");