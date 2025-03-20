<?php

namespace App\Core;

class Session
{
    /**
     * Inicia la sesión si no está iniciada
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar cookies seguras
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'],
                $cookieParams['domain'],
                Config::get('app.secure', false),
                true
            );
            
            // Nombre de sesión personalizado
            session_name(Config::get('app.session_name', 'PDF_EXTRACT_SESSION'));
            
            session_start();
            
            // Regenerar ID de sesión periódicamente para prevenir ataques
            if (!isset($_SESSION['_last_regeneration'])) {
                self::regenerateId();
            } else {
                $regenerationTime = Config::get('app.session_regeneration_time', 300); // 5 minutos
                if (time() - $_SESSION['_last_regeneration'] > $regenerationTime) {
                    self::regenerateId();
                }
            }
        }
    }
    
    /**
     * Regenera el ID de sesión
     */
    public static function regenerateId()
    {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
    
    /**
     * Obtiene un valor de la sesión
     */
    public static function get($key, $default = null)
    {
        self::start();
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Establece un valor en la sesión
     */
    public static function set($key, $value)
    {
        self::start();
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Elimina un valor de la sesión
     */
    public static function delete($key)
    {
        self::start();
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Verifica si existe un valor en la sesión
     */
    public static function has($key)
    {
        self::start();
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Establece un mensaje flash (disponible solo para la siguiente solicitud)
     */
    public static function flash($key, $value)
    {
        self::start();
        
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Establece un mensaje flash (disponible solo para la siguiente solicitud)
     * Alias de flash() para mantener compatibilidad con el código existente
     */
    public static function setFlash($key, $value)
    {
        self::flash($key, $value);
    }
    
    /**
     * Obtiene un mensaje flash y lo elimina
     */
    public static function getFlash($key, $default = null)
    {
        self::start();
        
        $value = $_SESSION['_flash'][$key] ?? $default;
        
        if (isset($_SESSION['_flash'][$key])) {
            unset($_SESSION['_flash'][$key]);
        }
        
        return $value;
    }
    
    /**
     * Verifica si existe un mensaje flash
     */
    public static function hasFlash($key)
    {
        self::start();
        
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Destruye la sesión actual
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            
            // Eliminar cookie de sesión
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
    }
}