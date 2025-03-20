<?php

namespace App\Core;

/**
 * Clase Request para manejar solicitudes HTTP
 * 
 * Proporciona métodos para acceder a los datos de la solicitud actual
 */
class Request
{
    /**
     * Obtiene un valor del array $_GET
     * 
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }
    
    /**
     * Obtiene un valor del array $_POST
     * 
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }
    
    /**
     * Obtiene un valor del array $_FILES
     * 
     * @param string $key Clave a buscar
     * @return array|null
     */
    public function files($key = null)
    {
        if ($key === null) {
            return $_FILES;
        }
        
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }
    
    /**
     * Obtiene el método de la solicitud HTTP
     * 
     * @return string
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Verifica si la solicitud es mediante AJAX
     * 
     * @return bool
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtiene la URI de la solicitud
     * 
     * @return string
     */
    public function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Obtiene la IP del cliente
     * 
     * @return string
     */
    public function ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Verifica si la solicitud es mediante HTTPS
     * 
     * @return bool
     */
    public function isSecure()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * Obtiene todos los datos de entrada (combinación de GET y POST)
     * 
     * @return array
     */
    public function all()
    {
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Verifica si existe un parámetro en la solicitud
     * 
     * @param string $key Clave a verificar
     * @return bool
     */
    public function has($key)
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }
    
    /**
     * Obtiene un valor de la solicitud (GET o POST)
     * 
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->all();
        }
        
        // Buscar primero en POST, luego en GET
        if (isset($_POST[$key])) {
            return $this->sanitize($_POST[$key]);
        }
        
        if (isset($_GET[$key])) {
            return $this->sanitize($_GET[$key]);
        }
        
        return $default;
    }
    
    /**
     * Obtiene solo los datos especificados de la solicitud
     * 
     * @param array $keys Claves a obtener
     * @return array
     */
    public function only(array $keys)
    {
        $results = [];
        
        foreach ($keys as $key) {
            $results[$key] = $this->input($key);
        }
        
        return $results;
    }
    
    /**
     * Obtiene todos los datos excepto los especificados
     * 
     * @param array $keys Claves a excluir
     * @return array
     */
    public function except(array $keys)
    {
        $all = $this->all();
        
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        
        return $all;
    }
    
    /**
     * Sanitiza un valor para prevenir XSS
     * 
     * @param mixed $value Valor a sanitizar
     * @return mixed
     */
    protected function sanitize($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->sanitize($val);
            }
            return $value;
        }
        
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}