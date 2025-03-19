<?php

namespace App\Core;

class Validator
{
    protected $data;
    protected $errors = [];
    protected $rules = [
        'required' => 'El campo %s es obligatorio.',
        'email' => 'El campo %s debe ser una dirección de correo electrónico válida.',
        'min' => 'El campo %s debe tener al menos %d caracteres.',
        'max' => 'El campo %s no debe tener más de %d caracteres.',
        'matches' => 'El campo %s debe coincidir con el campo %s.',
        'unique' => 'El valor del campo %s ya está en uso.',
        'numeric' => 'El campo %s debe ser un número.',
        'integer' => 'El campo %s debe ser un número entero.',
        'float' => 'El campo %s debe ser un número decimal.',
        'alpha' => 'El campo %s solo debe contener letras.',
        'alphanumeric' => 'El campo %s solo debe contener letras y números.',
        'url' => 'El campo %s debe ser una URL válida.',
        'date' => 'El campo %s debe ser una fecha válida.',
    ];
    
    /**
     * Constructor
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Valida que los campos requeridos estén presentes y no vacíos
     */
    public function required($fields, $message = null)
    {
        foreach ((array) $fields as $field) {
            if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
                $this->addError($field, 'required', $message);
            }
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea un email válido
     */
    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo tenga una longitud mínima
     */
    public function min($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->addError($field, 'min', $message, [$length]);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo tenga una longitud máxima
     */
    public function max($field, $length, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->addError($field, 'max', $message, [$length]);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo coincida con otro
     */
    public function matches($field, $matchField, $message = null)
    {
        if (isset($this->data[$field], $this->data[$matchField]) && 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->addError($field, 'matches', $message, [$matchField]);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea único en la base de datos
     */
    public function unique($field, $table, $column = null, $exceptId = null, $message = null)
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?? $field;
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $params = [$this->data[$field]];
        
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        $result = $db->query($sql, $params)->fetch();
        
        if ($result['count'] > 0) {
            $this->addError($field, 'unique', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea numérico
     */
    public function numeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($field, 'numeric', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea un entero
     */
    public function integer($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->addError($field, 'integer', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea un número decimal
     */
    public function float($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_FLOAT)) {
            $this->addError($field, 'float', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo contenga solo letras
     */
    public function alpha($field, $message = null)
    {
        if (isset($this->data[$field]) && !preg_match('/^[a-zA-Z]+$/', $this->data[$field])) {
            $this->addError($field, 'alpha', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo contenga solo letras y números
     */
    public function alphanumeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !preg_match('/^[a-zA-Z0-9]+$/', $this->data[$field])) {
            $this->addError($field, 'alphanumeric', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea una URL válida
     */
    public function url($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->addError($field, 'url', $message);
        }
        
        return $this;
    }
    
    /**
     * Valida que un campo sea una fecha válida
     */
    public function date($field, $format = 'Y-m-d', $message = null)
    {
        if (isset($this->data[$field])) {
            $date = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->addError($field, 'date', $message);
            }
        }
        
        return $this;
    }
    
    /**
     * Añade un error personalizado
     */
    public function addCustomError($field, $message)
    {
        $this->errors[$field][] = $message;
        return $this;
    }
    
    /**
     * Añade un error al campo especificado
     */
    protected function addError($field, $rule, $customMessage = null, $params = [])
    {
        if ($customMessage) {
            $message = $customMessage;
        } else {
            $message = $this->rules[$rule];
            array_unshift($params, $field);
            $message = vsprintf($message, $params);
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Verifica si la validación ha pasado
     */
    public function isValid()
    {
        return empty($this->errors);
    }
    
    /**
     * Obtiene todos los errores
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Obtiene los errores de un campo específico
     */
    public function getFieldErrors($field)
    {
        return $this->errors[$field] ?? [];
    }
}