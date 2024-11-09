<?php
// controllers/AdminAuditController.php
namespace Controllers;

use Core\Request;
use Core\Database;
use Middleware\AdminActivityLogger;
use Auth\AuthManager;
use Traits\RenderTrait;

class AdminAuditController {
    use RenderTrait;

    private $logger;
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = AuthManager::getInstance();
        $this->logger = new AdminActivityLogger($this->db);
        
        // Middleware-like authentication check
        if (!$this->auth->isAdmin()) {
            $_SESSION['flash_error'] = 'Unauthorized access';
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * View Admin Activity Logs
     * @param Request $request
     */
    public function viewLogs(Request $request) {
        try {
            // Pagination and filtering parameters
            $page = $request->getQuery('page', 1);
            $perPage = 50;
            $search = $request->getQuery('search', '');
            $startDate = $request->getQuery('start_date', '');
            $endDate = $request->getQuery('end_date', '');
            $action = $request->getQuery('action', '');
            
            // Prepare base query
            $queryBuilder = $this->prepareLogsQuery($search, $startDate, $endDate, $action);
            
            // Execute count query
            $totalLogs = $this->executeCountQuery($queryBuilder['countQuery'], $queryBuilder['params']);
            
            // Execute logs query with pagination
            $logs = $this->executeLogsQuery($queryBuilder['query'], $queryBuilder['params'], $page, $perPage);
            
            // Get unique actions for filter dropdown
            $uniqueActions = $this->getUniqueActions();
            
            // Calculate pagination
            $totalPages = ceil($totalLogs / $perPage);
            
            // Render logs view
            $this->render('admin/audit/logs', [
                'logs' => $logs,
                'page_title' => 'Admin Activity Logs',
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_logs' => $totalLogs,
                'unique_actions' => $uniqueActions,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'action' => $action
            ]);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'An error occurred while fetching logs', '/admin/dashboard');
        }
    }
    
    /**
     * Prepare logs query
     * @param string $search
     * @param string $startDate
     * @param string $endDate
     * @param string $action
     * @return array
     */
    private function prepareLogsQuery(
        string $search = '', 
        string $startDate = '', 
        string $endDate = '', 
        string $action = ''
    ): array {
        $query = "
            SELECT l.*, u.name as admin_name
            FROM admin_activity_logs l
            JOIN users u ON l.admin_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Add search conditions
        if (!empty($search)) {
            $query .= " AND (u.name LIKE :search OR l.action LIKE :search OR l.details LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        // Add date range conditions
        if (!empty($startDate)) {
            $query .= " AND l.created_at >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $query .= " AND l.created_at <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        // Add action filter
        if (!empty($action)) {
            $query .= " AND l.action = :action";
            $params[':action'] = $action;
        }
        
        // Add ordering
        $query .= " ORDER BY l.created_at DESC";
        
        // Prepare count query
        $countQuery = preg_replace('/SELECT l\.\*, u\.name as admin_name/', 'SELECT COUNT(*)', $query);
        
        return [
            'query' => $query,
            'countQuery' => $countQuery,
            'params' => $params
        ];
    }
    
    /**
     * Execute count query
     * @param string $countQuery
     * @param array $params
     * @return int
     */
    private function executeCountQuery(string $countQuery, array $params): int {
        $countStmt = $this->db->prepare($countQuery);
        
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        
        $countStmt->execute();
        return $countStmt->fetchColumn();
    }
    
    /**
     * Execute logs query with pagination
     * @param string $query
     * @param array $params
     * @param int $page
     * @param int $perPage
     * @return array
     */
    private function executeLogsQuery(
        string $query, 
        array $params, 
        int $page, 
        int $perPage
    ): array {
        // Add pagination to query
        $query .= " LIMIT :offset, :limit";
        
        $stmt = $this->db->prepare($query);
        
        // Bind pagination parameters
        $offset = ($page - 1) * $perPage;
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        
        // Bind other parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get unique actions for filtering
     * @return array
     */
    private function getUniqueActions(): array {
        $actionsStmt = $this->db->query("SELECT DISTINCT action FROM admin_activity_logs");
        return $actionsStmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    /**
     * View Log Details
     * @param Request $request
     */
    public function logDetails(Request $request) {
        try {
            $logId = $request->getParam('id');
            
            $stmt = $this->db->prepare("
                SELECT l.*, u.name as admin_name, u.email as admin_email
                FROM admin_activity_logs l
                JOIN users u ON l.admin_id = u.id
                WHERE l.id = :log_id
            ");
            $stmt->bindValue(':log_id', $logId, \PDO::PARAM_INT);
            $stmt->execute();
            
            $log = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$log) {
                throw new \Exception('Log entry not found');
            }
            
            // Parse JSON details
            $log['details'] = json_decode($log['details'], true);
            
            // Render log details view
            $this->render('admin/audit/log-details', [
                'log' => $log,
                'page_title' => 'Log Details'
            ]);
            
        } catch (\Exception $e) {
            $this->handleError($e, 'An error occurred while fetching log details', '/admin/audit/logs');
        }
    }
    
    /**
     * Handle errors with consistent error reporting
     * @param \Exception $e
     * @ param string $message
     * @param string $redirectUrl
     */
    private function handleError(\Exception $e, string $message, string $redirectUrl) {
        error_log($e->getMessage());
        $_SESSION['flash_error'] = $message;
        header('Location: ' . $redirectUrl);
        exit;
    }
}