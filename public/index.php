<?php
/**
 * Punto de entrada principal para la aplicación de extracción de datos de PDFs
 * 
 * Este archivo carga la configuración, inicializa la aplicación
 * y maneja todas las solicitudes entrantes.
 */

// Definir la ruta base de la aplicación
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', APP_PATH . '/Views');
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');

// Cargar Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/storage/logs/error.log');

// Iniciar sesión
session_start();

// Cargar las clases principales
use Core\App;
use Core\Router;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Helpers;

// Cargar configuración de la aplicación
$config = require_once CORE_PATH . '/Config.php';

// Inicializar la aplicación
$app = new App($config);

// Configurar rutas
$router = new Router();

// Rutas de autenticación
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/reset-password', 'AuthController@sendResetLink');
$router->get('/reset-password/verify', 'AuthController@verifyResetToken');
$router->post('/reset-password/update', 'AuthController@updatePassword');
$router->get('/logout', 'AuthController@logout');

// Rutas del dashboard
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// Rutas de PDFs
$router->get('/pdf/upload', 'PdfController@showUpload');
$router->post('/pdf/upload', 'PdfController@upload');
$router->get('/pdf/view/{id}', 'PdfController@view');
$router->get('/pdf/area-selector/{id}', 'PdfController@showAreaSelector');
$router->get('/pdf/delete/{id}', 'PdfController@delete');

// Rutas de API para AJAX
$router->post('/api/save-areas', 'PdfController@saveAreas');
$router->get('/api/get-areas/{id}', 'PdfController@getAreas');
$router->post('/api/process-ocr/{id}', 'OcrController@process');

// Rutas de Excel
$router->get('/excel/configure/{id}', 'ExcelController@showConfigure');
$router->post('/excel/configure', 'ExcelController@configure');
$router->get('/excel/download/{id}', 'ExcelController@download');
$router->get('/excel/download/{id}/{format}', 'ExcelController@download');

// Crear instancia de Request y Response
$request = new Request();
$response = new Response();

// Ejecutar la aplicación
$app->run($router, $request, $response);

/**
 * Función auxiliar para renderizar vistas
 * 
 * @param string $view Ruta a la vista
 * @param array $data Datos para pasar a la vista
 * @return string HTML renderizado
 */
function view($view, $data = []) {
    // Extraer variables para usar en la vista
    extract($data);
    
    // Incluir la vista
    $viewPath = VIEWS_PATH . '/' . $view . '.php';
    
    if (!file_exists($viewPath)) {
        throw new Exception("Vista no encontrada: {$view}");
    }
    
    // Iniciar buffer de salida
    ob_start();
    
    // Incluir la vista
    include $viewPath;
    
    // Obtener el contenido del buffer y limpiarlo
    $content = ob_get_clean();
    
    return $content;
}