<?php
namespace Core;

/**
 * Clase Request para manejar solicitudes HTTP
 * 
 * Encapsula y proporciona acceso a los datos de la solicitud actual
 */
class Request {
    /**
     * Método HTTP de la solicitud
     * 
     * @var string
     */
    protected $method;
    
    /**
     * URI de la solicitud
     * 
     * @var string
     */
    protected $uri;
    
    /**
     * Parámetros de la URL (query string)
     * 
     * @var array
     */
    protected $queryParams;
    
    /**
     * Datos del cuerpo de la solicitud (POST, PUT, etc.)
     * 
     * @var array
     */
    protected $body;
    
    /**
     * Archivos subidos
     * 
     * @var array
     */
    protected $files;
    
    /**
     * Cabeceras de la solicitud
     * 
     * @var array
     */
    protected $headers;
    
    /**
     * Cookies de la solicitud
     * 
     * @var array
     */
    protected $cookies;
    
    /**
     * Constructor de la clase Request
     */
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->queryParams = $_GET ?? [];
        $this->body = $this->parseBody();
        $this->files = $_FILES ?? [];
        $this->headers = $this->getHeaders();
        $this->cookies = $_COOKIE ?? [];
    }
    
    /**
     * Analiza el cuerpo de la solicitud según el método y tipo de contenido
     * 
     * @return array
     */
    protected function parseBody() {
        $body = [];
        
        // Para métodos con body (POST, PUT, etc.)
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Si es un formulario estándar
            if (isset($_POST) && !empty($_POST)) {
                $body = $_POST;
            } 
            // Si es JSON
            else if ($this->getContentType() === 'application/json') {
                $input = file_get_contents('php://input');
                if (!empty($input)) {
                    $body = json_decode($input, true) ?? [];
                }
            } 
            // Para otros tipos, intentar parsear el input
            else {
                parse_str(file_get_contents('php://input'), $body);
            }
        }
        
        return $body;
    }
    
    /**
     * Obtiene todas las cabeceras HTTP de la solicitud
     * 
     * @return array
     */
    protected function getHeaders() {
        $headers = [];
        
        // Si está disponible la función getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } 
        // Caso contrario, extraer desde $_SERVER
        else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                } else if ($name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                    $headers[$name] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Obtiene el método HTTP de la solicitud
     * 
     * @return string
     */
    public function getMethod() {
        // Verificar si el método real está en un campo oculto (para formularios)
        if ($this->method === 'POST' && isset($this->body['_method'])) {
            return strtoupper($this->body['_method']);
        }
        
        return $this->method;
    }
    
    /**
     * Obtiene la URI de la solicitud
     * 
     * @return string
     */
    public function getUri() {
        // Eliminar query string si existe
        $uri = explode('?', $this->uri)[0];
        
        return $uri;
    }
    
    /**
     * Obtiene los parámetros de la URL (query string)
     * 
     * @return array
     */
    public function getQueryParams() {
        return $this->queryParams;
    }
    
    /**
     * Obtiene un parámetro específico de la URL
     * 
     * @param string $key Nombre del parámetro
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function getQueryParam($key, $default = null) {
        return $this->queryParams[$key] ?? $default;
    }
    
    /**
     * Obtiene todos los datos del cuerpo de la solicitud
     * 
     * @return array
     */
    public function getBody() {
        return $this->body;
    }
    
    /**
     * Obtiene un dato específico del cuerpo de la solicitud
     * 
     * @param string $key Nombre del dato
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function getBodyParam($key, $default = null) {
        return $this->body[$key] ?? $default;
    }
    
    /**
     * Obtiene los archivos subidos en la solicitud
     * 
     * @return array
     */
    public function getFiles() {
        return $this->files;
    }
    
    /**
     * Obtiene un archivo específico subido en la solicitud
     * 
     * @param string $key Nombre del archivo
     * @return array|null
     */
    public function getFile($key) {
        return $this->files[$key] ?? null;
    }
    
    
    /**
     * Obtiene una cabecera específica de la solicitud
     * 
     * @param string $key Nombre de la cabecera
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function getHeader($key, $default = null) {
        // Cabeceras pueden estar en diferentes formatos, intentar varias opciones
        $key = str_replace('-', '', strtolower($key));
        
        foreach ($this->headers as $headerKey => $value) {
            if (str_replace('-', '', strtolower($headerKey)) === $key) {
                return $value;
            }
        }
        
        return $default;
    }
    
    /**
     * Obtiene el tipo de contenido de la solicitud
     * 
     * @return string|null
     */
    public function getContentType() {
        return $this->getHeader('Content-Type');
    }
    
    /**
     * Verifica si la solicitud es AJAX
     * 
     * @return bool
     */
    public function isAjax() {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * Verifica si la solicitud espera una respuesta JSON
     * 
     * @return bool
     */
    public function expectsJson() {
        return $this->isAjax() || strpos($this->getHeader('Accept', ''), '/json') !== false;
    }
    
    /**
     * Establece método de la solicitud (útil para testing)
     * 
     * @param string $method Método HTTP
     * @return $this
     */
    public function setMethod($method) {
        $this->method = strtoupper($method);
        return $this;
    }
    
    /**
     * Establece cuerpo de la solicitud (útil para testing)
     * 
     * @param array $body Datos del cuerpo
     * @return $this
     */
    public function setBody(array $body) {
        $this->body = $body;
        return $this;
    }
}