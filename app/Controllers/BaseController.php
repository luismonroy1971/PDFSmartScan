<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Response;

abstract class BaseController
{
    /**
     * Renderiza una vista con datos
     */
    protected function view($view, $data = [])
    {
        // Extraer datos para que estén disponibles en la vista
        extract($data);
        
        // Obtener mensajes flash para mostrar en la vista
        $success = Session::getFlash('success');
        $error = Session::getFlash('error');
        $errors = Session::getFlash('errors', []);
        $old = Session::getFlash('old', []);
        
        // Determinar la ruta del archivo de vista
        $viewPath = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("Vista no encontrada: $viewPath");
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir la vista
        include $viewPath;
        
        // Obtener contenido del buffer
        $content = ob_get_clean();
        
        // Verificar si hay una plantilla principal
        $layoutPath = VIEWS_PATH . '/layouts/main.php';
        
        if (file_exists($layoutPath)) {
            // Incluir la plantilla principal con el contenido
            include $layoutPath;
        } else {
            // Si no hay plantilla, mostrar directamente el contenido
            echo $content;
        }
    }
    
    /**
     * Redirige a una URL
     */
    protected function redirect($url)
    {
        Response::redirect($url);
    }
    
    /**
     * Devuelve una respuesta JSON
     */
    protected function json($data, $statusCode = 200)
    {
        return Response::json($data, $statusCode);
    }
    
    /**
     * Verifica si la solicitud es AJAX
     */
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtiene un valor de la solicitud (GET o POST)
     */
    protected function input($key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        return $default;
    }
    
    /**
     * Obtiene todos los datos de la solicitud
     */
    protected function all()
    {
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Verifica si la solicitud es POST
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Verifica si la solicitud es GET
     */
    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}