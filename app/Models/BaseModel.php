<?php

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected static $table;
    
    /**
     * Encuentra un registro por su ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1",
            [$id]
        );
        
        return $stmt->fetchObject(static::class) ?: null;
    }
    
    /**
     * Encuentra todos los registros
     */
    public static function findAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM " . static::$table);
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
    
    /**
     * Guarda el modelo actual (inserta o actualiza)
     */
    public function save()
    {
        if (isset($this->id) && $this->id) {
            return $this->update();
        }
        
        return $this->insert();
    }
    
    /**
     * Inserta un nuevo registro
     */
    protected function insert()
    {
        $db = Database::getInstance();
        $properties = get_object_vars($this);
        
        // Filtrar propiedades que no son columnas
        unset($properties['id']);
        
        // Añadir timestamps
        $now = date('Y-m-d H:i:s');
        $properties['created_at'] = $now;
        $properties['updated_at'] = $now;
        
        $columns = array_keys($properties);
        $values = array_values($properties);
        $placeholders = array_fill(0, count($values), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $result = $db->query($sql, $values);
        
        if ($result) {
            $this->id = $db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Actualiza un registro existente
     */
    protected function update()
    {
        $db = Database::getInstance();
        $properties = get_object_vars($this);
        
        // Extraer ID
        $id = $properties['id'];
        unset($properties['id']);
        
        // Actualizar timestamp
        $properties['updated_at'] = date('Y-m-d H:i:s');
        
        $setParts = [];
        $values = [];
        
        foreach ($properties as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }
        
        $values[] = $id; // Para la cláusula WHERE
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE id = ?",
            static::$table,
            implode(', ', $setParts)
        );
        
        return $db->query($sql, $values);
    }
    
    /**
     * Elimina el registro actual
     */
    public function delete()
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        $db = Database::getInstance();
        $sql = sprintf("DELETE FROM %s WHERE id = ?", static::$table);
        
        return $db->query($sql, [$this->id]);
    }
}