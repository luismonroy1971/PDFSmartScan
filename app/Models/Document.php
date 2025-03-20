<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Document
{
    public $id;
    public $user_id;
    public $filename;
    public $original_filename;
    public $file_size;
    public $file_path;
    public $created_at;
    public $updated_at;
    
    /**
     * Guarda un nuevo documento o actualiza uno existente
     */
    public function save()
    {
        $db = Database::getInstance();
        
        // Preparar datos
        $data = [
            'user_id' => $this->user_id,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'file_path' => $this->file_path
        ];
        
        if (isset($this->id)) {
            // Actualizar documento existente
            $data['id'] = $this->id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE documents SET 
                    user_id = :user_id,
                    filename = :filename,
                    original_filename = :original_filename,
                    file_size = :file_size,
                    file_path = :file_path,
                    updated_at = :updated_at
                    WHERE id = :id";
        } else {
            // Crear nuevo documento
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = $data['created_at'];
            
            $sql = "INSERT INTO documents 
                    (user_id, filename, original_filename, file_size, file_path, created_at, updated_at) 
                    VALUES 
                    (:user_id, :filename, :original_filename, :file_size, :file_path, :created_at, :updated_at)";
        }
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!isset($this->id) && $result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error al guardar documento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un documento (método de instancia)
     * 
     * @return bool Éxito o fracaso de la operación
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        return self::deleteById($this->id);
    }
    
    /**
     * Elimina un documento por su ID (método estático)
     * 
     * @param int $id ID del documento a eliminar
     * @return bool Éxito o fracaso de la operación
     */
    public static function deleteById($id)
    {
        $db = Database::getInstance();
        
        try {
            // Obtener información del documento
            $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document) {
                return false;
            }
            
            // Eliminar archivo físico
            $filePath = APP_PATH . '/public/' . $document['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Eliminar áreas asociadas si existe la clase DocumentArea
            if (class_exists('\App\Models\DocumentArea')) {
                \App\Models\DocumentArea::deleteByDocumentId($id);
            }
            
            // Eliminar registro de base de datos
            $stmt = $db->prepare("DELETE FROM documents WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar documento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un documento por su ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM documents WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar documento: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca un documento por su ID (alias de find)
     * 
     * @param int $id ID del documento
     * @return Document|null Documento encontrado o null
     */
    public static function findById($id)
    {
        return self::find($id);
    }
    
    /**
     * Busca documentos por ID de usuario con paginación
     * 
     * @param int $userId ID del usuario
     * @param int $limit Límite de resultados por página
     * @param int $offset Desplazamiento para paginación
     * @return array Documentos encontrados
     */
    public static function findByUserId($userId, $limit = null, $offset = 0)
    {
        $db = Database::getInstance();
        
        try {
            $sql = "SELECT * FROM documents WHERE user_id = :user_id ORDER BY created_at DESC";
            
            // Añadir límite y desplazamiento si se especifican
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $documents[] = self::createFromArray($row);
            }
            
            return $documents;
        } catch (\PDOException $e) {
            error_log("Error al buscar documentos por usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Alias de findByUserId para mantener consistencia con otros modelos
     * 
     * @param int $userId ID del usuario
     * @return array Documentos encontrados
     */
    public static function findAllByUser($userId)
    {
        return self::findByUserId($userId);
    }
    
    /**
     * Cuenta el número de documentos de un usuario
     * 
     * @param int $userId ID del usuario
     * @return int Número de documentos
     */
    public static function countByUserId($userId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error al contar documentos por usuario: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Cuenta el número total de documentos en el sistema
     * 
     * @return int Número total de documentos
     */
    public static function countAll()
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM documents");
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error al contar todos los documentos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Encuentra los documentos más recientes
     * 
     * @param int $limit Número máximo de documentos a retornar
     * @return array Documentos recientes
     */
    public static function findRecent($limit = 5)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM documents ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $documents = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $documents[] = self::createFromArray($row);
            }
            
            return $documents;
        } catch (\PDOException $e) {
            error_log("Error al buscar documentos recientes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crea una instancia de Document a partir de un array
     */
    private static function createFromArray($data)
    {
        $document = new self();
        $document->id = $data['id'];
        $document->user_id = $data['user_id'];
        $document->filename = $data['filename'];
        $document->original_filename = $data['original_filename'];
        $document->file_size = $data['file_size'];
        $document->file_path = $data['file_path'];
        $document->created_at = $data['created_at'];
        $document->updated_at = $data['updated_at'];
        
        return $document;
    }
    
    /**
     * Obtiene estadísticas de documentos por usuario
     * 
     * @return array Estadísticas por usuario
     */
    public static function getStatsPerUser()
    {
        $db = Database::getInstance();
        
        try {
            $sql = "SELECT u.id, u.name, u.email, COUNT(d.id) as document_count, 
                   SUM(d.file_size) as total_size, MAX(d.created_at) as last_upload 
                   FROM users u 
                   LEFT JOIN documents d ON u.id = d.user_id 
                   GROUP BY u.id, u.name, u.email 
                   ORDER BY document_count DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener estadísticas por usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas de documentos por mes
     * 
     * @param int $months Número de meses a considerar
     * @return array Estadísticas por mes
     */
    public static function getStatsPerMonth($months = 12)
    {
        $db = Database::getInstance();
        
        try {
            // Esta consulta puede variar según el motor de base de datos
            // Este ejemplo es para MySQL
            $sql = "SELECT 
                   DATE_FORMAT(created_at, '%Y-%m') as month, 
                   COUNT(*) as document_count, 
                   SUM(file_size) as total_size 
                   FROM documents 
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH) 
                   GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                   ORDER BY month DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener estadísticas por mes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el historial de procesamiento de documentos de un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $limit Límite de resultados
     * @return array Historial de procesamiento
     */
    public static function getProcessingHistory($userId, $limit = 20)
    {
        $db = Database::getInstance();
        
        try {
            // Esta consulta asume que hay una tabla de historial o logs
            // Si no existe, se podría adaptar para usar la tabla de documentos
            $sql = "SELECT d.id, d.original_filename, d.created_at, d.updated_at, 
                   d.file_size, COUNT(da.id) as areas_count 
                   FROM documents d 
                   LEFT JOIN document_areas da ON d.id = da.document_id 
                   WHERE d.user_id = :user_id 
                   GROUP BY d.id, d.original_filename, d.created_at, d.updated_at, d.file_size 
                   ORDER BY d.created_at DESC 
                   LIMIT :limit";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener historial de procesamiento: " . $e->getMessage());
            return [];
        }
    }
}