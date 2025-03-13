<?php
namespace Core;

/**
 * Clase para gestionar sesiones de forma segura
 * 
 * Proporciona métodos para trabajar con datos de sesión y flash messages
 */
class Session {
    /**
     * Indica si la sesión ha sido iniciada
     * 
     * @var bool
     */
    protected static $started = false;
    
    /**
     * Prefijo para mensajes flash
     * 
     * @var string
     */
    protected static $flashPrefix = 'flash_';
    
    /**
     * Inicia la sesión con opciones configurables
     * 
     * @param array $options Opciones de configuración de sesión
     * @return bool
     */
    public static function start(array $options = []) {
        if (self::$started) {
            return true;
        }
        
        // Establecer opciones de sesión seguras por defecto
        $defaultOptions = [
            'use_strict_mode' => 1,
            'use_cookies' => 1,
            'use_only_cookies' => 1,
            'cookie_httponly' => 1,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'cookie_samesite' => 'Lax',
            'gc_maxlifetime' => 7200 // 2 horas
        ];
        
        // Combinar opciones por defecto con las proporcionadas
        $sessionOptions = array_merge($defaultOptions, $options);
        
        // Aplicar opciones a la configuración de PHP
        foreach ($sessionOptions as $key => $value) {
            if (is_string($key) && strpos($key, 'cookie_') === 0 && version_compare(PHP_VERSION, '7.3.0', '<')) {
                // Para versiones de PHP < 7.3, manejar opciones de cookie de forma diferente
                continue;
            }
            
            $option = str_replace('_', '.', $key);
            ini_set('session.' . $option, $value);
        }
        
        // Iniciar sesión
        $result = session_start();
        
        if ($result) {
            self::$started = true;
            
            // Regenerar ID si no hay sesión activa
            if (empty($_SESSION)) {
                self::regenerateId();
            }
            
            // Procesar mensajes flash antiguos
            self::processFlashMessages();
        }
        
        return $result;
    }
    
    /**
     * Regenera el ID de sesión
     * 
     * @param bool $deleteOldSession Eliminar datos de sesión antigua
     * @return bool
     */
    public static function regenerateId($deleteOldSession = false) {
        if (!self::isStarted()) {
            self::start();
        }
        
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Verifica si la sesión ha sido iniciada
     * 
     * @return bool
     */
    public static function isStarted() {
        return self::$started || session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Obtiene un valor de sesión
     * 
     * @param string $key Clave del valor
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (!self::isStarted()) {
            self::start();
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Establece un valor de sesión
     * 
     * @param string $key Clave del valor
     * @param mixed $value Valor a guardar
     * @return void
     */
    public static function set($key, $value) {
        if (!self::isStarted()) {
            self::start();
        }
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Verifica si existe una clave en la sesión
     * 
     * @param string $key Clave a verificar
     * @return bool
     */
    public static function has($key) {
        if (!self::isStarted()) {
            self::start();
        }
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Elimina un valor de la sesión
     * 
     * @param string $key Clave a eliminar
     * @return void
     */
    public static function remove($key) {
        if (!self::isStarted()) {
            self::start();
        }
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Establece un mensaje flash (disponible solo hasta la próxima solicitud)
     * 
     * @param string $key Clave del mensaje
     * @param mixed $value Valor del mensaje
     * @return void
     */
    public static function setFlash($key, $value) {
        if (!self::isStarted()) {
            self::start();
        }
        
        $_SESSION[self::$flashPrefix . $key] = [
            'value' => $value,
            'is_new' => true
        ];
    }
    
    /**
     * Obtiene un mensaje flash
     * 
     * @param string $key Clave del mensaje
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function getFlash($key, $default = null) {
        if (!self::isStarted()) {
            self::start();
        }
        
        $flashKey = self::$flashPrefix . $key;
        
        if (isset($_SESSION[$flashKey])) {
            $flash = $_SESSION[$flashKey];
            
            // Marcar como leído para eliminación en la próxima solicitud
            $_SESSION[$flashKey]['is_new'] = false;
            
            return $flash['value'];
        }
        
        return $default;
    }
    
    /**
     * Verifica si existe un mensaje flash
     * 
     * @param string $key Clave a verificar
     * @return bool
     */
    public static function hasFlash($key) {
        if (!self::isStarted()) {
            self::start();
        }
        
        return isset($_SESSION[self::$flashPrefix . $key]);
    }
    
    /**
     * Procesa los mensajes flash, eliminando los que ya han sido leídos
     * 
     * @return void
     */
    protected static function processFlashMessages() {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, self::$flashPrefix) === 0 && is_array($value) && isset($value['is_new'])) {
                if ($value['is_new'] === false) {
                    unset($_SESSION[$key]);
                } else {
                    $_SESSION[$key]['is_new'] = false;
                }
            }
        }
    }
    
    /**
     * Obtiene todos los datos de sesión
     * 
     * @return array
     */
    public static function all() {
        if (!self::isStarted()) {
            self::start();
        }
        
        return $_SESSION;
    }
    
    /**
     * Limpia todos los datos de sesión
     * 
     * @return void
     */
    public static function clear() {
        if (!self::isStarted()) {
            self::start();
        }
        
        $_SESSION = [];
    }
    
    /**
     * Destruye completamente la sesión
     * 
     * @return bool
     */
    public static function destroy() {
        if (!self::isStarted()) {
            return false;
        }
        
        // Limpiar array de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión si existe
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destruir sesión
        $result = session_destroy();
        self::$started = false;
        
        return $result;
    }
}