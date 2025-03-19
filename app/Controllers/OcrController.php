<?php

namespace App\Controllers;

use App\Models\Document;
use App\Models\DocumentArea;
use App\Core\Session;
use App\Core\Response;
use App\Services\OcrService;
use App\Services\ExcelService;

class OcrController extends BaseController
{
    protected $ocrService;
    protected $excelService;
    
    public function __construct()
    {
        parent::__construct();
        $this->ocrService = new OcrService();
        $this->excelService = new ExcelService();
    }
    
    /**
     * Muestra la página de procesamiento OCR
     */
    public function showProcess($documentId)
    {
        $userId = Session::get('user_id');
        $document = Document::find($documentId);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Obtener áreas seleccionadas para este documento
        $areas = DocumentArea::findAllByDocument($documentId);
        
        if (empty($areas)) {
            Session::flash('error', 'No hay áreas seleccionadas para procesar. Por favor, seleccione al menos un área.');
            return $this->redirect('/documents/view/' . $documentId);
        }
        
        return $this->view('ocr/process', [
            'document' => $document,
            'areas' => $areas
        ]);
    }
    
    /**
     * Procesa el OCR para un documento
     */
    public function process($documentId)
    {
        $userId = Session::get('user_id');
        $document = Document::find($documentId);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            return Response::json([
                'success' => false,
                'message' => 'Documento no encontrado o no tiene permisos para acceder.'
            ]);
        }
        
        // Obtener áreas seleccionadas
        $areas = DocumentArea::findAllByDocument($documentId);
        
        if (empty($areas)) {
            return Response::json([
                'success' => false,
                'message' => 'No hay áreas seleccionadas para procesar.'
            ]);
        }
        
        // Configurar opciones de OCR
        $options = [
            'language' => $_POST['language'] ?? 'spa',
            'mode' => $_POST['mode'] ?? 'auto',
            'optimize' => isset($_POST['optimize']) && $_POST['optimize'] === '1'
        ];
        
        try {
            // Procesar OCR para cada área
            $results = [];
            $pdfPath = PUBLIC_PATH . '/' . $document->file_path;
            
            foreach ($areas as $area) {
                $text = $this->ocrService->processArea($pdfPath, $area, $options);
                $results[$area->id] = [
                    'column_name' => $area->column_name,
                    'text' => $text,
                    'page' => $area->page_number,
                    'coordinates' => [
                        'x' => $area->x_pos,
                        'y' => $area->y_pos,
                        'width' => $area->width,
                        'height' => $area->height
                    ]
                ];
            }
            
            // Guardar resultados en sesión para exportación
            Session::set('ocr_results_' . $documentId, $results);
            
            return Response::json([
                'success' => true,
                'message' => 'Procesamiento OCR completado correctamente.',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'message' => 'Error al procesar OCR: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Exporta los resultados OCR a Excel
     */
    public function exportExcel($documentId)
    {
        $userId = Session::get('user_id');
        $document = Document::find($documentId);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Obtener resultados de OCR de la sesión
        $results = Session::get('ocr_results_' . $documentId);
        
        if (empty($results)) {
            Session::flash('error', 'No hay resultados de OCR disponibles. Por favor, procese el documento primero.');
            return $this->redirect('/ocr/process/' . $documentId);
        }
        
        // Configurar opciones de exportación
        $options = [
            'format' => $_GET['format'] ?? 'xlsx',
            'filename' => $_GET['filename'] ?? 'datos_extraidos_' . date('Y-m-d'),
            'sheet_name' => $_GET['sheet_name'] ?? 'Datos Extraídos'
        ];
        
        try {
            // Generar archivo Excel
            $excelFile = $this->excelService->generateExcel($results, $options);
            
            // Determinar tipo MIME según formato
            $mimeType = ($options['format'] == 'csv') 
                ? 'text/csv' 
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            
            // Configurar cabeceras para descarga
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment;filename="' . $options['filename'] . '.' . $options['format'] . '"');
            header('Cache-Control: max-age=0');
            
            // Enviar archivo al navegador
            readfile($excelFile);
            
            // Eliminar archivo temporal
            unlink($excelFile);
            
            exit;
        } catch (\Exception $e) {
            Session::flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            return $this->redirect('/ocr/process/' . $documentId);
        }
    }
    
    /**
     * Muestra la página de configuración de OCR
     */
    public function showSettings()
    {
        // Verificar si el usuario es administrador
        if (Session::get('user_role') !== 'admin') {
            Session::flash('error', 'No tiene permisos para acceder a esta página.');
            return $this->redirect('/dashboard');
        }
        
        // Obtener configuración actual
        $settings = [
            'languages' => $this->ocrService->getAvailableLanguages(),
            'current_language' => $this->ocrService->getDefaultLanguage(),
            'optimization_level' => $this->ocrService->getOptimizationLevel(),
            'tesseract_path' => $this->ocrService->getTesseractPath(),
            'ghostscript_path' => $this->ocrService->getGhostscriptPath()
        ];
        
        return $this->view('ocr/settings', [
            'settings' => $settings
        ]);
    }
    
    /**
     * Guarda la configuración de OCR
     */
    public function saveSettings()
    {
        // Verificar si el usuario es administrador
        if (Session::get('user_role') !== 'admin') {
            Session::flash('error', 'No tiene permisos para acceder a esta funcionalidad.');
            return $this->redirect('/dashboard');
        }
        
        // Validar datos de entrada
        $validator = new Validator($_POST);
        $validator->required(['default_language', 'optimization_level']);
        
        if (!$validator->isValid()) {
            Session::flash('error', 'Por favor, complete todos los campos correctamente.');
            Session::flash('errors', $validator->getErrors());
            Session::flash('old', $_POST);
            return $this->redirect('/ocr/settings');
        }
        
        try {
            // Actualizar configuración
            $this->ocrService->setDefaultLanguage($_POST['default_language']);
            $this->ocrService->setOptimizationLevel($_POST['optimization_level']);
            
            if (!empty($_POST['tesseract_path'])) {
                $this->ocrService->setTesseractPath($_POST['tesseract_path']);
            }
            
            if (!empty($_POST['ghostscript_path'])) {
                $this->ocrService->setGhostscriptPath($_POST['ghostscript_path']);
            }
            
            Session::flash('success', 'Configuración de OCR actualizada correctamente.');
        } catch (\Exception $e) {
            Session::flash('error', 'Error al guardar la configuración: ' . $e->getMessage());
        }
        
        return $this->redirect('/ocr/settings');
    }
}