<?php

// models/Settings.php
namespace Models;

use Core\Database;

class Settings {

    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM settings");
        $settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $formatted = [];
        foreach ($settings as $setting) {
            $formatted[$setting['key']] = $setting['value'];
        }
        
        return $formatted;
    }
    
    public function updateAll($settings) {
        $this->db->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare(
                    "INSERT INTO settings (key, value) 
                     VALUES (:key, :value) 
                     ON DUPLICATE KEY UPDATE value = :value"
                );
                
                $stmt->execute([
                    'key' => $key,
                    'value' => $value
                ]);
            }
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}