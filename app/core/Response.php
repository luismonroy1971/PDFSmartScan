<?php

namespace App\Core;

class Response
{
    /**
     * Envía una respuesta JSON
     */
    public static function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Envía una respuesta de texto plano
     */
    public static function text($text, $statusCode = 200)
    {
        header('Content-Type: text/plain');
        http_response_code($statusCode);
        echo $text;
        exit;
    }
    
    /**
     * Envía una respuesta HTML
     */
    public static function html($html, $statusCode = 200)
    {
        header('Content-Type: text/html; charset=UTF-8');
        http_response_code($statusCode);
        echo $html;
        exit;
    }
    
    /**
     * Envía un archivo para descarga
     */
    public static function download($filePath, $filename = null, $contentType = null)
    {
        if (!file_exists($filePath)) {
            self::notFound('Archivo no encontrado');
        }
        
        $filename = $filename ?? basename($filePath);
        $contentType = $contentType ?? mime_content_type($filePath);
        
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Envía una respuesta de redirección
     */
    public static function redirect($url, $statusCode = 302)
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Envía una respuesta de error 404 (No encontrado)
     */
    public static function notFound($message = 'Recurso no encontrado')
    {
        http_response_code(404);
        echo $message;
        exit;
    }
    
    /**
     * Envía una respuesta de error 403 (Prohibido)
     */
    public static function forbidden($message = 'Acceso denegado')
    {
        http_response_code(403);
        echo $message;
        exit;
    }
    
    /**
     * Envía una respuesta de error 401 (No autorizado)
     */
    public static function unauthorized($message = 'No autorizado')
    {
        http_response_code(401);
        echo $message;
        exit;
    }
    
    /**
     * Envía una respuesta de error 500 (Error interno del servidor)
     */
    public static function serverError($message = 'Error interno del servidor')
    {
        http_response_code(500);
        echo $message;
        exit;
    }
    
    /**
     * Envía una respuesta de error 400 (Solicitud incorrecta)
     */
    public static function badRequest($message = 'Solicitud incorrecta')
    {
        http_response_code(400);
        echo $message;
        exit;
    }
}