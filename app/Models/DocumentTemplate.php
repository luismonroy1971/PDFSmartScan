<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DocumentTemplate
{
    public $id;
    public $user_id;
    public $name;
    public $description;
    public $created_at;
    public $updated_at;
    
    /**
     * Guarda una plantilla nueva o actualiza una existente
     */
    public function save()
    {
        $db = Database::getInstance();
        
        // Preparar datos
        $data = [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description
        ];
        
        if (isset($this->id)) {
            // Actualizar plantilla existente
            $data['id'] = $this->id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE document_templates SET 
                    user_id = :user_id,
                    name = :name,
                    description = :description,
                    updated_at = :updated_at
                    WHERE id = :id";
        } else {
            // Crear nueva plantilla
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = $data['created_at'];
            
            $sql = "INSERT INTO document_templates 
                    (user_id, name, description, created_at, updated_at) 
                    VALUES 
                    (:user_id, :name, :description, :created_at, :updated_at)";
        }
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!isset($this->id) && $result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error al guardar plantilla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina una plantilla
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        try {
            // Eliminar áreas asociadas a la plantilla
            TemplateArea::deleteByTemplateId($this->id);
            
            // Eliminar la plantilla
            $stmt = $db->prepare("DELETE FROM document_templates WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar plantilla: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca una plantilla por su ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM document_templates WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar plantilla: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca plantillas por ID de usuario
     */
    public static function findByUserId($userId)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM document_templates WHERE user_id = :user_id ORDER BY name ASC");
            $stmt->execute(['user_id' => $userId]);
            
            $templates = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $templates[] = self::createFromArray($row);
            }
            
            return $templates;
        } catch (\PDOException $e) {
            error_log("Error al buscar plantillas por usuario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crea una instancia de DocumentTemplate a partir de un array
     */
    private static function createFromArray($data)
    {
        $template = new self();
        $template->id = $data['id'];
        $template->user_id = $data['user_id'];
        $template->name = $data['name'];
        $template->description = $data['description'];
        $template->created_at = $data['created_at'];
        $template->updated_at = $data['updated_at'];
        
        return $template;
    }
}