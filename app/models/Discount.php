<?php
// models/Discount.php
namespace Models;

use Core\Database;
use PDO;

class Discount {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find discount by code
     */
    public function findByCode($code) {
        $stmt = $this->db->prepare("
            SELECT * FROM discounts 
            WHERE code = ? 
            AND active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate discounted price
     */
    public function calculateDiscount($originalPrice, $discount) {
        switch ($discount['type']) {
            case 'percentage':
                return $originalPrice * (1 - ($discount['value'] / 100));
            case 'fixed':
                return max(0, $originalPrice - $discount['value']);
            default:
                return $originalPrice;
        }
    }
    
    /**
     * Check if discount is valid
     */
    public function isValid($discount) {
        if (!$discount) {
            return false;
        }
        
        // Check if discount is active
        if (!$discount['active']) {
            return false;
        }
        
        // Check expiration
        if ($discount['expires_at'] && strtotime($discount['expires_at']) < time()) {
            return false;
        }
        
        // Check usage limit
        if ($discount['usage_limit'] !== null && $discount['usage_count'] >= $discount['usage_limit']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Apply discount to cart
     */
    public function applyToCart($cart, $discount) {
        $totalDiscount = 0;
        $discountedCart = $cart;
        
        foreach ($discountedCart as &$item) {
            $originalPrice = $item['product']['price'];
            $item['original_price'] = $originalPrice;
            $item['discounted_price'] = $this->calculateDiscount($originalPrice, $discount);
            $item['discount_amount'] = $originalPrice - $item['discounted_price'];
            
            $totalDiscount += $item['discount_amount'] * $item['quantity'];
        }
        
        return [
            'cart' => $discountedCart,
            'total_discount' => $totalDiscount
        ];
    }
    
    /**
     * Increment usage count for a discount
     */
    public function incrementUsage($discountId) {
        $stmt = $this->db->prepare("
            UPDATE discounts 
            SET usage_count = usage_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$discountId]);
    }
}