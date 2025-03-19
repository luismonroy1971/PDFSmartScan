<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class TemplateArea
{
    public $id;
    public $template_id;
    public $column_name;
    public $x_pos;
    public $y_pos;
    public $width;
    public $height;
    public $page_number;
    public $created_at;
    public $updated_at;
    
    /**
     * Guarda un área de plantilla nueva o actualiza una existente
     */
    public function save()
    {
        $db = Database::getInstance();
        
        // Preparar datos
        $data = [
            'template_id' => $this->template_id,
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
            
            $sql = "UPDATE template_areas SET 
                    template_id = :template_id,
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
            
            $sql = "INSERT INTO template_areas 
                    (template_id, column_name, x_pos, y_pos, width, height, page_number, created_at, updated_at) 
                    VALUES 
                    (:template_id, :column_name, :x_pos, :y_pos, :width, :height, :page_number, :created_at, :updated_at)";
        }
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!isset($this->id) && $result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error al guardar área de plantilla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un área de plantilla
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("DELETE FROM template_areas WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar área de plantilla: " . $e->getMessage());
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
            $stmt = $db->prepare("SELECT * FROM template_areas WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar área de plantilla: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca áreas por ID de plantilla
     */
    public static function findByTemplateId($templateId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM template_areas WHERE template_id = :template_id ORDER BY id ASC");
            $stmt->execute(['template_id' => $templateId]);
            
            $areas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $areas[] = self::createFromArray($row);
            }
            
            return $areas;
        } catch (\PDOException $e) {
            error_log("Error al buscar áreas por plantilla: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Elimina todas las áreas de una plantilla
     */
    public static function deleteByTemplateId($templateId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("DELETE FROM template_areas WHERE template_id = :template_id");
            return $stmt->execute(['template_id' => $templateId]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar áreas por plantilla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea una instancia de TemplateArea a partir de un array
     */
    private static function createFromArray($data)
    {
        $area = new self();
        $area->id = $data['id'];
        $area->template_id = $data['template_id'];
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
}