<?php
namespace Core;

/**
 * Clase de configuración de la aplicación
 * 
 * Carga y proporciona acceso a la configuración del sistema
 */
class Config {
    /**
     * Carga variables de entorno desde .env
     */
    private static function loadEnv() {
        $envFile = ROOT_PATH . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentarios
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Eliminar comillas si existen
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    /**
     * Almacena todos los valores de configuración
     * 
     * @var array
     */
    protected static $config = [];
    
    /**
     * Carga configuraciones desde un archivo
     * 
     * @param string $file Ruta al archivo de configuración (PHP que retorna un array)
     * @param string $namespace Espacio de nombres para las configuraciones
     * @return void
     */
    public static function load($file, $namespace = 'app') {
        // Cargar variables de entorno desde .env
        self::loadEnv();
        if (file_exists($file)) {
            $config = require $file;
            
            if (is_array($config)) {
                if (!isset(self::$config[$namespace])) {
                    self::$config[$namespace] = [];
                }
                
                self::$config[$namespace] = array_merge(self::$config[$namespace], $config);
            }
        }
    }
    
    /**
     * Obtiene un valor de configuración
     * 
     * @param string $key Clave en formato 'namespace.key.subkey'
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed
     */
    public static function get($key, $default = null) {
        $parts = explode('.', $key);
        $config = self::$config;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            
            $config = $config[$part];
        }
        
        return $config;
    }
    
    /**
     * Establece un valor de configuración
     * 
     * @param string $key Clave en formato 'namespace.key.subkey'
     * @param mixed $value Valor a establecer
     * @return void
     */
    public static function set($key, $value) {
        $parts = explode('.', $key);
        $current = &self::$config;
        
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $current[$part] = $value;
            } else {
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    $current[$part] = [];
                }
                
                $current = &$current[$part];
            }
        }
    }
    
    /**
     * Verifica si existe una clave de configuración
     * 
     * @param string $key Clave a verificar
     * @return bool
     */
    public static function has($key) {
        $parts = explode('.', $key);
        $config = self::$config;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return false;
            }
            
            $config = $config[$part];
        }
        
        return true;
    }
    
    /**
     * Obtiene todos los valores de configuración
     * 
     * @param string $namespace Espacio de nombres específico (opcional)
     * @return array
     */
    public static function all($namespace = null) {
        if ($namespace !== null) {
            return self::$config[$namespace] ?? [];
        }
        
        return self::$config;
    }
}