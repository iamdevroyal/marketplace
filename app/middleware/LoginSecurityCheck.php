<?php 
// Login Security enhancement middleware
// middleware/LoginSecurityCheck.php
namespace Middleware;

class AdminSecurityCheck {
    private $auth;
    private $db;
    
    public function __construct($auth, $db) {
        $this->auth = $auth;
        $this->db = $db;
    }
    
    public function verify() {
        if (!$this->auth->check()) {
            return false;
        }
        
        $user = $this->auth->user();
        
        // Check if user is active and admin
        if (!$user['is_admin'] || $user['status'] !== 'active') {
            return false;
        }
        
        // Check for suspicious activity
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_attempts
            FROM login_attempts
            WHERE user_id = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$user['id']]);
        $result = $stmt->fetch();
        
        if ($result['failed_attempts'] >= 5) {
            // Lock the account
            $this->db->prepare("UPDATE users SET status = 'locked' WHERE id = ?")
                     ->execute([$user['id']]);
            return false;
        }
        
        return true;
    }
}