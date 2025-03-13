<?php
namespace App\Models;

use Core\Database;

class Document {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO documents (user_id, filename, original_filename, file_size, 
                    file_path, created_at) 
                VALUES (:user_id, :filename, :original_filename, :file_size, :file_path, NOW())";
        
        $this->db->query($sql, [
            'user_id' => $data['user_id'],
            'filename' => $data['filename'],
            'original_filename' => $data['original_filename'],
            'file_size' => $data['file_size'],
            'file_path' => $data['file_path']
        ]);
        
        return $this->db->getLastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM documents WHERE id = :id LIMIT 1";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->fetch();
    }
    
    public function findByUserId($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM documents WHERE user_id = :user_id 
                ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->query($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        return $stmt->fetchAll();
    }
    
    public function countByUserId($userId) {
        $sql = "SELECT COUNT(*) as total FROM documents WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    public function delete($id) {
        // Primero eliminamos las áreas asociadas
        $this->deleteAreas($id);
        
        // Luego eliminamos el documento
        $sql = "DELETE FROM documents WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
    
    public function deleteAreas($documentId) {
        $sql = "DELETE FROM document_areas WHERE document_id = :document_id";
        return $this->db->query($sql, ['document_id' => $documentId]);
    }

    /**
     * Obtiene estadísticas de documentos agrupadas por usuario
     * 
     * @return array Estadísticas por usuario
     */
    public function getStatsPerUser() {
        $sql = "SELECT 
                    u.id, 
                    u.name, 
                    u.email,
                    COUNT(d.id) AS document_count,
                    SUM(d.file_size) AS total_size,
                    MAX(d.created_at) AS last_upload
                FROM documents d
                JOIN users u ON d.user_id = u.id
                GROUP BY u.id
                ORDER BY document_count DESC";
                
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene estadísticas de documentos agrupadas por mes
     * 
     * @param int $limit Número de meses a obtener
     * @return array Estadísticas por mes
     */
    public function getStatsPerMonth($limit = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS document_count,
                    SUM(file_size) AS total_size
                FROM documents 
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT :limit";
                
        $stmt = $this->db->query($sql, ['limit' => $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el historial de procesamiento de documentos para un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $limit Número máximo de registros
     * @param int $offset Desplazamiento para paginación
     * @return array Historial de procesamiento
     */
    public function getProcessingHistory($userId, $limit = 50, $offset = 0) {
        // Esta consulta asume que tienes una tabla de actividad o logs
        // Si no tienes esta tabla, necesitarás crearla o modificar esta consulta
        
        $sql = "SELECT 
                    a.id,
                    a.type,
                    a.document_id,
                    a.details,
                    a.status,
                    a.created_at,
                    d.original_filename AS document_name
                FROM activity_logs a
                LEFT JOIN documents d ON a.document_id = d.id
                WHERE a.user_id = :user_id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset";
                
        $stmt = $this->db->query($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        return $stmt->fetchAll();
    }
    /**
     * Cuenta el número total de documentos en el sistema
     * 
     * @return int Número total de documentos
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM documents";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Encuentra los documentos más recientes del sistema
     * 
     * @param int $limit Número máximo de documentos a retornar
     * @return array Lista de documentos recientes
     */
    public function findRecent($limit = 5) {
        $sql = "SELECT d.*, u.name as user_name 
                FROM documents d
                JOIN users u ON d.user_id = u.id
                ORDER BY d.created_at DESC 
                LIMIT :limit";
                
        $stmt = $this->db->query($sql, ['limit' => $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Registra una actividad en el historial
     * 
     * @param array $data Datos de la actividad
     * @return int|bool ID de la actividad o false en caso de error
     */
    public function logActivity($data) {
        $sql = "INSERT INTO activity_logs (user_id, document_id, type, details, status, created_at)
                VALUES (:user_id, :document_id, :type, :details, :status, NOW())";
                
        $this->db->query($sql, [
            'user_id' => $data['user_id'],
            'document_id' => $data['document_id'] ?? null,
            'type' => $data['type'],
            'details' => $data['details'] ?? '',
            'status' => $data['status'] ?? 'success'
        ]);
        
        return $this->db->getLastInsertId();
    }
}