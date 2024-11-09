<?php
// helpers/csrf_helper.php

/**
 * Generate a CSRF token
 * @param int $lifetime Token lifetime in seconds
 * @return string
 */
function csrf_generate($lifetime = 3600) {
    $currentTime = time();
    
    // Check if token exists and is not expired
    if (empty($_SESSION['_csrf_token']) || 
        !isset($_SESSION['_csrf_token_time']) || 
        ($currentTime - $_SESSION['_csrf_token_time']) > $lifetime) {
        
        // Generate new token
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token_time'] = $currentTime;
    }
    
    return $_SESSION['_csrf_token'];
}

/**
 * Verify a CSRF token
 * @param string $token Token to verify
 * @return bool
 */
function csrf_verify($token) {
    // Timing-safe comparison
    return isset($_SESSION['_csrf_token']) && 
           hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Generate a CSRF token input field
 * @return string
 */
function csrf_field() {
    return sprintf(
        '<input type="hidden" name="_token" value="%s">',
        csrf_generate()
    );
}

/**
 * Regenerate CSRF token
 * @return string
 */
function csrf_regenerate() {
    unset($_SESSION['_csrf_token'], $_SESSION['_csrf_token_time']);
    return csrf_generate();
}