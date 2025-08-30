<?php
/**
 * Local Development Configuration Override
 * 
 * This file overrides production settings for local development.
 * It will be loaded after config.php if it exists.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */

// Production database settings for shared hosting
define('DB_HOST', 'localhost');
define('DB_NAME', 'dalthaus_photocms');
define('DB_USER', 'dalthaus_photocms');
define('DB_PASS', 'f-I*GSo^Urt*k*&#');

// Set to production mode
define('ENV', 'production');

// Use local admin credentials
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Production settings
define('LOG_LEVEL', 'error');
define('CACHE_ENABLED', true);