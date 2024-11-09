<?php
// models/User.php
namespace Models;
use Core\Database;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Register a new user
     * 
     * @param array $data User data including 'email', 'password', 'name'
     * @return array|bool Returns user data on success, false on failure
     * @throws \Exception If validation fails or email already exists
     */
    public function register(array $data): array|bool {
        // Validate input data
        $this->validateRegistrationData($data);
        
        // Check if email already exists
        if ($this->findByEmail($data['email'])) {
            throw new \Exception('Email address already registered');
        }
        
        try {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Prepare insert statement
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            // Execute with data
            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword
            ]);
            
            // Get the newly created user
            $userId = $this->db->lastInsertId();
            return $this->find($userId);
            
        } catch (\PDOException $e) {
            // Log error here if needed
            throw new \Exception('Registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate user registration data
     * 
     * @param array $data
     * @throws \Exception If validation fails
     */
    private function validateRegistrationData(array $data): void {
        // Required fields
        $requiredFields = ['email', 'password', 'name'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("$field is required");
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }
        
        // Validate password strength
        if (strlen($data['password']) < 8) {
            throw new \Exception('Password must be at least 8 characters long');
        }
        
        // You can add more password requirements here
        if (!preg_match('/[A-Z]/', $data['password']) || 
            !preg_match('/[a-z]/', $data['password']) || 
            !preg_match('/[0-9]/', $data['password'])) {
            throw new \Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }
        
        // Validate name length
        if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
            throw new \Exception('Name must be between 2 and 50 characters');
        }
    }

    /**
     * Store password reset token
     */
    public function storeResetToken($userId, $token, $expiry) {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$userId, $token, $expiry]);
    }

    /**
     * Find valid reset token
     */
    public function findValidResetToken($token) {
        $stmt = $this->db->prepare("
            SELECT pr.*, u.email 
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token = ? 
            AND pr.expires_at > NOW()
            AND pr.used = 0
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update user password
     */
    public function updatePassword($userId, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Invalidate reset token
     */
    public function invalidateResetToken($token) {
        $stmt = $this->db->prepare("
            UPDATE password_resets 
            SET used = 1 
            WHERE token = ?
        ");
        return $stmt->execute([$token]);
    }
}