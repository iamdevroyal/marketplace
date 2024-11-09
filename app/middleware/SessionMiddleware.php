<?php
namespace Middleware;

use Core\Request;

class SessionMiddleware {
    public function handle(Request $request, callable $next) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate CSRF token if not exists
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $next($request);
    }
}