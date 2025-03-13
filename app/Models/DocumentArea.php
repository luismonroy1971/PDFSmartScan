<?php
namespace App\Models;

use Core\Database;

class DocumentArea {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO document_areas (document_id, column_name, x_pos, y_pos, width, height, 
                    page_number, created_at) 
                VALUES (:document_id, :column_name, :x_pos, :y_pos, :width, :height, 
                    :page_number, NOW())";
        
        $this->db->query($sql, [
            'document_id' => $data['document_id'],
            'column_name' => $data['column_name'],
            'x_pos' => $data['x_pos'],
            'y_pos' => $data['y_pos'],
            'width' => $data['width'],
            'height' => $data['height'],
            'page_number' => $data['page_number']
        ]);
        
        return $this->db->getLastInsertId();
    }
    
    public function findByDocumentId($documentId) {
        $sql = "SELECT * FROM document_areas WHERE document_id = :document_id ORDER BY id ASC";
        $stmt = $this->db->query($sql, ['document_id' => $documentId]);
        return $stmt->fetchAll();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        
        $sql = "UPDATE document_areas SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        return $this->db->query($sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM document_areas WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
    
    public function deleteByDocumentId($documentId) {
        $sql = "DELETE FROM document_areas WHERE document_id = :document_id";
        return $this->db->query($sql, ['document_id' => $documentId]);
    }
}