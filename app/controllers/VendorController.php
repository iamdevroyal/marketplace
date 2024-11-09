<?php
// controllers/VendorController.php
namespace Controllers;

use Core\Request;
use Models\Product;
use Models\Vendor;
use Auth\AuthManager;
use Traits\RenderTrait;
use Exception;

class VendorController {
    use RenderTrait;
    
    private $productModel;
    private $vendorModel;
    private $auth;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->vendorModel = new Vendor();
        $this->auth = AuthManager::getInstance();
    }
    
    public function home(Request $request) {
        $vendorSlug = $request->getParam('slug');
        
        // Find vendor by slug
        $vendor = $this->vendorModel->findBySlug($vendorSlug);
        
        if (!$vendor) {
            // Use render method for 404 page
            $this->render('errors/404', [
                'message' => 'Vendor not found',
                'page_title' => '404 Not Found'
            ], 404);
            exit;
        }
        
        // Fetch products with pagination and filtering
        $page = $request->getQuery('page', 1);
        $category = $request->getQuery('category', '');
        $sortBy = $request->getQuery('sort', 'created_at');
        $order = $request->getQuery('order', 'desc');
        
        $products = $this->productModel->paginate(20, $page, [
            'vendor_id' => $vendor['id'],
            'category' => $category,
            'sort' => $sortBy,
            'order' => $order
        ]);
        
        $categories = $this->productModel->getVendorCategories($vendor['id']);
        
        // Render vendor store view
        $this->render('vendor/store', [
            'vendor' => $vendor,
            'products' => $products,
            'categories' => $categories,
            'page_title' => $vendor['business_name'] . ' Store',
            'current_page' => $page,
            'selected_category' => $category,
            'sort_by' => $sortBy,
            'order' => $order
        ]);
    }
    
    public function products(Request $request) {
        $vendorSlug = $request->getParam('slug');
        
        // Find vendor by slug
        $vendor = $this->vendorModel->findBySlug($vendorSlug);
        
        if (!$vendor) {
            // Use render method for 404 page
            $this->render('errors/404', [
                'message' => 'Vendor not found',
                'page_title' => '404 Not Found'
            ], 404);
            exit;
        }
        
        // Check if current user is the vendor
        $currentUser = $this->auth->user();
        $isOwner = $currentUser && $vendor['user_id'] == $currentUser['id'];
        
        if (!$isOwner) {
            $_SESSION['flash_error'] = 'Unauthorized access';
            header('Location: /vendors/' . $vendorSlug);
            exit;
        }
        
        // Fetch products with pagination and filtering
        $page = $request->getQuery('page', 1);
        $category = $request->getQuery('category', '');
        $status = $request->getQuery('status', '');
        $sortBy = $request->getQuery('sort', 'created_at');
        $order = $request->getQuery('order', 'desc');
        
        $products = $this->productModel->paginate(20, $page, [
            'vendor_id' => $vendor['id'],
            'category' => $category,
            'status' => $status,
            'sort' => $sortBy,
            'order' => $order
        ]);
        
        $categories = $this->productModel->getVendorCategories($vendor['id']);
        
        // Render vendor products management view
        $this->render('vendor/products', [
            'vendor' => $vendor,
            'products' => $products,
            'categories' => $categories,
            'page_title' => 'Manage Products',
            'current_page' => $page,
            'selected_category' => $category,
            'selected_status' => $status,
            'sort_by' => $sortBy,
            'order' => $order
        ]);
    }
    
    public function dashboard(Request $request) {
        // Find vendor for current user
        $currentUser = $this->auth->user();
        $vendor = $this->vendorModel->findByUserId($currentUser['id']);
        
        if (!$vendor) {
            $_SESSION['flash_error'] = 'Vendor profile not found';
            header('Location: /vendor/register');
            exit;
        }
        
        // Fetch dashboard statistics
        $stats = [
            'total_products' => $this->productModel->countByVendor($vendor['id']),
            'total_sales' => $this->vendorModel->getTotalSales($vendor['id']),
            'recent_orders' => $this->vendorModel->getRecentOrders($vendor['id'], 5),
            'top_products' => $this->productModel->getTopSellingProducts($vendor['id'], 5)
        ];
        
        // Render vendor dashboard view
        $this->render('vendor/dashboard', [
            'vendor' => $vendor,
            'stats' => $stats,
            'page_title' => 'Vendor Dashboard'
        ]);
    }
    
    public function editProfile(Request $request) {
        // Find vendor for current user
        $currentUser = $this->auth->user();
        $vendor = $this->vendorModel->findByUserId($currentUser['id']);
        
        if (!$vendor) {
            $_SESSION['flash_error'] = 'Vendor profile not found';
            header('Location: /vendor/register');
            exit;
        }
        
        if ($request->isPost()) {
            try {
                $data = $this->validateVendorData($request->getBody());
                
                // Handle profile image upload
                $this->handleProfileImage($vendor['id'], $request);
                
                $this->vendorModel->update($vendor['id'], $data);
                
                $_SESSION['flash_success'] = 'Profile updated successfully';
                header('Location: /vendor/dashboard');
                exit;
                
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        
        // Render vendor profile edit view
        $this->render('vendor/edit-profile', [
            'vendor' => $vendor,
            'page_title' => 'Edit Vendor Profile',
            'old_input' => $_SESSION['old_input'] ?? $vendor
        ]);
        
        // Clear old input
        unset($_SESSION['old_input']);
    }
    
    private function validateVendorData($data) {
        $validated = [];
        $required = ['business_name', 'description', 'email'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("The {$field} field is required");
            }
            $validated[$field] = trim($data[$field]);
        }
        
        // Validate email
        if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Optional fields
        $validated['phone'] = $data['phone'] ?? '';
        $validated['address'] = $data['address'] ?? '';
        $validated['website'] = $data['website'] ?? '';
        
        return $validated;
    }
    
    private function handleProfileImage($vendorId, Request $request) {
        $file = $request->getUploadedFile('profile_image');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }
        
        $allowedTypes = [' image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid image type. Allowed types: JPG, PNG, WEBP');
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new Exception('Image size must be less than 5MB');
        }
        
        $uploadDir = BASE_PATH . '/public/uploads/vendors/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $vendorId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
        
        // Save the filename in the database
        $this->vendorModel->updateProfileImage($vendorId, $filename);
    }
}