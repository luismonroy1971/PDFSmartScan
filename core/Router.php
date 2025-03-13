<?php
namespace Core;

use function Core\getParameterClassName;

class Router {
    /**
     * Rutas registradas
     * 
     * @var array
     */
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];
    
    /**
     * Patrones para parámetros dinámicos
     * 
     * @var array
     */
    protected $patterns = [
        '{id}' => '([0-9]+)',
        '{slug}' => '([a-z0-9\-]+)',
        '{format}' => '(xlsx|csv)',
        '{any}' => '(.*)'
    ];
    
    /**
     * Registra una ruta GET
     * 
     * @param string $uri URI de la ruta
     * @param string|callable $handler Controlador@método o función anónima
     * @return void
     */
    public function get($uri, $handler) {
        $this->addRoute('GET', $uri, $handler);
    }
    
    /**
     * Registra una ruta POST
     * 
     * @param string $uri URI de la ruta
     * @param string|callable $handler Controlador@método o función anónima
     * @return void
     */
    public function post($uri, $handler) {
        $this->addRoute('POST', $uri, $handler);
    }
    
    /**
     * Añade una ruta al registro de rutas
     * 
     * @param string $method Método HTTP
     * @param string $uri URI de la ruta
     * @param string|callable $handler Controlador@método o función anónima
     * @return void
     */
    protected function addRoute($method, $uri, $handler) {
        // Reemplazar parámetros dinámicos con expresiones regulares
        $pattern = $this->compilePattern($uri);
        
        // Registrar la ruta
        $this->routes[$method][$pattern] = [
            'uri' => $uri,
            'handler' => $handler,
            'params' => $this->getParamNames($uri)
        ];
    }
    
    /**
     * Compila la URI en un patrón de expresión regular
     * 
     * @param string $uri URI de la ruta
     * @return string Patrón de expresión regular
     */
    protected function compilePattern($uri) {
        // Escapar caracteres especiales y reemplazar parámetros con expresiones regulares
        $pattern = preg_quote($uri, '/');
        
        // Reemplazar parámetros con patrones
        foreach ($this->patterns as $param => $regex) {
            $pattern = str_replace(preg_quote($param), $regex, $pattern);
        }
        
        return '/^' . str_replace('/', '\/', $pattern) . '$/';
    }
    
    /**
     * Obtiene los nombres de los parámetros en la URI
     * 
     * @param string $uri URI de la ruta
     * @return array Nombres de los parámetros
     */
    protected function getParamNames($uri) {
        preg_match_all('/{([^}]+)}/', $uri, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * Resuelve la ruta para la URI actual
     * 
     * @param string $uri URI actual
     * @param string $method Método HTTP actual
     * @return array|false Información de la ruta o false si no se encuentra
     */
    public function resolve($uri, $method) {
        // Normalizar URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        
        // Si la URI está vacía, establecer a "/"
        if ($uri === '') {
            $uri = '/';
        }
        
        // Buscar ruta coincidente
        $routes = $this->routes[$method] ?? [];
        
        foreach ($routes as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Eliminar la coincidencia completa
                array_shift($matches);
                
                // Asignar parámetros capturados a sus nombres
                $params = [];
                foreach ($route['params'] as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }
                
                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Maneja la solicitud y resuelve la ruta
     * 
     * @param Request $request Objeto de solicitud
     * @return mixed Resultado del controlador
     */
    public function dispatch(Request $request) {
        $uri = $request->getUri();
        $method = $request->getMethod();
        
        $route = $this->resolve($uri, $method);
        
        if ($route === false) {
            // Ruta no encontrada
            http_response_code(404);
            return view('errors/404');
        }
        
        $handler = $route['handler'];
        $params = $route['params'];
        
        // Si el handler es una función anónima
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        // Si el handler es una cadena "Controlador@método"
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            
            // Añadir namespace
            $controller = 'App\\Controllers\\' . $controller;
            
            if (!class_exists($controller)) {
                throw new \Exception("Controlador no encontrado: {$controller}");
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Método no encontrado: {$controller}@{$method}");
            }
            
            // Pasar el objeto Request como primer parámetro si el método lo requiere
            $reflection = new \ReflectionMethod($controllerInstance, $method);
            $parameters = $reflection->getParameters();
            
            if (!empty($parameters)) {
                $className = getParameterClassName($parameters[0]);
                if ($className === 'Core\\Request') {
                    array_unshift($params, $request);
                }
            }
            
            return call_user_func_array([$controllerInstance, $method], $params);
        }
        
        throw new \Exception("Handler no válido");
    }
}