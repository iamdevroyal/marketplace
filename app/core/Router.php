<?php
namespace Core;

class Router {
    private $routes = [];
    private $request;
    private $auth;

    public function __construct() {
        $this->request = new Request();
        $this->auth = \Auth\AuthManager::getInstance();
        $this->registerRoutes();
    }

    private function registerRoutes() {
        $this->routes = [
            // Authentication Routes
            ['GET', '/login', 'UserController@login'],
            ['POST', '/login', 'UserController@processLogin'],
            ['GET', '/register', 'UserController@register'],
            ['POST', '/register', 'UserController@processRegistration'],
            ['GET', '/logout', 'UserController@logout'],

            // Account Routes
            ['GET', '/account', 'UserController@account', 'auth'],
            ['GET', '/account/edit', 'UserController@edit', 'auth'],
            ['POST', '/account/update', 'UserController@update', 'auth'],
            ['GET', '/account/orders', 'UserController@orders', 'auth'],
            ['GET', '/account/order/{id}', 'UserController@orderDetails', 'auth'],

            // Password Reset Routes
            ['GET', '/password/reset', 'UserController@showPasswordResetRequest'],
            ['POST', '/password/reset', 'UserController@sendPasswordResetLink'],
            ['GET', '/password/reset/{token}', 'UserController@showPasswordReset'],
            ['POST', '/password/reset/{token}', 'UserController@resetPassword'],

            // Admin Routes
            ['GET', '/admin', 'AdminController@dashboard', 'admin'],
            ['GET', '/admin/users', 'AdminController@users', 'admin'],
            ['GET', '/admin/users/edit/{id}', 'AdminController@editUser', 'admin'],
            ['POST', '/admin/users/edit/{id}', 'AdminController@editUser', 'admin'],
            ['GET', '/admin/products', 'AdminController@products', 'admin'],
            ['GET', '/admin/products/create', 'AdminController@createProduct', 'admin'],
            ['POST', '/admin/products/create', 'AdminController@createProduct', 'admin'],
            ['GET', '/admin/products/edit/{id}', 'AdminController@editProduct', 'admin'],
            ['POST', '/admin/products/edit/{id}', 'AdminController@editProduct', 'admin'],
            ['GET', '/admin/orders', 'AdminController@orders', 'admin'],
            ['GET', '/admin/orders/view/{id}', 'AdminController@viewOrder', 'admin'],
            ['POST', '/admin/orders/status/{id}', 'AdminController@updateOrderStatus', 'admin'],
            ['GET', '/admin/settings', 'AdminController@settings', 'admin'],
            ['POST', '/admin/settings', 'AdminController@settings', 'admin'],
            ['GET', '/admin/export', 'AdminController@exportData', 'admin'],

            // Admin Audit Routes
            ['GET', '/admin/audit/logs', 'AdminAuditController@viewLogs', 'admin'],
            ['GET', '/admin/audit/log/{id}', 'AdminAuditController@logDetails', 'admin'],

            // Cart Routes
            ['GET', '/cart', 'CartController@view'],
            ['POST', '/cart/add', 'CartController@add'],
            ['POST', '/cart/update', 'CartController@update'],
            ['POST', '/cart/remove', 'CartController@remove'],
            ['GET', '/cart/checkout', 'CartController@checkout', 'auth'],
            ['POST', '/cart/process-checkout', 'CartController@processCheckout', 'auth'],

            // Marketplace Routes
            ['GET', '/', 'MarketplaceController@index'],
            ['GET', '/marketplace', 'MarketplaceController@index'],
            ['GET', '/marketplace/category/{category}', 'MarketplaceController@category'],
            ['GET', '/search', 'MarketplaceController@search'],

            // Product Routes
            ['GET', '/product/{id}', 'ProductController@view'],
            ['GET', '/products', 'ProductController@list'],
            ['POST', '/product/review/{id}', 'ProductController@addReview', 'auth'],

            // Vendor Routes
            ['GET', '/vendor/register', 'VendorController@register'],
            ['POST', '/vendor/register', 'VendorController@processRegistration'],
            ['GET', '/vendor/dashboard', 'VendorController@dashboard', 'vendor'],
            ['GET', '/vendor/products', 'VendorController@products', 'vendor'],
            ['GET', '/vendor/products/create', 'VendorController@createProduct', 'vendor'],
            ['POST', '/vendor/products/create', 'VendorController@createProduct', 'vendor'],
            ['GET', '/vendor/products/edit/{id}', 'VendorController@editProduct', 'vendor'],
            ['POST', '/vendor/products/edit/{id}', 'VendorController@editProduct', 'vendor'],
            ['GET', '/vendor/orders', 'VendorController@orders', 'vendor'],
            ['GET', '/vendor/order/{id}', 'VendorController@orderDetails', 'vendor'],
            ['POST', '/vendor/order/update/{id}', 'VendorController@updateOrderStatus', 'vendor'],
        ];
    }

    public function dispatch(Request $request) {
        $path = $this->parsePath($request->getUri());
        $method = $request->method();

        foreach ($this->routes as $route) {
            list($routeMethod, $routePath, $handler, $middleware) = 
                array_pad($route, 4, null);

            if ($method === $routeMethod && $this->matchPath($routePath, $path)) {
                // Apply middleware if exists
                if ($middleware) {
                    $this->applyMiddleware($middleware, $request);
                }

                // Dispatch to controller
                return $this->dispatchToController($handler, $request);
            }
        }

        // 404 Not Found
        $this->handle404();
    }

    private function parsePath($uri) {
        return strtok($uri, '?');
    }

    private function matchPath($routePath, $requestPath) {
        // Convert route with parameters to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        return preg_match("/^{$pattern}$/", $requestPath);
    }

    private function applyMiddleware($middleware, Request $request) {
        switch ($middleware) {
            case 'auth':
                if (!$this->auth->check()) {
                    $_SESSION['intended_url'] = $request->getUri();
                    $_SESSION['flash_error'] = 'Please log in to access this page';
                    header('Location: /login');
                    exit;
                }
                break;
            case 'admin':
                if (!$this->auth->check() || !$this->auth->isAdmin()) {
                    $_SESSION['flash_error'] = 'Administrative access required';
                    header('Location: /login');
                    exit;
                }
                break;
            case 'vendor':
                if (!$this->auth->check() || !$this->auth->isVendor()) {
                    $_SESSION['flash_error'] = 'Vendor access required';
                    header('Location: /vendor/register');
                    exit;
                }
                break;
        }
    }

    private function dispatchToController($handler, Request $request) {
        list($controllerName, $method) = explode('@', $handler);
        $controllerClass = "\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new \Exception("Method {$method} not found in {$controllerName}");
        }

        return call_user_func([$controller, $method], $request);
    }

    private function handle404() {
        http_response_code(404);
        include BASE_PATH . '/views/errors/404.php';
        exit;
    }
}