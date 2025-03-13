<?php
// Definir constantes básicas
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require_once BASE_PATH . '/vendor/autoload.php';

// Intentar cargar directamente el controlador
$controllerPath = APP_PATH . '/Controllers/AuthController.php';
echo "Verificando si existe el controlador: " . $controllerPath . "<br>";
echo "El archivo " . (file_exists($controllerPath) ? "SÍ" : "NO") . " existe.<br><br>";

// Intentar instanciar el controlador
echo "Intentando instanciar el controlador...<br>";
try {
    // Verificar si existe la clase
    echo "Clase App\\Controllers\\AuthController " . 
         (class_exists('App\\Controllers\\AuthController') ? "SÍ" : "NO") . 
         " está definida.<br>";
         
    // Verificar método
    if (class_exists('App\\Controllers\\AuthController')) {
        $reflection = new ReflectionClass('App\\Controllers\\AuthController');
        echo "Método showLogin " . 
             ($reflection->hasMethod('showLogin') ? "SÍ" : "NO") . 
             " existe.<br>";
    }
    
    echo "<hr>Información de depuración adicional:<br>";
    echo "Directorio actual: " . getcwd() . "<br>";
    echo "BASE_PATH: " . BASE_PATH . "<br>";
    echo "APP_PATH: " . APP_PATH . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}