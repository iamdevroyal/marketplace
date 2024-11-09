<?php

// models/AdminBackup.php
namespace Models;

class AdminBackup {
    private $db;
    private $backupPath;
    
    public function __construct(\PDO $db, $backupPath = null) {
        $this->db = $db;
        $this->backupPath = $backupPath ?? BASE_PATH . '/storage/backups';
    }
    
    public function createBackup() {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        $filename = date('Y-m-d_H-i-s') . '_backup.sql';
        $filepath = $this->backupPath . '/' . $filename;
        
        // Get all tables
        $tables = $this->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        
        $output = "-- Database backup created on " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $output .= "-- Table structure for table `$table`\n";
            $createTable = $this->db->query("SHOW CREATE TABLE `$table`")->fetch();
            $output .= $createTable[1] . ";\n\n";
            
            $output .= "-- Dumping data for table `$table`\n";
            $rows = $this->db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $fields = implode("', '", array_map([$this->db, 'quote'], $row));
                $output .= "INSERT INTO `$table` VALUES ('$fields');\n";
            }
            $output .= "\n";
        }
        
        return file_put_contents($filepath, $output) !== false ? $filename : false;
    }
}