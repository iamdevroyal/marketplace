<?php
// controllers/ProductController.php
namespace Controllers;

use Core\Request;
use Models\Product;
use Models\Vendor;
use Auth\AuthManager;
use Traits\RenderTrait;
use Exception;

class ProductController {
    use RenderTrait;
    
    private $productModel;
    private $vendorModel;
    private $auth;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->vendorModel = new Vendor();
        $this->auth = AuthManager::getInstance();
    }
    
    public function index(Request $request) {
        // Pagination and filtering
        $page = $request->getQuery('page', 1);
        $search = $request->getQuery('search', '');
        $category = $request->getQuery('category', '');
        $sortBy = $request->getQuery('sort', 'created_at');
        $order = $request->getQuery('order', 'desc');
        
        $products = $this->productModel->paginate(20, $page, [
            'search' => $search,
            'category' => $category,
            'sort' => $sortBy,
            'order' => $order
        ]);
        
        $categories = $this->productModel->getAllCategories();
        
        // Render products view
        $this->render('marketplace/products', [
            'products' => $products,
            'categories' => $categories,
            'page_title' => 'Marketplace',
            'current_page' => $page,
            'search' => $search,
            'selected_category' => $category,
            'sort_by' => $sortBy,
            'order' => $order
        ]);
    }
    
    public function show(Request $request) {
        $productId = $request->getParam('id');
        $vendorSlug = $request->getParam('vendor');
        
        // Find product
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            // Use render method for 404 page
            $this->render('errors/404', [
                'message' => 'Product not found',
                'page_title' => '404 Not Found'
            ], 404);
            exit;
        }
        
        // If vendor slug is provided, validate vendor
        $vendor = null;
        if ($vendorSlug) {
            $vendor = $this->vendorModel->findBySlug($vendorSlug);
            
            if (!$vendor || $product['vendor_id'] != $vendor['id']) {
                $this->render('errors/404', [
                    'message' => 'Product or Vendor not found',
                    'page_title' => '404 Not Found'
                ], 404);
                exit;
            }
        }
        
        // Get related products
        $relatedProducts = $this->productModel->getRelatedProducts(
            $product['id'], 
            $product['category_id']
        );
        
        // Get product reviews
        $reviews = $this->productModel->getReviews($productId);
        
        // Render product details view
        $this->render('product/details', [
            'product' => $product,
            'vendor' => $vendor,
            'related_products' => $relatedProducts,
            'reviews' => $reviews,
            'page_title' => $product['name']
        ]);
    }
    
    public function create(Request $request) {
        // Check if user is authenticated and has vendor permissions
        $user = $this->auth->user();
        $vendor = $this->vendorModel->findByUserId($user['id']);
        
        if (!$vendor) {
            $_SESSION['flash_error'] = 'You must be a vendor to create products';
            header('Location: /vendor/register');
            exit;
        }
        
        if ($request->isPost()) {
            try {
                $data = $this->validateProductData($request->getBody());
                $data['vendor_id'] = $vendor['id'];
                
                $productId = $this->productModel->create($data);
                
                // Handle product image upload
                $this->handleProductImage($productId, $request);
                
                $_SESSION['flash_success'] = 'Product created successfully';
                header('Location: /vendor/products');
                exit;
                
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                $_SESSION['old_input'] = $request->getBody();
            }
        }
        
        $categories = $this->productModel->getAllCategories();
        
        // Render product creation view
        $this->render('product/create', [
            'categories' => $categories,
            'page_title' => 'Create Product',
            'old_input' => $_SESSION['old_input'] ?? []
        ]);
        
        // Clear old input
        unset($_SESSION['old_input']);
    }
    
    public function edit(Request $request) {
        $productId = $request->getParam('id');
        
        // Find product and check ownership
        $product = $this->productModel->findById($productId);
        $user = $this->auth->user();
        $vendor = $this->vendorModel->findByUserId($user['id']);
        
        if (!$product || !$vendor || $product['vendor_id'] != $vendor['id']) {
            $_SESSION['flash_error'] = 'Product not found or unauthorized';
            header('Location: /vendor/products');
            exit;
        }
        
        if ($request->isPost()) {
            try {
                $data = $this->validateProductData($request->getBody());
                
                $this->productModel->update($productId, $data);
                
                // Handle product image upload
                $this->handleProductImage($productId, $request);
                
                $_SESSION['flash_success'] = 'Product updated successfully';
                header('Location: /vendor/products');
                exit;
                
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                $_SESSION['old_input'] = $request->getBody();
            }
        }
        
        $categories = $this->productModel->getAllCategories();
        
        // Render product edit view
        $this->render('product/edit', [
            'product' => $product,
            'categories' => $categories,
            'page_title' => 'Edit Product',
            'old_input' => $_SESSION['old_input'] ?? $product
        ]);
        
        // Clear old input
        unset($_SESSION['old_input']);
    }
    
    public function delete(Request $request) {
        $productId = $request->getParam('id');
        
        // Find product and check ownership
        $product = $this->productModel->findById($productId);
        $user = $this->auth->user();
        $vendor = $this->vendorModel->findByUserId($user['id']);
        
        if (!$product || !$vendor || $product['vendor_id'] != $vendor['id']) {
            $_SESSION['flash_error'] = 'Product not found or unauthorized';
            header('Location: /vendor/products');
            exit;
        }
        
        try {
            $this->productModel->delete($productId);
            
            $_SESSION['flash_success'] = 'Product deleted successfully';
            header('Location: /vendor/products');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /vendor/products');
            exit;
        }
    }
    
    
    private function validateProductData($data) {
        $validated = [];
        $required = ['name', 'description', 'price', 'category_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("The {$field} field is required");
            }
            $validated[$field] = trim($data[$field]);
        }
        
        // Validate price
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            throw new \Exception('Price must be a positive number');
        }
        
        $validated['price'] = floatval($data['price']);
        $validated['stock'] = intval($data['stock'] ?? 0);
        $validated['is_active'] = !empty($data['is_active']);
        
        return $validated;
    }
    
    private function handleProductImage($productId, Request $request) {
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
        
        $filename = $productId . '_' . time() . '.' . pathinfo($file['name'], ['PATH info']['extension']);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
        
        // Save the filename in the database
        $this->productModel->updateImage($productId, $filename);
    }
}