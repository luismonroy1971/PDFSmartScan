<?php
/**
 * Punto de entrada principal para la aplicación de extracción de datos de PDFs
 * 
 * Este archivo carga la configuración, inicializa la aplicación
 * y maneja todas las solicitudes entrantes.
 */

// Definir la ruta base de la aplicación
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', APP_PATH . '/Views');
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CONFIG_PATH', BASE_PATH . '/config');
define('ROOT_PATH', BASE_PATH);

// Definir la URL base para enlaces y redirecciones
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host);

// Cargar Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Cargar helpers y utilidades
require_once CORE_PATH . '/helpers.php';
require_once CORE_PATH . '/Utils.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/storage/logs/error.log');

// Cargar las clases principales
use Core\App;
use Core\Router;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Config;

// Cargar archivo de Config
require_once CORE_PATH . '/Config.php';

// Verificar que existen los directorios principales
if (!is_dir(BASE_PATH . '/config')) {
    mkdir(BASE_PATH . '/config', 0755, true);
}

if (!is_dir(BASE_PATH . '/storage/logs')) {
    mkdir(BASE_PATH . '/storage/logs', 0755, true);
}

if (!is_dir(VIEWS_PATH)) {
    mkdir(VIEWS_PATH, 0755, true);
}

if (!is_dir(VIEWS_PATH . '/auth')) {
    mkdir(VIEWS_PATH . '/auth', 0755, true);
}

if (!is_dir(VIEWS_PATH . '/layouts')) {
    mkdir(VIEWS_PATH . '/layouts', 0755, true);
}

if (!is_dir(VIEWS_PATH . '/errors')) {
    mkdir(VIEWS_PATH . '/errors', 0755, true);
}

// Crear vistas predeterminadas si no existen
if (!file_exists(VIEWS_PATH . '/auth/login.php')) {
    file_put_contents(VIEWS_PATH . '/auth/login.php', '<?php use App\Core\Session; ?>\n<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Iniciar Sesión</h4>
                </div>
                <div class="card-body">
                    <?php if (Session::hasFlash(\'error\')): ?>
                        <div class="alert alert-danger">
                            <?= Session::getFlash(\'error\') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (Session::hasFlash(\'success\')): ?>
                        <div class="alert alert-success">
                            <?= Session::getFlash(\'success\') ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/login" method="POST">
                        <div class="form-group mb-3">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="/reset-password">¿Olvidaste tu contraseña?</a>
                        <p class="mt-2">¿No tienes cuenta? <a href="/register">Regístrate</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>');
}

if (!file_exists(VIEWS_PATH . '/layouts/app.php')) {
    file_put_contents(VIEWS_PATH . '/layouts/app.php', '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= Session::get(\'csrf_token\') ?>">
    <title><?= $title ?? \'PDFSmartScan\' ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= $style ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                PDFSmartScan
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (Session::get(\'user_id\')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/pdf/upload">
                                <i class="fas fa-file-upload"></i> Subir PDF
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/">
                                <i class="fas fa-home"></i> Inicio
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <?php if (Session::get(\'user_id\')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> 
                                <?= htmlspecialchars(Session::get(\'user_name\')) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="/logout">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/register">
                                <i class="fas fa-user-plus"></i> Registro
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="pb-5">
        <?= $content ?? \'\' ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="text-center">
                <p class="small mb-0">
                    &copy; <?= date(\'Y\') ?> PDFSmartScan. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (algunos componentes podrían requerirlo) -->
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/main.js"></script>
    
    <!-- Additional Scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?= $inlineScripts ?>
        </script>
    <?php endif; ?>
</body>
</html>');
}

if (!file_exists(VIEWS_PATH . '/errors/404.php')) {
    file_put_contents(VIEWS_PATH . '/errors/404.php', '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        p {
            color: #333;
            margin-bottom: 1.5rem;
        }
        a {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error 404: Página no encontrada</h1>
        <p>Lo sentimos, la página que estás buscando no existe o ha sido movida.</p>
        <a href="/">Volver al inicio</a>
    </div>
</body>
</html>');
}

if (!file_exists(VIEWS_PATH . '/errors/500.php')) {
    file_put_contents(VIEWS_PATH . '/errors/500.php', '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        p {
            color: #333;
            margin-bottom: 1.5rem;
        }
        a {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error 500: Error del servidor</h1>
        <p>Lo sentimos, ha ocurrido un error interno. Por favor, inténtelo de nuevo más tarde.</p>
        <a href="/">Volver al inicio</a>
    </div>
</body>
</html>');
}

// Sobrescribir la función view() para asegurar que use layouts
if (!function_exists('Core\view')) {
    file_put_contents(CORE_PATH . '/helpers.php', '<?php
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
    $viewPath = VIEWS_PATH . \'/\' . $view . \'.php\';
    
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
    
    // Si se especifica un layout, usarlo, de lo contrario devolver el contenido
    if (isset($data[\'layout\']) && $data[\'layout\'] === false) {
        return $content;
    } else {
        // Iniciar buffer para el layout
        ob_start();
        
        // Definir la variable $content para que esté disponible en el layout
        $layoutPath = VIEWS_PATH . \'/layouts/\' . ($data[\'layout\'] ?? \'app\') . \'.php\';
        
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            echo $content;
        }
        
        // Obtener el contenido completo (layout + vista)
        $fullContent = ob_get_clean();
        
        return $fullContent;
    }
}

/**
 * Proxy para Session::hasFlash
 */
function has_flash($key) {
    return Session::hasFlash($key);
}

/**
 * Proxy para Session::getFlash
 */
function flash($key, $default = null) {
    return Session::getFlash($key, $default);
}

/**
 * Proxy para Session::get
 */
function session($key, $default = null) {
    return Session::get($key, $default);
}

/**
 * Redirecciona a una URL específica
 * 
 * @param string $url URL a la que redireccionar
 * @return void
 */
function redirect($url) {
    header(\'Location: \' . $url);
    exit;
}');
}

// Crear archivo de configuración app.php si no existe
if (!file_exists(BASE_PATH . '/config/app.php')) {
    file_put_contents(BASE_PATH . '/config/app.php', '<?php
return [
    "name" => "PDFSmartScan",
    "debug" => ' . ($_ENV["APP_DEBUG"] === "true" ? 'true' : 'false') . ',
    "log_path" => BASE_PATH . "/storage/logs/error.log"
];');
}

// Crear archivo de configuración database.php si no existe
if (!file_exists(BASE_PATH . '/config/database.php')) {
    file_put_contents(BASE_PATH . '/config/database.php', '<?php
return [
    "enabled" => true,
    "driver" => "mysql",
    "host" => "' . ($_ENV["DB_HOST"] ?? "localhost") . '",
    "port" => "' . ($_ENV["DB_PORT"] ?? "3306") . '",
    "database" => "' . ($_ENV["DB_DATABASE"] ?? "pdf_extract") . '",
    "username" => "' . ($_ENV["DB_USERNAME"] ?? "root") . '",
    "password" => "' . ($_ENV["DB_PASSWORD"] ?? "") . '",
    "charset" => "utf8mb4",
    "collation" => "utf8mb4_unicode_ci"
];');
}

// Crear archivo de configuración session.php si no existe
if (!file_exists(BASE_PATH . '/config/session.php')) {
    file_put_contents(BASE_PATH . '/config/session.php', '<?php
return [
    "enabled" => true,
    "lifetime" => 3600,
    "path" => "/",
    "domain" => "",
    "secure" => false,
    "httponly" => true,
    "samesite" => "Lax"
];');
}

// Cargar configuraciones
Config::load(BASE_PATH . '/config/app.php', 'app');
Config::load(BASE_PATH . '/config/database.php', 'database');
Config::load(BASE_PATH . '/config/session.php', 'session');

// Inicializar la aplicación con todas las configuraciones
$app = new App(Config::all());

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

$router->get('/test', 'TestController@index');

// Crear instancia de Request y Response
$request = new Request();
$response = new Response();

// Ejecutar la aplicación
$app->run($router, $request, $response);