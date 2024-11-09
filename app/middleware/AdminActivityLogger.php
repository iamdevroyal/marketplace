// middleware/AdminActivityLogger.php
<?php
namespace Middleware;

use Core\Database;

class AdminActivityLogger {
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }
    
    public function logActivity($adminId, $action, $details = null, $additionalData = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO admin_activity_logs (
                    admin_id, 
                    action, 
                    details, 
                    ip_address, 
                    user_agent, 
                    created_at
                ) VALUES (
                    :admin_id, 
                    :action, 
                    :details, 
                    :ip_address, 
                    :user_agent, 
                    NOW()
                )
            ");
            
            // Merge additional context with details
            $logDetails = array_merge([
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'http_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            ], (array)$details, $additionalData);
            
            return $stmt->execute([
                'admin_id' => $adminId,
                'action' => $action,
                'details' => json_encode($logDetails),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (\Exception $e) {
            // Log error but don't throw to prevent disrupting main application flow
            error_log('Failed to log admin activity: ' . $e->getMessage());
            return false;
        }
    }
    
    // Example usage methods
    // public function logLogin($adminId) {
    //     return $this->logActivity($adminId, 'admin_login');
    // }
    
    // public function logUserManagement($adminId, $action, $userId) {
    //     return $this->logActivity($adminId, $action, [
    //         'user_id' => $userId
    //     ]);
    // }
    
    // public function logResourceMo dification($adminId, $resourceType, $resourceId, $action) {
    //     return $this->logActivity($adminId, "resource_{$action}", [
    //         'resource_type' => $resourceType,
    //         'resource_id' => $resourceId
    //     ]);
    // }
    
    // public function logSettingsChange($adminId, $settings) {
    //     return $this->logActivity($adminId, 'settings_change', $settings);
    // }
} 