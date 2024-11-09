<?php
// public/index.php

// Start output buffering for header redirects
ob_start();

// Start session
session_start();

// Load configuration
require_once '../config/config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load CSRF helper functions
require_once BASE_PATH . '/helpers/csrf_helper.php';

// Create request and middleware
$request = new Core\Request();
$middleware = new Core\Middleware();

// Add middleware stack
$middleware->add(new Middleware\SessionMiddleware());
$middleware->add(new Middleware\CsrfMiddleware());
$middleware->add(new Middleware\AdminActivityLogger());
$middleware->add(new Middleware\LoginSecurityCheck());

// Dispatch request through middleware
$middleware->run($request, function($request) {
    $router = new Core\Router();
    $router->dispatch($request);
});

// End output buffering and send output
ob_end_flush();