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

// Override database settings for local MySQL
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'dalthaus_cms');
define('DB_USER', 'root');
define('DB_PASS', ''); // You'll set this after MySQL installation

// Set to development mode
define('ENV', 'development');

// Use local admin credentials
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Enable debugging
define('LOG_LEVEL', 'debug');
define('CACHE_ENABLED', false);