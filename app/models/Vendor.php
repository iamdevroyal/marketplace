<?php
// models/Vendor.php
namespace Models;

use Core\Database;
use PDO;
use Exception;

class Vendor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find vendor by slug
     */
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM vendors WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find vendor by user ID
     */
    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM vendors WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get featured vendors
     */
    public function getFeatured() {
        $stmt = $this->db->query("SELECT * FROM vendors WHERE is_featured = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update vendor profile
     */
    public function update($vendorId, $data) {
        $fields = [];
        $values = [];
        
        // Dynamically build update query
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $vendorId; // Add vendorId for WHERE clause
        
        $sql = "UPDATE vendors SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute($values);
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to update vendor profile: " . $e->getMessage());
        }
    }
    
    /**
     * Update vendor profile image
     */
    public function updateProfileImage($vendorId, $filename) {
        $stmt = $this->db->prepare("UPDATE vendors SET profile_image = ? WHERE id = ?");
        
        try {
            $stmt->execute([$filename, $vendorId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to update profile image: " . $e->getMessage());
        }
    }
    
    /**
     * Get total sales for a vendor
     */
    public function getTotalSales($vendorId) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(order_items.quantity * order_items.price), 0) as total_sales
            FROM order_items
            JOIN products ON order_items.product_id = products.id
            WHERE products.vendor_id = ?
        ");
        $stmt->execute([$vendorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_sales'] ?? 0;
    }
    
    /**
     * Get recent orders for a vendor
     */
    public function getRecentOrders($vendorId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.vendor_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$vendorId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new vendor profile
     */
    public function create($data) {
        $requiredFields = ['user_id', 'business_name', 'slug', 'description', 'email'];
        
        // Validate required fields
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("The {$field} field is required");
            }
        }
        
        // Prepare SQL statement
        $sql = "INSERT INTO vendors (
            user_id, business_name, slug, description, email, 
            phone, address, website, profile_image
        ) VALUES (
            :user_id, :business_name, :slug, :description, :email, 
            :phone, :address, :website, :profile_image
        )";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':business_name' => $data['business_name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? '',
                ':address' => $data['address'] ?? '',
                ':website' => $data['website'] ?? '',
                ':profile_image' => $data['profile_image'] ?? null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Failed to create vendor profile: " . $e->getMessage());
        }
    }
    
    /**
     * Check if a vendor slug already exists
     */
    public function slugExists($slug) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM vendors WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetchColumn() > 0;
    }

/**
 * Search vendors with pagination
 */
public function search($query, $page = 1, $limit = 20) {
    $offset = ($page - 1) * $limit;
    
    $stmt = $this->db->prepare("
        SELECT * FROM vendors 
        WHERE business_name LIKE :query 
        OR description LIKE :query 
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all vendor categories
 */
public function getAllCategories() {
    $stmt = $this->db->query("
        SELECT DISTINCT category 
        FROM vendors 
        WHERE category IS NOT NULL 
        ORDER BY category
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Paginate vendors with optional filtering
 */
public function paginate($limit, $page, $filters = []) {
    $offset = ($page - 1) * $limit;
    $query = "SELECT * FROM vendors WHERE 1=1";
    
    // Apply search filter
    if (!empty($filters['search'])) {
        $query .= " AND (business_name LIKE :search OR description LIKE :search)";
    }
    
    // Apply category filter
    if (!empty($filters['category'])) {
        $query .= " AND category = :category";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
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
}