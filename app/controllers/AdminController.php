<?php
// controllers/AdminController.php
namespace Controllers;

use Core\Request;
use Auth\AuthManager;
use Models\User;
use Models\Order;
use Models\Product;
use Models\Settings;
use Traits\RenderTrait;

class AdminController {
    use RenderTrait;
    
    private $auth;
    private $userModel;
    private $orderModel;
    private $productModel;
    private $settingsModel;
    
    public function __construct() {
        $this->auth = AuthManager::getInstance();
        $this->userModel = new User();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->settingsModel = new Settings();
        
        // Middleware-like authentication check
        if (!$this->isAdmin()) {
            $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Check if current user is an admin
     * @return bool
     */
    private function isAdmin(): bool {
        if (!$this->auth->check()) return false;
        $user = $this->auth->user();
        return $user['is_admin'] ?? false;
    }
    
    /**
     * Admin Dashboard
     * @param Request $request
     */
    public function dashboard(Request $request) {
        $stats = [
            'total_users' => $this->userModel->count(),
            'total_orders' => $this->orderModel->count(),
            'total_revenue' => $this->orderModel->totalRevenue(),
            'total_products' => $this->productModel->count(),
            'recent_orders' => $this->orderModel->getRecent(5),
            'recent_users' => $this->userModel->getRecent(5),
            'popular_products' => $this->productModel->getPopular(5)
        ];
        
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'page_title' => 'Admin Dashboard'
        ]);
    }
    
    /**
     * User Management
     * @param Request $request
     */
    public function users(Request $request) {
        $page = $request->getQuery('page', 1);
        $search = $request->getQuery('search', '');
        $sort = $request->getQuery('sort', 'created_at');
        $order = $request->getQuery('order', 'desc');
        
        $users = $this->userModel->paginate(20, $page, [
            'search' => $search,
            'sort' => $sort,
            'order' => $order
        ]);
        
        $this->render('admin/users/index', [
            'users' => $users,
            'page_title' => 'Manage Users',
            'search' => $search,
            'sort' => $sort,
            'order' => $order
        ]);
    }
    
    /**
     * Edit User
     * @param Request $request
     */
    public function editUser(Request $request) {
        $id = $request->getParam('id');
        $user = $this->userModel->find($id);
        
        if (!$user) {
            $_SESSION['flash_error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }
        
        if ($request->isPost()) {
            try {
                $data = $this->validateUserData($request);
                $this->userModel->update($id, $data);
                
                $_SESSION['flash_success'] = 'User updated successfully';
                header('Location: /admin/users');
                exit;
                
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        
        $this->render('admin/users/edit', [
            'user' => $user,
            'page_title' => 'Edit User'
        ]);
    }
    
    /**
     * Validate user data for update
     * @param Request $request
     * @return array
     */
    private function validateUserData(Request $request): array {
        $data = [
            'name' => $request->getBody('name'),
            'email' => $request->getBody('email'),
            'is_admin' => $request->getBody('is_admin') ? true : false,
            'status' => $request->getBody('status')
        ];
        
        // Optional password update
        if (!empty($request->getBody('password'))) {
            $data['password'] = password_hash($request->getBody('password'), PASSWORD_DEFAULT);
        }
        
        return $data;
    }
    
    /**
     * Product Management
     * @param Request $request
     */
    public function products(Request $request) {
        $page = $request->getQuery('page', 1);
        $search = $request->getQuery('search', '');
        $category = $request->getQuery('category', '');
        
        $products = $this->productModel->paginate(20, $page, [
            'search' => $search,
            'category' => $category
        ]);
        
        $categories = $this->productModel->getAllCategories();
        
        $this->render('admin/products/index', [
            'products' => $products,
            'categories' => $categories,
            'page_title' => 'Manage Products',
            'search' => $search,
            'category' => $category
        ]);
    }
    
    /**
     * Create Product
     * @param Request $request
     */
    public function createProduct(Request $request) {
        if ($request->isPost()) {
            try {
                $data = $this->validateProductData($request->getBody());
                $productId = $this->productModel->create($data);
                
                $this->handleProductImage($productId, $request);
                
                $_SESSION['flash_success'] = 'Product created successfully';
                header('Location: /admin/products');
                exit;
                
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        
        $categories = $this->productModel->getAllCategories();
        
        $this->render('admin/products/create', [
            'categories' => $categories,
            'page_title' => 'Create Product'
        ]);
    }
    
    /**
     * Edit Product
     * @param Request $request
     */
    public function editProduct(Request $request) {
        $id = $request->getParam('id');
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['flash_error'] = 'Product not found';
            header('Location: /admin/products');
            exit;
        }
        
        if ($request->isPost()) {
            try {
                $data = $this->validateProductData($request->getBody());
                $this->productModel->update($id, $data);
                
                $this->handleProductImage($id, $request);
                
                $_SESSION['flash_success'] = 'Product updated successfully';
                header('Location: /admin/products');
                exit;
                
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        
        $categories = $this->productModel->getAllCategories();
        
        $this->render('admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'page_title' => 'Edit Product'
        ]);
    }
    
    /**
     * Validate Product Data
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function validateProductData(array $data): array {
        $validated = [];
        $required = ['name', 'price', 'category_id', 'description'];
        
        foreach ($required as $field) {
            if (empty($data [$field])) {
                throw new \Exception("The {$field} field is required");
            }
            $validated[$field] = trim($data[$field]);
        }
        
        if (!is_numeric($validated['price']) || $validated['price'] < 0) {
            throw new \Exception('Price must be a valid number');
        }
        
        $validated['is_active'] = !empty($data['is_active']);
        $validated['stock'] = (int)($data['stock'] ?? 0);
        
        return $validated;
    }
    
    /**
     * Handle Product Image Upload
     * @param int $productId
     * @param Request $request
     * @throws \Exception
     */
    private function handleProductImage(int $productId, Request $request) {
        $file = $request->getUploadedFile('image');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Invalid image type. Allowed types: JPG, PNG, WEBP');
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new \Exception('Image size must be less than 5MB');
        }
        
        $uploadDir = BASE_PATH . '/public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $productId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $destination = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Failed to upload image');
        }
        
        $this->productModel->update($productId, ['image' => $filename]);
    }
    
    /**
     * Order Management
     * @param Request $request
     */
    public function orders(Request $request) {
        $page = $request->getQuery('page', 1);
        $status = $request->getQuery('status', '');
        $search = $request->getQuery('search', '');
        
        $orders = $this->orderModel->paginate(20, $page, [
            'status' => $status,
            'search' => $search
        ]);
        
        $this->render('admin/orders/index', [
            'orders' => $orders,
            'page_title' => 'Manage Orders',
            'status' => $status,
            'search' => $search
        ]);
    }
    
    /**
     * View Order Details
     * @param Request $request
     */
    public function viewOrder(Request $request) {
        $id = $request->getParam('id');
        $order = $this->orderModel->findWithDetails($id);
        
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found';
            header('Location: /admin/orders');
            exit;
        }
        
        $this->render('admin/orders/view', [
            'order' => $order,
            'page_title' => 'View Order'
        ]);
    }
    
    /**
     * Update Order Status
     * @param Request $request
     */
    public function updateOrderStatus(Request $request) {
        $id = $request->getParam('id');
        
        try {
            $status = $request->getBody('status');
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($status, $validStatuses)) {
                throw new \Exception('Invalid status');
            }
            
            $this->orderModel->updateStatus($id, $status);
            $_SESSION['flash_success'] = 'Order status updated successfully';
            
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        
        header('Location: /admin/orders/view/' . $id);
        exit;
    }
    
    /**
     * Settings Management
     * @param Request $request
     */
    public function settings(Request $request) {
        if ($request->isPost()) {
            try {
                $settings = [
                    'site_name' => $request->getBody('site_name'),
                    'contact_email' => $request->getBody('contact_email'),
                    'support_phone' => $request->getBody('support_phone'),
                    'address' => $request ->getBody('address'),
                    'currency' => $request->getBody('currency', 'USD'),
                    'tax_rate' => floatval($request->getBody('tax_rate', 0)),
                    'shipping_fee' => floatval($request->getBody('shipping_fee', 0)),
                    'min_order_amount' => floatval($request->getBody('min_order_amount', 0)),
                    'maintenance_mode' => $request->getBody('maintenance_mode') ? true : false,
                    'allow_registrations' => $request->getBody('allow_registrations') ? true : false
                ];
                
                $this->settingsModel->updateAll($settings);
                $_SESSION['flash_success'] = 'Settings updated successfully';
                
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        
        $settings = $this->settingsModel->getAll();
        $this->render('admin/settings', [
            'settings' => $settings,
            'page_title' => 'Site Settings'
        ]);
    }

    /**
     * Export Data
     * @param Request $request
     */
    public function exportData(Request $request) {
        $format = $request->getQuery('format', 'csv');
        $type = $request->getQuery('type', 'orders');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        switch ($type) {
            case 'orders':
                fputcsv($output, ['Order ID', 'Customer', 'Total', 'Status', 'Date']);
                $orders = $this->orderModel->getAll();
                foreach ($orders as $order) {
                    fputcsv($output, [
                        $order['id'],
                        $order['customer_name'],
                        $order['total'],
                        $order['status'],
                        $order['created_at']
                    ]);
                }
                break;
                
            case 'products':
                fputcsv($output, ['ID', 'Name', 'Category', 'Price', 'Stock', 'Status']);
                $products = $this->productModel->getAll();
                foreach ($products as $product) {
                    fputcsv($output, [
                        $product['id'],
                        $product['name'],
                        $product['category_name'],
                        $product['price'],
                        $product['stock'],
                        $product['is_active'] ? 'Active' : 'Inactive'
                    ]);
                }
                break;
        }
        
        fclose($output);
        exit;
    }
}