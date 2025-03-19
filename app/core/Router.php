<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $notFoundCallback;
    
    /**
     * Añade una ruta GET
     */
    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
        return $this;
    }
    
    /**
     * Añade una ruta POST
     */
    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
        return $this;
    }
    
    /**
     * Añade una ruta PUT
     */
    public function put($path, $callback)
    {
        $this->addRoute('PUT', $path, $callback);
        return $this;
    }
    
    /**
     * Añade una ruta DELETE
     */
    public function delete($path, $callback)
    {
        $this->addRoute('DELETE', $path, $callback);
        return $this;
    }
    
    /**
     * Añade una ruta para cualquier método HTTP
     */
    public function any($path, $callback)
    {
        $this->addRoute('GET|POST|PUT|DELETE', $path, $callback);
        return $this;
    }
    
    /**
     * Añade una ruta con métodos HTTP específicos
     */
    public function match($methods, $path, $callback)
    {
        $this->addRoute(is_array($methods) ? implode('|', $methods) : $methods, $path, $callback);
        return $this;
    }
    
    /**
     * Establece el callback para rutas no encontradas
     */
    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
        return $this;
    }
    
    /**
     * Añade una ruta al enrutador
     */
    protected function addRoute($method, $path, $callback)
    {
        // Convertir el path a una expresión regular
        $pattern = $this->pathToRegex($path);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }
    
    /**
     * Convierte un path en una expresión regular
     */
    protected function pathToRegex($path)
    {
        // Escapar caracteres especiales
        $path = preg_quote($path, '/');
        
        // Convertir parámetros {param} a grupos de captura
        $path = preg_replace('/\\\{([a-zA-Z0-9_]+)\\\}/', '(?P<$1>[^/]+)', $path);
        
        // Añadir delimitadores y anclas
        return '/^' . $path . '$/';
    }
    
    /**
     * Despacha la solicitud actual
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Soporte para métodos PUT y DELETE a través de POST con _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        foreach ($this->routes as $route) {
            // Verificar si el método coincide
            if (!preg_match('/^(' . $route['method'] . ')$/', $method)) {
                continue;
            }
            
            // Verificar si la ruta coincide
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }
            
            // Filtrar parámetros capturados
            $params = array_filter($matches, function($key) {
                return !is_numeric($key);
            }, ARRAY_FILTER_USE_KEY);
            
            // Ejecutar el callback
            return $this->executeCallback($route['callback'], $params);
        }
        
        // Ruta no encontrada
        if ($this->notFoundCallback) {
            return call_user_func($this->notFoundCallback);
        }
        
        // Respuesta predeterminada para rutas no encontradas
        Response::notFound('Página no encontrada');
    }
    
    /**
     * Obtiene la URI actual sin query string
     */
    protected function getUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Eliminar query string si existe
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Eliminar la ruta base si existe
        $basePath = Config::get('app.base_path', '');
        if ($basePath && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Normalizar la URI
        $uri = '/' . trim($uri, '/');
        
        return $uri;
    }
    
    /**
     * Ejecuta el callback de una ruta
     */
    protected function executeCallback($callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }
        
        // Si el callback es un string en formato 'Controller@method'
        if (is_string($callback) && strpos($callback, '@') !== false) {
            list($controller, $method) = explode('@', $callback);
            
            // Añadir namespace si no está presente
            if (strpos($controller, '\\') === false) {
                $controller = 'App\\Controllers\\' . $controller;
            }
            
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $params);
                }
            }
        }
        
        // Si el callback es un array [Controller::class, 'method']
        if (is_array($callback) && count($callback) === 2) {
            list($controller, $method) = $callback;
            
            if (is_string($controller)) {
                // Añadir namespace si no está presente
                if (strpos($controller, '\\') === false) {
                    $controller = 'App\\Controllers\\' . $controller;
                }
                
                if (class_exists($controller)) {
                    $controllerInstance = new $controller();
                    
                    if (method_exists($controllerInstance, $method)) {
                        return call_user_func_array([$controllerInstance, $method], $params);
                    }
                }
            }
        }
        
        // Si llegamos aquí, el callback no es válido
        Response::serverError('Error en la configuración de la ruta');
    }
}