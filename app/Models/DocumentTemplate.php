<?php
namespace App\Models;

use Core\Database;

/**
 * Modelo para las plantillas de documento
 * 
 * Gestiona las operaciones relacionadas con plantillas de áreas predefinidas
 * que pueden aplicarse a documentos similares
 */
class DocumentTemplate {
    /**
     * Instancia de la base de datos
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Constructor de la clase DocumentTemplate
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea una nueva plantilla de documento
     * 
     * @param array $data Datos de la plantilla
     * @return int|false ID de la plantilla creada o false en caso de error
     */
    public function create(array $data) {
        $sql = "INSERT INTO document_templates (user_id, name, description, created_at) 
                VALUES (:user_id, :name, :description, NOW())";
                
        $this->db->query($sql, [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? ''
        ]);
        
        $templateId = $this->db->getLastInsertId();
        
        // Si se proporcionaron áreas, guardarlas
        if (isset($data['areas']) && is_array($data['areas']) && !empty($data['areas'])) {
            $this->saveAreas($templateId, $data['areas']);
        }
        
        return $templateId;
    }
    
    /**
     * Encuentra una plantilla por su ID
     * 
     * @param int $id ID de la plantilla
     * @return array|false Datos de la plantilla o false si no existe
     */
    public function findById($id) {
        $sql = "SELECT * FROM document_templates WHERE id = :id LIMIT 1";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Encuentra todas las plantillas de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Lista de plantillas
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM document_templates WHERE user_id = :user_id ORDER BY name ASC";
        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Actualiza una plantilla existente
     * 
     * @param int $id ID de la plantilla
     * @param array $data Nuevos datos
     * @return bool Resultado de la operación
     */
    public function update($id, array $data) {
        $fields = [];
        $params = ['id' => $id];
        
        // Construir la lista de campos a actualizar
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'areas') {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE document_templates SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $result = $this->db->query($sql, $params);
        
        // Si se proporcionaron áreas, actualizar las existentes
        if (isset($data['areas']) && is_array($data['areas'])) {
            // Eliminar áreas existentes
            $this->deleteAreas($id);
            
            // Guardar nuevas áreas
            if (!empty($data['areas'])) {
                $this->saveAreas($id, $data['areas']);
            }
        }
        
        return $result;
    }
    
    /**
     * Elimina una plantilla y sus áreas asociadas
     * 
     * @param int $id ID de la plantilla
     * @return bool Resultado de la operación
     */
    public function delete($id) {
        // Primero eliminar las áreas asociadas
        $this->deleteAreas($id);
        
        // Luego eliminar la plantilla
        $sql = "DELETE FROM document_templates WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
    
    /**
     * Guarda las áreas de una plantilla
     * 
     * @param int $templateId ID de la plantilla
     * @param array $areas Lista de áreas
     * @return bool Resultado de la operación
     */
    public function saveAreas($templateId, array $areas) {
        $sql = "INSERT INTO template_areas (template_id, column_name, x_pos, y_pos, width, height, 
                    page_number, created_at) 
                VALUES (:template_id, :column_name, :x_pos, :y_pos, :width, :height, 
                    :page_number, NOW())";
        
        foreach ($areas as $area) {
            $this->db->query($sql, [
                'template_id' => $templateId,
                'column_name' => $area['column_name'],
                'x_pos' => $area['x_pos'] ?? $area['x'],
                'y_pos' => $area['y_pos'] ?? $area['y'],
                'width' => $area['width'],
                'height' => $area['height'],
                'page_number' => $area['page_number'] ?? $area['page'] ?? 1
            ]);
        }
        
        return true;
    }
    
    /**
     * Elimina todas las áreas de una plantilla
     * 
     * @param int $templateId ID de la plantilla
     * @return bool Resultado de la operación
     */
    public function deleteAreas($templateId) {
        $sql = "DELETE FROM template_areas WHERE template_id = :template_id";
        return $this->db->query($sql, ['template_id' => $templateId]);
    }
    
    /**
     * Obtiene todas las áreas de una plantilla
     * 
     * @param int $templateId ID de la plantilla
     * @return array Lista de áreas
     */
    public function getAreas($templateId) {
        $sql = "SELECT * FROM template_areas WHERE template_id = :template_id ORDER BY id ASC";
        $stmt = $this->db->query($sql, ['template_id' => $templateId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Aplica una plantilla a un documento, creando áreas basadas en la plantilla
     * 
     * @param int $templateId ID de la plantilla
     * @param int $documentId ID del documento
     * @return bool Resultado de la operación
     */
    public function applyToDocument($templateId, $documentId) {
        // Obtener las áreas de la plantilla
        $areas = $this->getAreas($templateId);
        
        if (empty($areas)) {
            return false;
        }
        
        // Crear un modelo de área de documento
        $documentAreaModel = new DocumentArea();
        
        // Primero eliminar las áreas existentes en el documento
        $documentAreaModel->deleteByDocumentId($documentId);
        
        // Crear nuevas áreas basadas en la plantilla
        foreach ($areas as $area) {
            $documentAreaModel->create([
                'document_id' => $documentId,
                'column_name' => $area['column_name'],
                'x_pos' => $area['x_pos'],
                'y_pos' => $area['y_pos'],
                'width' => $area['width'],
                'height' => $area['height'],
                'page_number' => $area['page_number']
            ]);
        }
        
        return true;
    }
    
    /**
     * Busca plantillas por nombre o descripción
     * 
     * @param int $userId ID del usuario propietario
     * @param string $query Término de búsqueda
     * @return array Lista de plantillas que coinciden
     */
    public function search($userId, $query) {
        $sql = "SELECT * FROM document_templates 
                WHERE user_id = :user_id 
                AND (name LIKE :query OR description LIKE :query) 
                ORDER BY name ASC";
                
        $stmt = $this->db->query($sql, [
            'user_id' => $userId,
            'query' => '%' . $query . '%'
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cuenta el número de plantillas de un usuario
     * 
     * @param int $userId ID del usuario
     * @return int Número de plantillas
     */
    public function countByUserId($userId) {
        $sql = "SELECT COUNT(*) as total FROM document_templates WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Verifica si un usuario tiene acceso a una plantilla
     * 
     * @param int $templateId ID de la plantilla
     * @param int $userId ID del usuario
     * @return bool True si tiene acceso, false en caso contrario
     */
    public function userHasAccess($templateId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM document_templates 
                WHERE id = :template_id AND user_id = :user_id";
                
        $stmt = $this->db->query($sql, [
            'template_id' => $templateId,
            'user_id' => $userId
        ]);
        
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }
}