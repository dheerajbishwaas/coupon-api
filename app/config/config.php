<?php
// Environment Settings
define('APP_ENV', 'development'); // 'production' or 'development'
define('APP_DEBUG', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'coupon');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


// API Settings
define('API_RATE_LIMIT', 100); // Requests per minute
define('API_SECRET_KEY', 'your-secret-key-here');

// Path Constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Timezone
date_default_timezone_set('UTC');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}