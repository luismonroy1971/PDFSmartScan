<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DocumentArea
{
    public $id;
    public $document_id;
    public $column_name;
    public $x_pos;
    public $y_pos;
    public $width;
    public $height;
    public $page_number;
    public $created_at;
    public $updated_at;
    
    /**
     * Guarda un área de documento nueva o actualiza una existente
     */
    public function save()
    {
        $db = Database::getInstance();
        
        // Preparar datos
        $data = [
            'document_id' => $this->document_id,
            'column_name' => $this->column_name,
            'x_pos' => $this->x_pos,
            'y_pos' => $this->y_pos,
            'width' => $this->width,
            'height' => $this->height,
            'page_number' => $this->page_number
        ];
        
        if (isset($this->id)) {
            // Actualizar área existente
            $data['id'] = $this->id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE document_areas SET 
                    document_id = :document_id,
                    column_name = :column_name,
                    x_pos = :x_pos,
                    y_pos = :y_pos,
                    width = :width,
                    height = :height,
                    page_number = :page_number,
                    updated_at = :updated_at
                    WHERE id = :id";
        } else {
            // Crear nueva área
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = $data['created_at'];
            
            $sql = "INSERT INTO document_areas 
                    (document_id, column_name, x_pos, y_pos, width, height, page_number, created_at, updated_at) 
                    VALUES 
                    (:document_id, :column_name, :x_pos, :y_pos, :width, :height, :page_number, :created_at, :updated_at)";
        }
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!isset($this->id) && $result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error al guardar área de documento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un área de documento
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("DELETE FROM document_areas WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar área de documento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un área por su ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM document_areas WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar área de documento: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca áreas por ID de documento
     */
    public static function findByDocumentId($documentId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM document_areas WHERE document_id = :document_id ORDER BY id ASC");
            $stmt->execute(['document_id' => $documentId]);
            
            $areas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $areas[] = self::createFromArray($row);
            }
            
            return $areas;
        } catch (\PDOException $e) {
            error_log("Error al buscar áreas por documento: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca un área por documento y nombre de columna
     */
    public static function findByDocumentAndColumn($documentId, $columnName)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM document_areas WHERE document_id = :document_id AND column_name = :column_name");
            $stmt->execute([
                'document_id' => $documentId,
                'column_name' => $columnName
            ]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar área por documento y columna: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Elimina todas las áreas de un documento
     */
    public static function deleteByDocumentId($documentId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("DELETE FROM document_areas WHERE document_id = :document_id");
            return $stmt->execute(['document_id' => $documentId]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar áreas por documento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una instancia de DocumentArea a partir de un array
     */
    private static function createFromArray($data)
    {
        $area = new self();
        $area->id = $data['id'];
        $area->document_id = $data['document_id'];
        $area->column_name = $data['column_name'];
        $area->x_pos = $data['x_pos'];
        $area->y_pos = $data['y_pos'];
        $area->width = $data['width'];
        $area->height = $data['height'];
        $area->page_number = $data['page_number'];
        $area->created_at = $data['created_at'];
        $area->updated_at = $data['updated_at'];
        
        return $area;
    }
    
    /**
     * Alias de findByDocumentId para mantener consistencia con otros modelos
     * 
     * @param int $documentId ID del documento
     * @return array Áreas encontradas
     */
    public static function findAllByDocument($documentId)
    {
        return self::findByDocumentId($documentId);
    }
    
    /**
     * Obtiene las coordenadas del área para extracción de imagen
     * 
     * @return array Coordenadas del área
     */
    public function getCoordinates()
    {
        return [
            'x' => $this->x_pos,
            'y' => $this->y_pos,
            'width' => $this->width,
            'height' => $this->height,
            'page' => $this->page_number
        ];
    }
}