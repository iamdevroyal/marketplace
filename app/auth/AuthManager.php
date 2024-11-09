<?php
// auth/AuthManager.php
namespace Auth;

class AuthManager {
    private static $instance = null;
    private $user = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function attempt($email, $password) {
        $userModel = new \Models\User();
        $user = $userModel->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->login($user);
            return true;
        }
        
        return false;
    }
    
    public function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $this->user = $user;
    }
    
    public function logout() {
        unset($_SESSION['user_id']);
        $this->user = null;
    }
    
    public function check() {
        return $this->user !== null;
    }
    
    public function user() {
        if ($this->user === null && isset($_SESSION['user_id'])) {
            $userModel = new \Models\User();
            $this->user = $userModel->find($_SESSION['user_id']);
        }
        return $this->user;
    }

    // New method to check if the current user is an admin
    public function isAdmin() {
        $user = $this->user();
        return $user && ($user['is_admin'] === 1 || $user['is_admin'] === true);
    }

    // New method to check if the current user is a vendor
    public function isVendor() {
        $user = $this->user();
        return $user && ($user['is_vendor'] === 1 || $user['is_vendor'] === true);
    }
}