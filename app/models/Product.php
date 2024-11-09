
<?php
// models/Product.php
namespace Models;

use Core\Database;
use PDO;
use Exception;

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        $stmt = $ this->db->prepare("
            SELECT p.*, v.name as vendor_name, v.slug as vendor_slug 
            FROM products p 
            JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByVendorId($vendorId) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE vendor_id = ?");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT p.*, v.name as vendor_name, v.slug as vendor_slug 
            FROM products p 
            JOIN vendors v ON p.vendor_id = v.id 
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function paginate($limit, $page, $filters = []) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT p.*, v.name as vendor_name, v.slug as vendor_slug 
                  FROM products p 
                  JOIN vendors v ON p.vendor_id = v.id 
                  WHERE 1=1";
        
        if (!empty($filters['search'])) {
            $query .= " AND p.name LIKE :search";
        }
        
        if (!empty($filters['category'])) {
            $query .= " AND p.category_id = :category";
        }
        
        $query .= " ORDER BY p.{$filters['sort']} {$filters['order']} LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        
        if (!empty($filters['search'])) {
            $stmt->bindValue(':search', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['category'])) {
            $stmt->bindValue(':category', $filters['category']);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO products (name, description, price, category_id, vendor_id, stock, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['vendor_id'],
            $data['stock'],
            $data['is_active']
        ]);
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock = ?, is_active = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['stock'],
            $data['is_active'],
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    public function updateImage($productId, $filename) {
        $stmt = $this->db->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmt->execute([$filename, $productId]);
    }
    
    public function getRelatedProducts($productId, $categoryId) {
        $stmt = $this->db->prepare("
            SELECT * FROM products 
            WHERE category_id = ? AND id != ? 
            LIMIT 4
        ");
        $stmt->execute([$categoryId, $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getReviews($productId) {
        // Assuming there's a reviews table
        $stmt = $this->db->prepare("
            SELECT * FROM reviews 
            WHERE product_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllCategories() {
        // Assuming there's a categories table
        $stmt = $this->db->query("SELECT * FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find product by slug
     */
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, v.slug as vendor_slug 
            FROM products p 
            JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.slug = ?
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Search products with pagination
     */
    public function search($query, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, v.slug as vendor_slug 
            FROM products p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.name LIKE :query 
            OR p.description LIKE :query 
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products by multiple IDs
     */
    public function getProductsByIds($productIds) {
        // Ensure $productIds is an array and contains only numeric values
        $productIds = array_map('intval', (array)$productIds);
        
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, v.slug as vendor_slug 
            FROM products p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.id IN ($placeholders)
        ");
        
        $stmt->execute($productIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get vendor's products
     */
    public function getVendorProducts($vendorId) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, v.slug as vendor_slug 
            FROM products p
            JOIN vendors v ON p.vendor_id = v.id
            WHERE p.vendor_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}