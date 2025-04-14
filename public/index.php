<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/database.php';

$router = new AltoRouter();

// Set the correct base path (just the path portion after domain)
$router->setBasePath('/coupon-api/public');

// Include routes
require_once __DIR__ . '/../app/routes/api.php';

// Match current request
$match = $router->match();

if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    // For debugging - see what's being matched
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Matched route: ' . print_r($match, true));
    
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    echo json_encode([
        'error' => 'Endpoint not found',
        'request_uri' => $_SERVER['REQUEST_URI'],
        'base_path' => '/coupon-api/public',
        'debug_info' => $match
    ]);
}