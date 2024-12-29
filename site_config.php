<?php
// Site configuration
define('SITE_URL', 'http://localhost'); // Change this to your domain

// Set the application subdirectory - change this value to modify the base path
$app_subdir = 'cpms';
define('BASE_PATH', '/' . trim($app_subdir, '/'));
define('APP_NAME', 'CPMS');

// Database configuration - these will be used by conn.php
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Time zone
date_default_timezone_set('Asia/Jakarta'); 
