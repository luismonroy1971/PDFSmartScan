<?php
namespace Core;

/**
 * Clase Response para manejar respuestas HTTP
 * 
 * Encapsula la generación y envío de respuestas HTTP
 */
class Response {
    /**
     * Código de estado HTTP
     * 
     * @var int
     */
    protected $statusCode = 200;
    
    /**
     * Cabeceras de la respuesta
     * 
     * @var array
     */
    protected $headers = [];
    
    /**
     * Contenido de la respuesta
     * 
     * @var string
     */
    protected $content = '';
    
    /**
     * Cookies a enviar con la respuesta
     * 
     * @var array
     */
    protected $cookies = [];
    
    /**
     * Constructor de la clase Response
     * 
     * @param string $content Contenido inicial
     * @param int $statusCode Código de estado inicial
     * @param array $headers Cabeceras iniciales
     */
    public function __construct($content = '', $statusCode = 200, array $headers = []) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    /**
     * Establece el código de estado HTTP
     * 
     * @param int $statusCode Código de estado
     * @return $this
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Obtiene el código de estado HTTP actual
     * 
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    /**
     * Establece una cabecera HTTP
     * 
     * @param string $name Nombre de la cabecera
     * @param string $value Valor de la cabecera
     * @return $this
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Establece múltiples cabeceras HTTP
     * 
     * @param array $headers Arreglo de cabeceras [nombre => valor]
     * @return $this
     */
    public function setHeaders(array $headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        
        return $this;
    }
    
    /**
     * Establece una cookie para la respuesta
     * 
     * @param string $name Nombre de la cookie
     * @param string $value Valor de la cookie
     * @param int $expire Tiempo de expiración
     * @param string $path Ruta de la cookie
     * @param string $domain Dominio de la cookie
     * @param bool $secure Cookie segura (HTTPS)
     * @param bool $httpOnly Cookie HTTP only
     * @param string $sameSite Política SameSite (Lax, Strict, None)
     * @return $this
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true, $sameSite = 'Lax') {
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite
        ];
        
        return $this;
    }
    
    /**
     * Establece el contenido de la respuesta
     * 
     * @param string $content Contenido de la respuesta
     * @return $this
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Obtiene el contenido actual de la respuesta
     * 
     * @return string
     */
    public function getContent() {
        return $this->content;
    }
    
    /**
     * Envía una redirección al navegador
     * 
     * @param string $url URL de destino
     * @param int $statusCode Código de estado (default 302 Found)
     * @return void
     */
    public static function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Crea una respuesta JSON
     * 
     * @param mixed $data Datos a convertir a JSON
     * @param int $statusCode Código de estado
     * @param array $headers Cabeceras adicionales
     * @return $this
     */
    public function json($data, $statusCode = 200, array $headers = []) {
        $this->setHeader('Content-Type', 'application/json');
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
        $this->setContent(json_encode($data));
        
        return $this;
    }
    
    /**
     * Envía la respuesta al cliente
     * 
     * @return void
     */
    public function send() {
        // Enviar código de estado
        http_response_code($this->statusCode);
        
        // Enviar cabeceras
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        // Enviar cookies
        foreach ($this->cookies as $name => $params) {
            setcookie(
                $name,
                $params['value'],
                [
                    'expires' => $params['expire'],
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httpOnly'],
                    'samesite' => $params['sameSite']
                ]
            );
        }
        
        // Enviar contenido
        echo $this->content;
    }
}