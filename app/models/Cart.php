<?php
// models/Cart.php
namespace Models;

use Core\Database;

class Cart {
    private $db;
    private $discountModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->discountModel = new Discount();
        
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getVendorCart($vendorId) {
        return $_SESSION['carts'][$vendorId] ?? [];
    }
    
    public function getAllCarts() {
        return $_SESSION['carts'] ?? [];
    }
    
    public function addItem($vendorId, $productId, $quantity = 1) {
        // Validate product exists and is in stock
        $product = $this->getProduct($productId);
        if (!$product || $product['stock'] < $quantity) {
            return false;
        }
        
        if (!isset($_SESSION['carts'][$vendorId])) {
            $_SESSION['carts'][$vendorId] = [];
        }
        
        if (isset($_SESSION['carts'][$vendorId][$productId])) {
            $_SESSION['carts'][$vendorId][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['carts'][$vendorId][$productId] = [
                'product' => $product,
                'quantity' => $quantity
            ];
        }
        
        return true;
    }
    
    public function updateQuantity($vendorId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($vendorId, $productId);
        }
        
        if (isset($_SESSION['carts'][$vendorId][$productId])) {
            $_SESSION['carts'][$vendorId][$productId]['quantity'] = $quantity;
            return true;
        }
        
        return false;
    }
    
    public function removeItem($vendorId, $productId) {
        if (isset($_SESSION['carts'][$vendorId][$productId])) {
            unset($_SESSION['carts'][$vendorId][$productId]);
            
            if (empty($_SESSION['carts'][$vendorId])) {
                unset($_SESSION['carts'][$vendorId]);
            }
            
            return true;
        }
        
        return false;
    }
    
    public function getTotal($vendorId = null) {
        if ($vendorId) {
            return $this->calculateTotal($_SESSION['carts'][$vendorId] ?? []);
        }
        
        $total = 0;
        foreach ($_SESSION['carts'] ?? [] as $vendorCart) {
            $total += $this->calculateTotal($vendorCart);
        }
        
        return $total;
    }
    
    private function calculateTotal($cart) {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['product']['price'] * $item['quantity'];
        }
        return $total;
    }
    
    private function getProduct($productId) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function clear($vendorId = null) {
        if ($vendorId) {
            unset($_SESSION['carts'][$vendorId]);
        } else {
            unset($_SESSION['carts']);
        }
    }
    
    public function applyDiscount($code, $vendorId = null) {
        // Find the discount
        $discount = $this->discountModel->findByCode($code);
        
        // Validate discount
        if (!$this->discountModel->isValid($discount)) {
            return false;
        }
        
        // Apply to specific vendor or global cart
        if ($vendorId) {
            if (isset($_SESSION['carts'][$vendorId])) {
                // Apply discount to vendor-specific cart
                $discountResult = $this->discountModel->applyToCart(
                    $_SESSION['carts'][$vendorId], 
                    $discount
                );
                
                $_SESSION['discounts'][$vendorId] = $discount;
                $_SESSION['discounted_carts'][$vendorId] = $discountResult['cart'];
                $_SESSION['total_discounts'][$vendorId] = $discountResult['total_discount'];
            }
        } else {
            // Apply global discount
            $globalDiscountResults = [];
            foreach ($_SESSION['carts'] as $vendorId => $cart) {
                $discountResult = $this->discountModel->applyToCart($cart, $discount);
                $globalDiscountResults[$vendorId] = $discountResult;
            }
            
            $_SESSION['global_discount'] = $discount;
            $_SESSION['global_discounted_carts'] = $globalDiscountResults;
        }
        
        // Increment discount usage
        $this->discountModel->incrementUsage($discount['id']);
        
        return true;
    }
    
    /**
     * Get total after applying discounts
     */
    public function getTotalAfterDiscount($vendorId = null) {
        $total = $this->getTotal($vendorId);
        
        if ($vendorId && isset($_SESSION['discounts'][$vendorId])) {
            $discount = $_SESSION['discounts'][$vendorId];
            return $total - ($_SESSION['total_discounts'][$vendorId] ?? 0);
        } elseif (!$vendorId && isset($_SESSION['global_discount'])) {
            $totalDiscount = 0;
            foreach ($_SESSION['global_discounted_carts'] as $vendorCart) {
                $totalDiscount += $vendorCart['total_discount'];
            }
            return $total - $totalDiscount;
        }
        
        return $total;
    }
}