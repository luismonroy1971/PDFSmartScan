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
     * Elimina un documento
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        try {
            // Eliminar archivo físico
            $filePath = APP_PATH . '/public/' . $this->file_path;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Eliminar áreas asociadas
            DocumentArea::deleteByDocumentId($this->id);
            
            // Eliminar registro de base de datos
            $stmt = $db->prepare("DELETE FROM documents WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
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
     * Busca documentos por ID de usuario
     */
    public static function findByUserId($userId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM documents WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute(['user_id' => $userId]);
            
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
}