<?php
/**
 * Local Production Configuration Override
 * 
 * This file overrides default settings for production hosting.
 * Constants are only defined if not already set to prevent redefinition warnings.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */

// Production database settings for shared hosting - only define if not already set
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'dalthaus_photocms');
if (!defined('DB_USER')) define('DB_USER', 'dalthaus_photocms');
if (!defined('DB_PASS')) define('DB_PASS', 'f-I*GSo^Urt*k*&#');

// Set to production mode - only if not already set
if (!defined('ENV')) define('ENV', 'production');

// Use local admin credentials - only if not already set
if (!defined('DEFAULT_ADMIN_USER')) define('DEFAULT_ADMIN_USER', 'admin');
if (!defined('DEFAULT_ADMIN_PASS')) define('DEFAULT_ADMIN_PASS', '130Bpm');

// Production settings - only if not already set
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'error');
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', true);