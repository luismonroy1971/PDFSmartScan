<?php
namespace Core;

/**
 * Clase principal de la aplicación
 * 
 * Esta clase inicializa la aplicación y coordina los componentes principales
 */
class App {
    /**
     * Configuración de la aplicación
     * 
     * @var array
     */
    protected $config;
    
    /**
     * Instancia única de la aplicación (patrón Singleton)
     * 
     * @var App
     */
    protected static $instance;
    
    /**
     * Constructor de la clase App
     * 
     * @param array $config Configuración de la aplicación
     */
    public function __construct(array $config = []) {
        $this->config = $config;
        self::$instance = $this;
        
        // Inicializar componentes básicos
        $this->initializeComponents();
        
        // Configurar manejo de errores
        $this->setupErrorHandling();
    }
    
    /**
     * Obtiene la instancia única de la aplicación
     * 
     * @return App
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Obtiene un valor de configuración
     * 
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed
     */
    public function getConfig($key, $default = null) {
        $keys = explode('.', $key);
        $config = $this->config;
        
        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            
            $config = $config[$segment];
        }
        
        return $config;
    }
    
    /**
     * Inicializa los componentes básicos de la aplicación
     */
    protected function initializeComponents() {
        // Inicializar base de datos si está configurada
        if ($this->getConfig('database.enabled', true)) {
            Database::getInstance();
        }
        
        // Inicializar gestión de sesiones
        if ($this->getConfig('session.enabled', true)) {
            Session::start([
                'lifetime' => $this->getConfig('session.lifetime', 3600),
                'path' => $this->getConfig('session.path', '/'),
                'domain' => $this->getConfig('session.domain', ''),
                'secure' => $this->getConfig('session.secure', false),
                'httponly' => $this->getConfig('session.httponly', true),
                'samesite' => $this->getConfig('session.samesite', 'Lax')
            ]);
        }
    }
    
    /**
     * Configura el manejo de errores personalizado
     */
    protected function setupErrorHandling() {
        $isDebug = $this->getConfig('app.debug', false);
        
        // Configurar nivel de reporte de errores
        error_reporting($isDebug ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Configurar mostrar errores
        ini_set('display_errors', $isDebug ? 1 : 0);
        
        // Configurar manejador de excepciones no capturadas
        set_exception_handler(function (\Throwable $exception) use ($isDebug) {
            $this->handleException($exception, $isDebug);
        });
        
        // Configurar manejador de errores
        set_error_handler(function ($level, $message, $file, $line) use ($isDebug) {
            if (error_reporting() & $level) {
                $this->handleError($level, $message, $file, $line, $isDebug);
            }
        });
    }
    
    /**
     * Maneja excepciones no capturadas
     * 
     * @param \Throwable $exception Excepción a manejar
     * @param bool $isDebug Modo debug activo
     */
    protected function handleException(\Throwable $exception, $isDebug) {
        // Registrar el error
        $this->logError($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Mostrar página de error
        if ($isDebug) {
            // En modo debug, mostrar detalle del error
            echo '<h1>Error: ' . get_class($exception) . '</h1>';
            echo '<p>' . $exception->getMessage() . '</p>';
            echo '<p>En archivo: ' . $exception->getFile() . ':' . $exception->getLine() . '</p>';
            echo '<h2>Stack Trace:</h2>';
            echo '<pre>' . $exception->getTraceAsString() . '</pre>';
        } else {
            // En producción, mostrar página de error genérica
            http_response_code(500);
            if (file_exists(VIEWS_PATH . '/errors/500.php')) {
                include VIEWS_PATH . '/errors/500.php';
            } else {
                echo '<h1>Error del servidor</h1>';
                echo '<p>Lo sentimos, ha ocurrido un error interno. Por favor, inténtelo de nuevo más tarde.</p>';
            }
        }
        
        exit(1);
    }
    
    /**
     * Maneja errores de PHP
     * 
     * @param int $level Nivel de error
     * @param string $message Mensaje de error
     * @param string $file Archivo donde ocurrió el error
     * @param int $line Línea donde ocurrió el error
     * @param bool $isDebug Modo debug activo
     */
    protected function handleError($level, $message, $file, $line, $isDebug) {
        // Registrar el error
        $this->logError($message, [
            'level' => $level,
            'file' => $file,
            'line' => $line
        ]);
        
        // Para errores fatales, mostrar página de error
        if ($level === E_ERROR || $level === E_CORE_ERROR || $level === E_COMPILE_ERROR || $level === E_USER_ERROR) {
            if ($isDebug) {
                echo '<h1>Error Fatal</h1>';
                echo '<p>' . $message . '</p>';
                echo '<p>En archivo: ' . $file . ':' . $line . '</p>';
            } else {
                http_response_code(500);
                if (file_exists(VIEWS_PATH . '/errors/500.php')) {
                    include VIEWS_PATH . '/errors/500.php';
                } else {
                    echo '<h1>Error del servidor</h1>';
                    echo '<p>Lo sentimos, ha ocurrido un error interno. Por favor, inténtelo de nuevo más tarde.</p>';
                }
            }
            
            exit(1);
        }
    }
    
    /**
     * Registra un mensaje de error en el log
     * 
     * @param string $message Mensaje de error
     * @param array $context Contexto adicional
     */
    protected function logError($message, array $context = []) {
        $logFile = $this->getConfig('app.log_path', BASE_PATH . '/storage/logs/error.log');
        $logDir = dirname($logFile);
        
        // Crear directorio de logs si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Formatear mensaje de log
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ';
        $logMessage .= 'Error: ' . $message . ' ';
        $logMessage .= json_encode($context, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        // Escribir en archivo de log
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Ejecuta la aplicación con el router y request proporcionados
     * 
     * @param Router $router Router de la aplicación
     * @param Request $request Objeto de solicitud
     * @param Response $response Objeto de respuesta
     */
    public function run(Router $router, Request $request, Response $response) {
        try {
            // Obtener el resultado de la ruta
            $result = $router->dispatch($request);
            
            // Si es una respuesta directa (string), enviarla
            if (is_string($result)) {
                $response->setContent($result);
            } 
            // Si es un array, convertirlo a JSON
            else if (is_array($result)) {
                $response->json($result);
            }
            
            // Enviar la respuesta
            $response->send();
            
        } catch (\Exception $e) {
            // Manejar excepciones durante la ejecución
            $this->handleException($e, $this->getConfig('app.debug', false));
        }
    }
}