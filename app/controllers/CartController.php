<?php
// controllers/CartController.php
namespace Controllers;

use Core\Request;
use Auth\AuthManager;
use Models\Cart;
use Models\Product;
use Traits\RenderTrait;

class CartController {
    use RenderTrait;
    
    private $cartModel;
    private $auth;
    private $productModel;
    
    public function __construct() {
        $this->cartModel = new Cart();
        $this->productModel = new Product();
        $this->auth = AuthManager::getInstance();
    }
    
    public function show(Request $request) {
        $vendor = $request->getParam('vendor');
        
        if ($vendor) {
            $cart = $this->cartModel->getVendorCart($vendor['id']);
            
            // Render vendor cart view
            $this->render('vendor/cart', [
                'cart' => $cart,
                'vendor' => $vendor,
                'total' => $this->cartModel->getTotal($vendor['id']),
                'page_title' => $vendor['business_name'] . ' - Cart'
            ]);
        } else {
            $allCarts = $this->cartModel->getAllCarts();
            
            // Render marketplace cart view
            $this->render('marketplace/cart', [
                'carts' => $allCarts,
                'total' => $this->cartModel->getTotal(),
                'page_title' => 'Shopping Cart'
            ]);
        }
    }
    
    public function checkout(Request $request) {
        $vendor = $request->getParam('vendor');
        
        if ($vendor) {
            $cart = $this->cartModel->getVendorCart($vendor['id']);
            if (empty($cart)) {
                $_SESSION['flash_error'] = 'Your cart is empty';
                header('Location: /' . $vendor['slug'] . '/cart');
                exit;
            }
            
            // Render vendor checkout view
            $this->render('vendor/checkout', [
                'cart' => $cart,
                'vendor' => $vendor,
                'total' => $this->cartModel->getTotal($vendor['id']),
                'page_title' => $vendor['business_name'] . ' - Checkout'
            ]);
        } else {
            $allCarts = $this->cartModel->getAllCarts();
            if (empty($allCarts)) {
                $_SESSION['flash_error'] = 'Your cart is empty';
                header('Location: /cart');
                exit;
            }
            
            // Render marketplace checkout view
            $this->render('marketplace/checkout', [
                'carts' => $allCarts,
                'total' => $this->cartModel->getTotal(),
                'page_title' => 'Marketplace Checkout'
            ]);
        }
    }
    
    public function addItem(Request $request) {
        // Validate request
        if (!$request->isPost()) {
            return $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }
        
        $productId = $request->getBody('product_id');
        $quantity = $request->getBody('quantity', 1);
        $vendorId = $request->getBody('vendor_id');
        
        if (!$productId || !$vendorId) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $success = $this->cartModel->addItem($vendorId, $productId, $quantity);
            
            return $this->jsonResponse([
                'success' => $success,
                'cart' => $this->cartModel->getVendorCart($vendorId),
                'total' => $this->cartModel->getTotal($vendorId)
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function removeItem(Request $request) {
        // Validate request
        if (!$request->isPost()) {
            return $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }
        
        $productId = $request->getBody('product_id');
        $vendorId = $request->getBody('vendor_id');
        
        if (!$productId || !$vendorId) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $success = $this->cartModel->removeItem($vendorId, $productId);
            
            return $this->jsonResponse([
                'success' => $success,
                'cart' => $this->cartModel->getVendorCart($vendorId),
                'total' => $this->cartModel->getTotal($vendorId)
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function updateItemQuantity(Request $request) {
        // Validate request
        if (!$request->isPost()) {
            return $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }
        
        $productId = $request->getBody('product_id');
        $quantity = $request->getBody('quantity');
        $vendorId = $request->getBody('vendor_id');
        
        if (!$productId || !$vendorId || $quantity === null) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        try {
            $success = $this->cartModel->updateQuantity($vendorId, $productId, $quantity);
            
            return $this->jsonResponse([
                'success' => $success,
                'cart' => $this->cartModel->getVendorCart($vendorId),
                'total' => $this->cartModel->getTotal($vendorId)
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    public function clearCart(Request $request) {
        // Validate request
        if (!$request->isPost()) {
            return $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }
        
        $vendorId = $request->getBody('vendor_id');
        
        if (!$vendorId) {
            return $this->jsonResponse(['error' => 'Missing vendor ID'], 400);
        }
        
        try {
            $success = $this->cartModel->clear($vendorId);
            
            return $this->jsonResponse([
                'success' => $success,
                'cart' => []
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Helper method to send JSON responses
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}