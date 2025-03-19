<?php

namespace App\Services;

use App\Core\Config;
use Exception;

class OcrService
{
    protected $tesseractPath;
    protected $languages;
    protected $tempDir;
    protected $ghostscriptPath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tesseractPath = Config::get('ocr.tesseract_path', '/usr/bin/tesseract');
        $this->ghostscriptPath = Config::get('ocr.ghostscript_path', '/usr/bin/gs');
        $this->languages = Config::get('ocr.languages', 'eng,spa');
        $this->tempDir = Config::get('app.temp_dir', sys_get_temp_dir());
        
        // Asegurar que el directorio temporal existe
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Extrae texto de una imagen usando OCR
     * 
     * @param string $imagePath Ruta a la imagen
     * @param array $options Opciones adicionales para Tesseract
     * @return string Texto extraído
     */
    public function extractTextFromImage($imagePath, $options = [])
    {
        if (!file_exists($imagePath)) {
            throw new Exception("Imagen no encontrada: {$imagePath}");
        }
        
        // Generar nombre de archivo temporal para la salida
        $outputBase = $this->tempDir . '/' . uniqid('ocr_');
        $outputFile = $outputBase . '.txt';
        
        // Construir comando base
        $command = escapeshellcmd($this->tesseractPath);
        $command .= ' ' . escapeshellarg($imagePath);
        $command .= ' ' . escapeshellarg($outputBase);
        
        // Añadir idiomas
        $command .= ' -l ' . escapeshellarg($this->languages);
        
        // Añadir opciones adicionales
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                if (is_numeric($key)) {
                    // Opción sin valor
                    $command .= ' ' . escapeshellarg($value);
                } else {
                    // Opción con valor
                    $command .= ' ' . escapeshellarg($key) . ' ' . escapeshellarg($value);
                }
            }
        }
        
        // Ejecutar comando
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        // Verificar si hubo error
        if ($returnVar !== 0) {
            throw new Exception("Error al ejecutar Tesseract: " . implode("\n", $output));
        }
        
        // Leer resultado
        if (!file_exists($outputFile)) {
            throw new Exception("Archivo de salida no generado: {$outputFile}");
        }
        
        $text = file_get_contents($outputFile);
        
        // Limpiar archivo temporal
        @unlink($outputFile);
        
        return trim($text);
    }
    
    /**
     * Extrae texto de un área específica de una página PDF
     * 
     * @param string $pdfPath Ruta al archivo PDF
     * @param int $pageNumber Número de página (comenzando desde 1)
     * @param array $area Coordenadas del área [x, y, width, height]
     * @param array $options Opciones adicionales para Tesseract
     * @return string Texto extraído
     */
    public function extractTextFromPdfArea($pdfPath, $pageNumber, $area, $options = [])
    {
        if (!file_exists($pdfPath)) {
            throw new Exception("PDF no encontrado: {$pdfPath}");
        }
        
        // Extraer la página como imagen
        $imagePath = $this->convertPdfPageToImage($pdfPath, $pageNumber);
        
        // Recortar el área específica
        $croppedImagePath = $this->cropImage($imagePath, $area);
        
        // Extraer texto del área recortada
        $text = $this->extractTextFromImage($croppedImagePath, $options);
        
        // Limpiar archivos temporales
        @unlink($imagePath);
        @unlink($croppedImagePath);
        
        return $text;
    }
    
    /**
     * Convierte una página de PDF a imagen
     * 
     * @param string $pdfPath Ruta al archivo PDF
     * @param int $pageNumber Número de página (comenzando desde 1)
     * @param int $dpi Resolución de la imagen (por defecto 300)
     * @return string Ruta a la imagen generada
     */
    public function convertPdfPageToImage($pdfPath, $pageNumber, $dpi = 300)
    {
        // Generar nombre de archivo temporal para la salida
        $outputImagePath = $this->tempDir . '/' . uniqid('pdf_page_') . '.png';
        
        // Construir comando de Ghostscript
        $command = escapeshellcmd($this->ghostscriptPath);
        $command .= ' -dQUIET -dSAFER -dBATCH -dNOPAUSE';
        $command .= ' -sDEVICE=png16m -dTextAlphaBits=4 -dGraphicsAlphaBits=4';
        $command .= ' -r' . escapeshellarg($dpi);
        $command .= ' -dFirstPage=' . escapeshellarg($pageNumber);
        $command .= ' -dLastPage=' . escapeshellarg($pageNumber);
        $command .= ' -sOutputFile=' . escapeshellarg($outputImagePath);
        $command .= ' ' . escapeshellarg($pdfPath);
        
        // Ejecutar comando
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        // Verificar si hubo error
        if ($returnVar !== 0) {
            throw new Exception("Error al convertir PDF a imagen: " . implode("\n", $output));
        }
        
        // Verificar si se generó la imagen
        if (!file_exists($outputImagePath)) {
            throw new Exception("Imagen no generada: {$outputImagePath}");
        }
        
        return $outputImagePath;
    }
    
    /**
     * Recorta un área específica de una imagen
     * 
     * @param string $imagePath Ruta a la imagen
     * @param array $area Coordenadas del área [x, y, width, height]
     * @return string Ruta a la imagen recortada
     */
    public function cropImage($imagePath, $area)
    {
        // Validar parámetros
        if (!isset($area['x']) || !isset($area['y']) || !isset($area['width']) || !isset($area['height'])) {
            throw new Exception("Área de recorte inválida. Se requieren las claves: x, y, width, height");
        }
        
        // Cargar imagen
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            throw new Exception("No se pudo obtener información de la imagen: {$imagePath}");
        }
        
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                throw new Exception("Formato de imagen no soportado: {$mimeType}");
        }
        
        if (!$sourceImage) {
            throw new Exception("No se pudo cargar la imagen: {$imagePath}");
        }
        
        // Crear imagen recortada
        $croppedImage = imagecreatetruecolor($area['width'], $area['height']);
        
        // Preservar transparencia si es PNG
        if ($mimeType === 'image/png') {
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent = imagecolorallocatealpha($croppedImage, 255, 255, 255, 127);
            imagefilledrectangle($croppedImage, 0, 0, $area['width'], $area['height'], $transparent);
        }
        
        // Recortar imagen
        imagecopy(
            $croppedImage,
            $sourceImage,
            0,
            0,
            $area['x'],
            $area['y'],
            $area['width'],
            $area['height']
        );
        
        // Generar nombre de archivo temporal para la salida
        $outputImagePath = $this->tempDir . '/' . uniqid('cropped_') . '.png';
        
        // Guardar imagen recortada
        imagepng($croppedImage, $outputImagePath);
        
        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($croppedImage);
        
        return $outputImagePath;
    }
    
    /**
     * Preprocesa una imagen para mejorar el OCR
     * 
     * @param string $imagePath Ruta a la imagen
     * @param array $options Opciones de preprocesamiento
     * @return string Ruta a la imagen procesada
     */
    public function preprocessImage($imagePath, $options = [])
    {
        // Opciones por defecto
        $defaultOptions = [
            'grayscale' => true,
            'contrast' => 0,
            'brightness' => 0,
            'threshold' => false,
            'denoise' => false,
            'deskew' => false
        ];
        
        $options = array_