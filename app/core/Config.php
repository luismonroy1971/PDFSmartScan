<?php

namespace App\Core;

class Config
{
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Carga la configuración desde archivos
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }
        
        // Cargar configuración desde .env
        self::loadEnv();
        
        // Cargar configuraciones predeterminadas
        $configFiles = glob(CONFIG_PATH . '/*.php');
        
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }
        
        self::$loaded = true;
    }
    
    /**
     * Carga variables de entorno desde .env
     */
    private static function loadEnv()
    {
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
     * Obtiene un valor de configuración
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        // Comprobar si es una clave anidada (por ejemplo, 'app.name')
        if (strpos($key, '.') !== false) {
            list($section, $item) = explode('.', $key, 2);
            
            if (isset(self::$config[$section][$item])) {
                return self::$config[$section][$item];
            }
            
            // Buscar en variables de entorno
            $envKey = strtoupper($section . '_' . $item);
            if (getenv($envKey) !== false) {
                return getenv($envKey);
            }
            
            return $default;
        }
        
        // Clave simple
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        // Buscar en variables de entorno
        $envKey = strtoupper($key);
        if (getenv($envKey) !== false) {
            return getenv($envKey);
        }
        
        return $default;
    }
    
    /**
     * Establece un valor de configuración
     */
    public static function set($key, $value)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        // Comprobar si es una clave anidada
        if (strpos($key, '.') !== false) {
            list($section, $item) = explode('.', $key, 2);
            
            if (!isset(self::$config[$section])) {
                self::$config[$section] = [];
            }
            
            self::$config[$section][$item] = $value;
        } else {
            self::$config[$key] = $value;
        }
    }
}