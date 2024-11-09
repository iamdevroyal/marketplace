<?php
// controllers/MarketplaceController.php
namespace Controllers;

use Core\Request;
use Models\Vendor;
use Models\Product;
use Traits\RenderTrait;

class MarketplaceController {
    use RenderTrait;
    
    private $vendorModel;
    private $productModel;
    
    public function __construct() {
        $this->vendorModel = new Vendor();
        $this->productModel = new Product();
    }
    
    public function home(Request $request) {
        $featuredVendors = $this->vendorModel->getFeatured();
        
        // Render home view
        $this->render('marketplace/home', [
            'featured_vendors' => $featuredVendors,
            'page_title' => 'Marketplace Home'
        ]);
    }
    
    public function vendors(Request $request) {
        $page = $request->getQuery('page', 1);
        $search = $request->getQuery('search', '');
        $category = $request->getQuery('category', '');
        
        $vendors = $this->vendorModel->paginate(20, $page, [
            'search' => $search,
            'category' => $category
        ]);
        
        $categories = $this->vendorModel->getAllCategories();
        
        // Render vendors list view
        $this->render('marketplace/vendors', [
            'vendors' => $vendors,
            'categories' => $categories,
            'page_title' => 'Marketplace Vendors',
            'current_page' => $page,
            'search' => $search,
            'selected_category' => $category
        ]);
    }
    
    public function vendorProfile(Request $request) {
        $slug = $request->getParam('slug');
        $vendor = $this->vendorModel->findBySlug($slug);
        
        if (!$vendor) {
            $_SESSION['flash_error'] = 'Vendor not found';
            header('Location: /vendors');
            exit;
        }
        
        $products = $this->productModel->findByVendorId($vendor['id']);
        
        // Render vendor profile view
        $this->render('marketplace/vendor-profile', [
            'vendor' => $vendor,
            'products' => $products,
            'page_title' => $vendor['business_name'] . ' - Vendor Profile'
        ]);
    }
    
    public function search(Request $request) {
        $query = $request->getQuery('q', '');
        $page = $request->getQuery('page', 1);
        $type = $request->getQuery('type', 'all');
        
        $results = [];
        
        switch ($type) {
            case 'vendors':
                $results = $this->vendorModel->search($query, $page);
                break;
            case 'products':
                $results = $this->productModel->search($query, $page);
                break;
            default:
                $results = [
                    'vendors' => $this->vendorModel->search($query, $page),
                    'products' => $this->productModel->search($query, $page)
                ];
        }
        
        // Render search results view
        $this->render('marketplace/search-results', [
            'results' => $results,
            'query' => $query,
            'type' => $type,
            'page_title' => 'Search Results',
            'current_page' => $page
        ]);
    }
    
    public function categories(Request $request) {
        $vendorCategories = $this->vendorModel->getAllCategories();
        $productCategories = $this->productModel->getAllCategories();
        
        // Render categories view
        $this->render('marketplace/categories', [
            'vendor_categories' => $vendorCategories,
            'product_categories' => $productCategories,
            'page_title' => 'Marketplace Categories'
        ]);
    }
    
    public function productDetails(Request $request) {
        $slug = $request->getParam('slug');
        $product = $this->productModel->findBySlug($slug);
        
        if (!$product) {
            // Use render method for 404 page
            $this->render('errors/404', [
                'message' => 'Product not found',
                'page_title' => '404 Not Found'
            ], 404);
            exit;
        }
        
        $relatedProducts = $this->productModel->getRelatedProducts($product['id'], $product['category_id']);
        $vendor = $this->vendorModel->findById($product['vendor_id']);
        
        // Render product details view
        $this->render('marketplace/product-details', [
            'product' => $product,
            'vendor' => $vendor,
            'related_products' => $relatedProducts,
            'page_title' => $product['name']
        ]);
    }
    
    public function compareProducts(Request $request) {
        $productIds = $request->getQuery('products', []);
        
        if (empty($productIds)) {
            $_SESSION['flash_error'] = 'Please select products to compare';
            header('Location: /products');
            exit;
        }
        
        $products = $this->productModel->getProductsByIds($productIds);
        
        // Render product comparison view
        $this->render('marketplace/product-compare', [
            'products' => $products,
            'page_title' => 'Compare Products'
        ]);
    }
}