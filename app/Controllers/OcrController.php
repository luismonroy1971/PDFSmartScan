<?php
namespace App\Controllers;

use App\Models\Document;
use App\Models\DocumentArea;
use Core\Session;
use Core\Request;
use Core\Response;
use thiagoalessio\TesseractOCR\TesseractOCR;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

class OcrController {
    private $documentModel;
    private $documentAreaModel;
    
    public function __construct() {
        $this->documentModel = new Document();
        $this->documentAreaModel = new DocumentArea();
        
        // Verificar si el usuario está autenticado
        if (!Session::get('user_id')) {
            Response::redirect('/login');
        }
    }
    
    public function process(Request $request) {
        $data = $request->getBody();
        
        if (empty($data['document_id'])) {
            return Response::json(['success' => false, 'message' => 'ID de documento no proporcionado']);
        }
        
        $documentId = $data['document_id'];
        $document = $this->documentModel->findById($documentId);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            return Response::json(['success' => false, 'message' => 'Documento no encontrado']);
        }
        
        $areas = $this->documentAreaModel->findByDocumentId($documentId);
        
        if (empty($areas)) {
            return Response::json(['success' => false, 'message' => 'No hay áreas definidas para este documento']);
        }
        
        $pdfPath = $document['file_path'];
        $results = [];
        
        try {
            // Procesar cada área definida
            foreach ($areas as $area) {
                // Extraer la imagen del área del PDF
                $imagePath = $this->extractAreaImage($pdfPath, $area);
                
                if (!$imagePath) {
                    $results[$area['column_name']] = 'Error al extraer imagen';
                    continue;
                }
                
                // Aplicar filtros para mejorar el reconocimiento
                $filteredImagePath = $this->applyImageFilters($imagePath);
                
                // Procesar OCR en la imagen
                $text = $this->processOCR($filteredImagePath);
                
                // Guardar el texto reconocido
                $results[$area['column_name']] = trim($text);
                
                // Eliminar imágenes temporales
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                if (file_exists($filteredImagePath) && $filteredImagePath !== $imagePath) {
                    unlink($filteredImagePath);
                }
            }
            
            return Response::json([
                'success' => true, 
                'results' => $results,
                'message' => 'Procesamiento OCR completado'
            ]);
            
        } catch (\Exception $e) {
            return Response::json([
                'success' => false, 
                'message' => 'Error al procesar OCR: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Extrae una imagen de un área específica de un PDF
     * 
     * @param string $pdfPath Ruta al archivo PDF
     * @param array $area Información del área a extraer
     * @return string|false Ruta a la imagen extraída o false en caso de error
     */
    private function extractAreaImage($pdfPath, $area) {
        $tempDir = sys_get_temp_dir();
        $outputImagePath = $tempDir . '/pdf_area_' . uniqid() . '.png';
        
        try {
            // Usar FPDI para extraer la página específica
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            // Verificar que el número de página sea válido
            $pageNumber = $area['page_number'];
            if ($pageNumber > $pageCount) {
                return false;
            }
            
            // Importar la página
            $templateId = $pdf->importPage($pageNumber);
            $specs = $pdf->getTemplateSize($templateId);
            $pdf->AddPage();
            
            // Calcular dimensiones originales de la página
            $pageWidth = $specs['width'];
            $pageHeight = $specs['height'];
            
            // Usar ghostscript para convertir la página a imagen
            $pageImagePath = $tempDir . '/pdf_page_' . uniqid() . '.png';
            $resolution = 300; // DPI para mejor calidad de OCR
            
            $cmd = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r{$resolution} " .
                   "-dFirstPage={$pageNumber} -dLastPage={$pageNumber} " .
                   "-sOutputFile={$pageImagePath} {$pdfPath} 2>&1";
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($pageImagePath)) {
                return false;
            }
            
            // Ahora recortar el área específica usando GD
            $image = imagecreatefrompng($pageImagePath);
            
            // Convertir coordenadas del área a píxeles según la resolución
            $factor = $resolution / 72; // Convertir de puntos PDF a píxeles
            $x = round($area['x_pos'] * $factor);
            $y = round($area['y_pos'] * $factor);
            $width = round($area['width'] * $factor);
            $height = round($area['height'] * $factor);
            
            // Recortar la imagen
            $croppedImage = imagecrop($image, [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height
            ]);
            
            // Guardar la imagen recortada
            imagepng($croppedImage, $outputImagePath);
            
            // Liberar recursos
            imagedestroy($image);
            imagedestroy($croppedImage);
            
            // Eliminar la imagen temporal de la página completa
            if (file_exists($pageImagePath)) {
                unlink($pageImagePath);
            }
            
            return $outputImagePath;
            
        } catch (\Exception $e) {
            // En caso de error, asegurarse de eliminar cualquier archivo temporal
            if (file_exists($outputImagePath)) {
                unlink($outputImagePath);
            }
            
            error_log('Error al extraer imagen del PDF: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aplica filtros para mejorar la calidad de la imagen para OCR
     * 
     * @param string $imagePath Ruta a la imagen original
     * @return string Ruta a la imagen procesada
     */
    private function applyImageFilters($imagePath) {
        $outputPath = sys_get_temp_dir() . '/filtered_' . basename($imagePath);
        
        try {
            // Cargar la imagen
            $image = imagecreatefrompng($imagePath);
            
            // Convertir a escala de grises
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            
            // Aumentar el contraste
            imagefilter($image, IMG_FILTER_CONTRAST, -20);
            
            // Eliminar ruido (suavizado)
            $matrix = [
                [1, 1, 1],
                [1, 1, 1],
                [1, 1, 1]
            ];
            // Aplicar filtro de convolución para suavizar
            imageconvolution($image, $matrix, 9, 0);
            
            // Umbral (threshold) para binarizar la imagen
            // Este bucle simula un filtro de umbral
            $threshold = 145;
            for ($x = 0; $x < imagesx($image); $x++) {
                for ($y = 0; $y < imagesy($image); $y++) {
                    $color = imagecolorat($image, $x, $y);
                    $gray = ($color >> 16) & 0xFF; // Obtener componente rojo (en escala de grises todos son iguales)
                    
                    if ($gray > $threshold) {
                        imagesetpixel($image, $x, $y, 0xFFFFFF); // Blanco
                    } else {
                        imagesetpixel($image, $x, $y, 0x000000); // Negro
                    }
                }
            }
            
            // Guardar la imagen procesada
            imagepng($image, $outputPath);
            
            // Liberar recursos
            imagedestroy($image);
            
            return $outputPath;
            
        } catch (\Exception $e) {
            error_log('Error al aplicar filtros a la imagen: ' . $e->getMessage());
            return $imagePath; // Devolver la ruta original en caso de error
        }
    }
    
    /**
     * Procesa OCR en una imagen
     * 
     * @param string $imagePath Ruta a la imagen a procesar
     * @return string Texto reconocido
     */
    private function processOCR($imagePath) {
        try {
            // Configurar Tesseract OCR
            $ocr = new TesseractOCR($imagePath);
            
            // Configurar para reconocer escritura manuscrita
            $ocr->configFile('tessdata/configs/hocr');
            
            // Admitir múltiples idiomas
            $ocr->lang('spa', 'eng');
            
            // Permitir reconocimiento de dígitos y letras
            $ocr->oem(1); // LSTM mode
            $ocr->psm(6); // Assume single block of text
            
            // Ejecutar OCR y obtener texto
            return $ocr->run();
            
        } catch (\Exception $e) {
            error_log('Error al ejecutar OCR: ' . $e->getMessage());
            return 'Error: ' . $e->getMessage();
        }
    }
}