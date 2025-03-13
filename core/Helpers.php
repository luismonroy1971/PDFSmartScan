<?php
namespace Core;

/**
 * Renderiza una vista
 * @param string $view Ruta de la vista a renderizar
 * @param array $data Datos para pasar a la vista
 * @return string El HTML renderizado
 */
function view($view, $data = []) {
    // Extraer las variables para que estén disponibles en la vista
    extract($data);
    
    // Definir la ruta completa a la vista
    $viewPath = VIEWS_PATH . '/' . $view . '.php';
    
    // Verificar si la vista existe
    if (!file_exists($viewPath)) {
        throw new \Exception("Vista no encontrada: {$view}");
    }
    
    // Iniciar buffer de salida
    ob_start();
    
    // Incluir la vista
    include $viewPath;
    
    // Obtener el contenido y limpiar el buffer
    $content = ob_get_clean();
    
    return $content;
}

/**
 * Proxy para Session::hasFlash
 */
function has_flash($key) {
    return \Core\Session::hasFlash($key);
}

/**
 * Proxy para Session::getFlash
 */
function flash($key, $default = null) {
    return \Core\Session::getFlash($key);
}

/**
 * Proxy para Session::get
 */
function session($key, $default = null) {
    return \Core\Session::get($key, $default);
}