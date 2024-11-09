<?php
// controllers/UserController.php
namespace Controllers;

use Core\Request;
use Auth\AuthManager;
use Traits\RenderTrait;
use Models\Order;
use Exception;

class UserController {
    use RenderTrait;
    
    private $userModel;
    private $auth;
    private $orderModel;
    
    public function __construct() {
        $this->userModel = new \Models\User();
        $this->auth = AuthManager::getInstance();
        $this->orderModel = new Order();
    }
    
    /**
     * Display registration form
     */
    public function register(Request $request) {
        // Redirect if already logged in
        if ($this->auth->check()) {
            header('Location: /account');
            exit;
        }

        // Render registration view
        $this->render('user/register', [
            'old_input' => $_SESSION['old_input'] ?? [],
            'page_title' => 'Register'
        ]);
        
        // Clear old input after rendering
        unset($_SESSION['old_input']);
    }
    
    /**
     * Handle user registration
     */
    public function processRegistration(Request $request) {
        try {
            // Validate input
            $this->validateRegistrationInput($request);
            
            // Prepare user data
            $userData = [
                'email' => $request->getBody('email'),
                'password' => $request->getBody('password'),
                'name' => $request->getBody('name')
            ];
            
            // Attempt registration
            $user = $this->userModel->register($userData);
            
            if ($user) {
                // Set success flash message
                $_SESSION['flash_success'] = 'Registration successful! You can now login.';
                
                // Redirect to login page
                header('Location: /login');
                exit;
            }
            
            throw new Exception('Registration failed');

        } catch (Exception $e) {
            // Handle registration errors
            $_SESSION['flash_error'] = $e->getMessage();
            $_SESSION['old_input'] = array_diff_key($request->getBody(), ['password' => '']);
            
            // Render registration view with errors
            $this->render('user/register', [
                'old_input' => $_SESSION['old_input'] ?? [],
                'page_title' => 'Register'
            ]);
            exit;
        }
    }
    
    /**
     * Validate registration input
     * @throws Exception
     */
    private function validateRegistrationInput(Request $request) {
        $email = $request->getBody('email');
        $password = $request->getBody('password');
        $name = $request->getBody('name');
        
        // Basic validation
        if (empty($email) || empty($password) || empty($name)) {
            throw new Exception('All fields are required');
        }
        
        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Password strength validation
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        // Check if email already exists
        if ($this->userModel->findByEmail($email)) {
            throw new Exception('Email already registered');
        }
    }
    
    /**
     * Display login form
     */
    public function login(Request $request) {
        // Redirect if already logged in
        if ($this->auth->check()) {
            header('Location: /account');
            exit;
        }
        
        // Render login view
        $this->render('user/login', [
            'old_input' => $_SESSION['old_input'] ?? [],
            'page_title' => 'Login'
        ]);
        
        // Clear old input after rendering
        unset($_SESSION['old_input']);
    }
    
    /**
     * Handle user login
     */
    public function processLogin(Request $request) {
        $email = $request->getBody('email');
        $password = $request->getBody('password');
        
        try {
            // Validate login credentials
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required');
            }
            
            // Attempt login
            if ($this->auth->attempt($email, $password)) {
                // Clear any previous login attempts
                unset($_SESSION['login_attempts']);
                
                // Set success message
                $_SESSION['flash_success'] = 'Welcome back!';
                
                // Redirect to intended URL or default account page
                $redirect = $_SESSION['intended_url'] ?? '/account';
                unset($_SESSION['intended_url']);
                
                header("Location: $redirect");
                exit;
            } else {
                // Track login attempts
                $this->trackLoginAttempts($email);
                
                throw new Exception('Invalid email or password');
            }
        } catch (Exception $e) {
            // Handle login errors
            $_SESSION['flash_error'] = $e->getMessage();
            $_SESSION['old_input'] = ['email' => $email];
            
            // Render login view with errors
            $this->render('user/login', [
                'old_input' => $_SESSION['old_input'] ?? [],
                'page_title' => 'Login'
            ]);
            exit;
        }
    }
    
    /**
     * Track and limit login attempts
     */
    private function trackLoginAttempts($email) {
        // Initialize login attempts tracking
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
        
        // Add current attempt
        $_SESSION['login_attempts'][] = [
            'email' => $email,
            'time' => time()
        ];
        
        // Remove attempts older than 1 hour
        $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) {
            return $attempt['time'] > (time() - 3600);
        });
        
        // Check if too many attempts
        if (count($_SESSION['login_attempts']) > 5) {
            // Implement additional security measures (e.g., temporary account lock)
            throw new Exception('Too many login attempts. Please try again later.');
        }
    }
    
    /**
     * Display user account page
     */
    public function account(Request $request) {
        // Require authentication
        $this->requireAuth('/account');
        
        $user = $this->auth->user();
        
        // Get user's orders
        $orders = $this->orderModel->findByUserId($user['id']);
        
        // Render account view
        $this->render('user/account', [
            'user' => $user,
            'orders' => $orders,
            'page_title' => 'My Account'
        ]);
    }
    
    /**
     * Display profile edit form
     */
    public function edit(Request $request) {
        // Require authentication
        $this->requireAuth('/account/edit');
        
        $user = $this->auth->user();
        
        // Render edit view
        $this->render('user/edit', [
            'user' => $user,
            'old_input' => $_SESSION['old_input'] ?? [],
            'page_title' => 'Edit Profile'
        ]);
        
        // Clear old input
        unset($_SESSION['old_input']);
    }
    
    /**
     * Handle profile update
     */
    public function update(Request $request) {
        // Require authentication
        $this->requireAuth('/account/edit');
        
        try {
            $userId = $this->auth->user()['id'];
            $userData = $this->validateProfileUpdate($request);
            
            // Update user profile
            $user = $this->userModel->update($userId, $userData);
            
            $_SESSION['flash_success'] = 'Profile updated successfully';
            header('Location: /account');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $_SESSION['old_input'] = array_diff_key($request->getBody(), ['password' => '']);
            
            header('Location: /account/edit');
            exit;
        }
    }

    /**
     * Validate profile update input
     * @throws Exception
     */
    private function validateProfileUpdate(Request $request) {
        $name = $request->getBody('name');
        $email = $request->getBody('email');
        
        // Basic validation
        if (empty($name) || empty($email)) {
            throw new Exception('All fields are required');
        }
        
        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        return [
            'name' => $name,
            'email' => $email,
            'password' => $request->getBody('password') ?: null
        ];
    }

    /**
     * Show the password reset request form
     */
    public function showPasswordResetRequest(Request $request) {
        // Redirect if already logged in
        if ($this->auth->check()) {
            header('Location: /account');
            exit;
        }
        
        // Render password reset request view
        $this->render('user/password/request', [
            'page_title' => 'Reset Password'
        ]);
    }

    /**
     * Handle the password reset request
     */
    public function sendPasswordResetLink(Request $request) {
        try {
            $email = $request->getBody('email');
            
            // Find user
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                throw new Exception('No account found with this email address');
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token
            $this->userModel->storeResetToken($user['id'], $token, $expiry);
            
            // Send reset email
            $resetUrl = "http://{$_SERVER['HTTP_HOST']}/password/reset/$token";
            $this->sendPasswordResetEmail($user['email'], $user['name'], $resetUrl);
            
            $_SESSION['flash_success'] = 'Password reset instructions have been sent to your email';
            header('Location: /login');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $_SESSION['old_input'] = ['email' => $email];
            header('Location: /password/request');
            exit;
        }
    }

    /**
     * Show the password reset form
     */
    public function showPasswordReset(Request $request) {
        $token = $request->getParam('token');

        // Redirect if already logged in
        if ($this->auth->check()) {
            header('Location: /account');
            exit;
        }
        
        // Verify token and get associated email
        $resetData = $this->userModel->findValidResetToken($token);
        if (!$resetData) {
            $_SESSION['flash_error'] = 'Invalid or expired password reset link';
            header('Location: /password/request');
            exit;
        }
        
        // Render password reset view
        $this->render('user/password/reset', [
            'token' => $token,
            'page_title' => 'Reset Password'
        ]);
    }

    /**
     * Handle the password reset
     */
    public function resetPassword(Request $request) {
        try {
            $token = $request->getBody('token');
            $email = $request->getBody('email');
            $password = $request->getBody('password');
            $passwordConfirm = $request->getBody('password_confirm');
            
            // Validate passwords match
            if ($password !== $passwordConfirm) {
                throw new Exception('Passwords do not match');
            }
            // Find and verify reset token
            $resetData = $this->userModel->findValidResetToken($token);
            if (!$resetData || $resetData['email'] !== $email) {
                throw new Exception('Invalid or expired password reset link');
            }
            
            // Update password
            $this->userModel->updatePassword($resetData['user_id'], $password);
            
            // Invalidate reset token
            $this->userModel->invalidateResetToken($token);
            
            $_SESSION['flash_success'] = 'Your password has been reset successfully';
            header('Location: /login');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: /password/reset/$token");
            exit;
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $name, $resetUrl) {
        $to = $email;
        $subject = "Reset Your Password";
        
        $message = "Hello $name,\n\n";
        $message .= "You recently requested to reset your password. Click the link below to reset it:\n\n";
        $message .= $resetUrl . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Best regards,\nYour Website Team";
        
        $headers = "From: noreply@yourwebsite.com\r\n";
        $headers .= "Reply-To: support@yourwebsite.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        mail($to, $subject, $message, $headers);
    }
    
    /**
     * Handle user logout
     */
    public function logout(Request $request) {
        $this->auth->logout();
        $_SESSION['flash_success'] = 'You have been logged out successfully';
        header('Location: /');
        exit;
    }

    /**
     * Require authentication for certain actions
     */
    private function requireAuth($redirectUrl) {
        if (!$this->auth->check()) {
            $_SESSION['intended_url'] = $redirectUrl;
            $_SESSION['flash_error'] = 'Please log in to access this page';
            header('Location: /login');
            exit;
        }
    }
}