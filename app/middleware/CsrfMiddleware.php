<?php
// src/Middleware/CsrfMiddleware.php
namespace Middleware;

use Core\Request;

class CsrfMiddleware {
    public function handle(Request $request) {
        // Only validate CSRF for state-changing requests
        $csrfMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        
        if (in_array($request->getMethod(), $csrfMethods)) {
            // Skip CSRF check for specific routes if needed
            $excludedRoutes = [
                '/webhook/stripe',
                '/api/external-callback'
            ];
            
            $currentPath = $_SERVER['REQUEST_URI'];
            
            if (!in_array($currentPath, $excludedRoutes)) {
                $submittedToken = $request->getBody('_token');
                
                if (!$submittedToken || !csrf_verify($submittedToken)) {
                    // Log potential CSRF attempt
                    error_log("CSRF token validation failed for route: {$currentPath}");
                    
                    // Set error message
                    $_SESSION['flash_error'] = 'Invalid security token. Please try again.';
                    
                    // Redirect back or to a safe page
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
                    header("Location: {$referrer}");
                    exit;
                }
            }
        }
        
        return true;
    }
}