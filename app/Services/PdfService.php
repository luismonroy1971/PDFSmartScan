<?php

namespace App\Services;

use App\Core\Config;

class PdfService
{
    protected $ghostscriptPath;
    protected $tempDir;
    protected $resolution;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ghostscriptPath = Config::get('pdf.ghostscript_path', 'gswin64c.exe');
        $this->tempDir = Config::get('app.temp_dir', STORAGE_PATH . '/temp');
        $this->resolution = Config::get('pdf.resolution', 300);
        
        // Crear directorio temporal si no existe
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Obtiene información del documento PDF
     */
    public function getDocumentInfo($pdfPath)
    {
        if (!file_exists($pdfPath)) {
            throw new \Exception("Archivo PDF no encontrado: $pdfPath");
        }
        
        // Utilizar la extensión TCPDF para obtener información del PDF
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);
        
        $info = [
            'pageCount' => $pageCount,
            'pages' => []
        ];
        
        // Obtener información de cada página
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            
            $info['pages'][$i] = [
                'width' => $size['width'],
                'height' => $size['height'],
                'orientation' => ($size['width'] > $size['height']) ? 'landscape' : 'portrait'
            ];
        }
        
        return $info;
    }
    
    /**
     * Extrae una imagen de un área específica del PDF
     */
    public function extractAreaImage($pdfPath, $coordinates)
    {
        if (!file_exists($pdfPath)) {
            throw new \Exception("Archivo PDF no encontrado: $pdfPath");
        }
        
        // Extraer coordenadas
        $x = $coordinates['x'];
        $y = $coordinates['y'];
        $width = $coordinates['width'];
        $height = $coordinates['height'];
        $page = $coordinates['page'];
        
        // Generar nombre único para la imagen de la página completa
        $pageImagePath = $this->tempDir . '/' . uniqid('page_') . '.png';
        
        // Convertir la página del PDF a imagen usando Ghostscript
        $command = escapeshellcmd($this->ghostscriptPath) . 
                  ' -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m' .
                  ' -r' . $this->resolution . 
                  ' -dFirstPage=' . $page . ' -dLastPage=' . $page .
                  ' -sOutputFile=' . escapeshellarg($pageImagePath) .
                  ' ' . escapeshellarg($pdfPath);
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0 || !file_exists($pageImagePath)) {
            throw new \Exception("Error al convertir página PDF a imagen");
        }
        
        // Generar nombre para la imagen recortada
        $croppedImagePath = $this->tempDir . '/' . uniqid('area_') . '.png';
        
        // Cargar la imagen de la página
        $pageImage = imagecreatefrompng($pageImagePath);
        
        if (!$pageImage) {
            @unlink($pageImagePath);
            throw new \Exception("Error al cargar imagen de página");
        }
        
        // Convertir coordenadas de puntos PDF a píxeles de imagen
        $pdfToImageRatio = $this->resolution / 72; // 72 puntos por pulgada en PDF
        $pixelX = round($x * $pdfToImageRatio);
        $pixelY = round($y * $pdfToImageRatio);
        $pixelWidth = round($width * $pdfToImageRatio);
        $pixelHeight = round($height * $pdfToImageRatio);
        
        // Crear imagen recortada
        $croppedImage = imagecreatetruecolor($pixelWidth, $pixelHeight);
        
        // Copiar área seleccionada
        imagecopy(
            $croppedImage,
            $pageImage,
            0, 0,
            $pixelX, $pixelY,
            $pixelWidth, $pixelHeight
        );
        
        // Guardar imagen recortada
        imagepng($croppedImage, $croppedImagePath);
        
        // Liberar memoria
        imagedestroy($pageImage);
        imagedestroy($croppedImage);
        
        // Eliminar imagen de página completa
        @unlink($pageImagePath);
        
        return $croppedImagePath;
    }
    
    /**
     * Convierte un PDF completo a imágenes
     */
    public function convertPdfToImages($pdfPath, $outputDir = null)
    {
        if (!file_exists($pdfPath)) {
            throw new \Exception("Archivo PDF no encontrado: $pdfPath");
        }
        
        $outputDir = $outputDir ?? $this->tempDir . '/' . uniqid('pdf_images_');
        
        // Crear directorio de salida si no existe
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Obtener número de páginas
        $info = $this->getDocumentInfo($pdfPath);
        $pageCount = $info['pageCount'];
        
        $outputPaths = [];
        
        // Convertir cada página a imagen
        for ($page = 1; $page <= $pageCount; $page++) {
            $outputPath = $outputDir . '/page_' . $page . '.png';
            
            // Usar Ghostscript para convertir la página a imagen
            $command = escapeshellcmd($this->ghostscriptPath) . 
                      ' -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m' .
                      ' -r' . $this->resolution . 
                      ' -dFirstPage=' . $page . ' -dLastPage=' . $page .
                      ' -sOutputFile=' . escapeshellarg($outputPath) .
                      ' ' . escapeshellarg($pdfPath);
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($outputPath)) {
                throw new \Exception("Error al convertir página $page a imagen");
            }
            
            $outputPaths[$page] = $outputPath;
        }
        
        return [
            'directory' => $outputDir,
            'images' => $outputPaths,
            'pageCount' => $pageCount
        ];
    }
    
    /**
     * Limpia archivos temporales generados
     */
    public function cleanupTempFiles($files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        } elseif (is_string($files) && file_exists($files)) {
            @unlink($files);
        } elseif (is_string($files) && is_dir($files)) {
            // Eliminar directorio y su contenido
            $this->removeDirectory($files);
        }
    }
    
    /**
     * Elimina un directorio y todo su contenido
     */
    protected function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        @rmdir($dir);
    }
}