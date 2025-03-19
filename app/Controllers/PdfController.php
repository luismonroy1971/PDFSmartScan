<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Request;
use App\Models\Document;
use App\Models\DocumentArea;
use App\Models\DocumentTemplate;
use App\Models\TemplateArea;
use App\Services\OcrService;
use App\Services\ExcelService;

class PdfController extends Controller
{
    protected $ocrService;
    protected $excelService;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Verificar si el usuario está autenticado
        if (!Session::get('user_id')) {
            Session::flash('error', 'Debe iniciar sesión para acceder a esta sección.');
            $this->redirect('/login');
        }
        
        $this->ocrService = new OcrService();
        $this->excelService = new ExcelService();
    }
    
    /**
     * Muestra la lista de documentos del usuario
     */
    public function index()
    {
        $userId = Session::get('user_id');
        $documents = Document::findByUserId($userId);
        
        return $this->view('pdf/index', ['documents' => $documents]);
    }
    
    /**
     * Muestra el formulario para subir un nuevo documento
     */
    public function upload()
    {
        return $this->view('pdf/upload');
    }
    
    /**
     * Procesa la subida de un nuevo documento
     */
    public function doUpload()
    {
        $request = new Request();
        $userId = Session::get('user_id');
        
        // Verificar si se ha subido un archivo
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Error al subir el archivo. Por favor, intente nuevamente.');
            return $this->redirect('/pdf/upload');
        }
        
        $file = $_FILES['pdf_file'];
        
        // Validar tipo de archivo
        $allowedTypes = ['application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            Session::flash('error', 'Solo se permiten archivos PDF.');
            return $this->redirect('/pdf/upload');
        }
        
        // Validar tamaño de archivo (máximo 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB en bytes
        if ($file['size'] > $maxSize) {
            Session::flash('error', 'El archivo excede el tamaño máximo permitido (10MB).');
            return $this->redirect('/pdf/upload');
        }
        
        // Generar nombre único para el archivo
        $filename = uniqid('pdf_') . '.pdf';
        $uploadDir = APP_PATH . '/public/uploads/' . $userId;
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . '/' . $filename;
        
        // Mover archivo subido
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            Session::flash('error', 'Error al guardar el archivo. Por favor, intente nuevamente.');
            return $this->redirect('/pdf/upload');
        }
        
        // Guardar información en la base de datos
        $document = new Document();
        $document->user_id = $userId;
        $document->filename = $filename;
        $document->original_filename = $file['name'];
        $document->file_size = $file['size'];
        $document->file_path = 'uploads/' . $userId . '/' . $filename;
        
        if ($document->save()) {
            Session::flash('success', 'Documento subido correctamente.');
            return $this->redirect('/pdf/view/' . $document->id);
        } else {
            // Si falla el guardado en la base de datos, eliminar el archivo
            @unlink($filePath);
            Session::flash('error', 'Error al registrar el documento. Por favor, intente nuevamente.');
            return $this->redirect('/pdf/upload');
        }
    }
    
    /**
     * Muestra un documento PDF con la interfaz para seleccionar áreas
     */
    public function view($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/pdf');
        }
        
        // Obtener áreas ya definidas para este documento
        $areas = DocumentArea::findByDocumentId($id);
        
        // Obtener plantillas disponibles del usuario
        $templates = DocumentTemplate::findByUserId($userId);
        
        return $this->view('pdf/view', [
            'document' => $document,
            'areas' => $areas,
            'templates' => $templates
        ]);
    }
    
    /**
     * Guarda un área seleccionada en un documento
     */
    public function saveArea()
    {
        $request = new Request();
        $userId = Session::get('user_id');
        
        $documentId = $request->post('document_id');
        $columnName = $request->post('column_name');
        $xPos = $request->post('x_pos');
        $yPos = $request->post('y_pos');
        $width = $request->post('width');
        $height = $request->post('height');
        $pageNumber = $request->post('page_number');
        
        // Validar datos
        if (empty($documentId) || empty($columnName) || 
            !isset($xPos) || !isset($yPos) || !isset($width) || !isset($height) || !isset($pageNumber)) {
            return json_encode(['success' => false, 'message' => 'Datos incompletos']);
        }
        
        // Verificar propiedad del documento
        $document = Document::find($documentId);
        if (!$document || $document->user_id != $userId) {
            return json_encode(['success' => false, 'message' => 'Documento no encontrado o no tiene permisos']);
        }
        
        // Crear o actualizar área
        $area = DocumentArea::findByDocumentAndColumn($documentId, $columnName);
        
        if (!$area) {
            $area = new DocumentArea();
            $area->document_id = $documentId;
            $area->column_name = $columnName;
        }
        
        $area->x_pos = $xPos;
        $area->y_pos = $yPos;
        $area->width = $width;
        $area->height = $height;
        $area->page_number = $pageNumber;
        
        if ($area->save()) {
            return json_encode(['success' => true, 'area_id' => $area->id]);
        } else {
            return json_encode(['success' => false, 'message' => 'Error al guardar el área']);
        }
    }
    
    /**
     * Elimina un área seleccionada
     */
    public function deleteArea($id)
    {
        $userId = Session::get('user_id');
        
        // Obtener el área
        $area = DocumentArea::find($id);
        
        if (!$area) {
            return json_encode(['success' => false, 'message' => 'Área no encontrada']);
        }
        
        // Verificar propiedad del documento
        $document = Document::find($area->document_id);
        if (!$document || $document->user_id != $userId) {
            return json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar esta área']);
        }
        
        // Eliminar área
        if ($area->delete()) {
            return json_encode(['success' => true]);
        } else {
            return json_encode(['success' => false, 'message' => 'Error al eliminar el área']);
        }
    }
    
    /**
     * Procesa un documento y extrae texto de las áreas seleccionadas
     */
    public function processDocument($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/pdf');
        }
        
        // Obtener áreas definidas para este documento
        $areas = DocumentArea::findByDocumentId($id);
        
        if (empty($areas)) {
            Session::flash('error', 'No hay áreas definidas para extraer. Por favor, seleccione al menos un área.');
            return $this->redirect('/pdf/view/' . $id);
        }
        
        try {
            // Preparar áreas para el procesamiento OCR
            $ocrAreas = [];
            foreach ($areas as $area) {
                $ocrAreas[] = [
                    'column_name' => $area->column_name,
                    'x_pos' => $area->x_pos,
                    'y_pos' => $area->y_pos,
                    'width' => $area->width,
                    'height' => $area->height,
                    'page_number' => $area->page_number
                ];
            }
            
            // Ruta completa al archivo PDF
            $pdfPath = APP_PATH . '/public/' . $document->file_path;
            
            // Procesar OCR
            $results = $this->ocrService->extractTextFromMultipleAreas($pdfPath, $ocrAreas);
            
            // Guardar resultados en sesión para mostrarlos
            Session::set('ocr_results', $results);
            Session::set('document_id', $id);
            
            // Redirigir a la página de resultados
            return $this->redirect('/pdf/results');
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al procesar el documento: ' . $e->getMessage());
            return $this->redirect('/pdf/view/' . $id);
        }
    }
    
    /**
     * Muestra los resultados de OCR
     */
    public function showResults()
    {
        $results = Session::get('ocr_results');
        $documentId = Session::get('document_id');
        
        if (!$results || !$documentId) {
            Session::flash('error', 'No hay resultados disponibles. Por favor, procese un documento primero.');
            return $this->redirect('/pdf');
        }
        
        $document = Document::find($documentId);
        
        return $this->view('pdf/results', [
            'results' => $results,
            'document' => $document
        ]);
    }
    
    /**
     * Exporta los resultados a Excel
     */
    public function exportToExcel()
    {
        $results = Session::get('ocr_results');
        $documentId = Session::get('document_id');
        
        if (!$results || !$documentId) {
            Session::flash('error', 'No hay resultados disponibles. Por favor, procese un documento primero.');
            return $this->redirect('/pdf');
        }
        
        $document = Document::find($documentId);
        
        try {
            // Generar archivo Excel
            $excelFile = $this->excelService->generateExcel($results, null, [
                'title' => 'Datos extraídos de ' . $document->original_filename,
                'author' => Session::get('user_name', 'Usuario')
            ]);
            
            // Preparar descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="datos_extraidos_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            readfile($excelFile);
            
            // Eliminar archivo temporal
            @unlink($excelFile);
            exit;
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            return $this->redirect('/pdf/results');
        }
    }
    
    /**
     * Guarda una configuración como plantilla
     */
    public function saveTemplate()
    {
        $request = new Request();
        $userId = Session::get('user_id');
        
        $documentId = $request->post('document_id');
        $templateName = $request->post('template_name');
        $templateDescription = $request->post('template_description');
        
        // Validar datos
        if (empty($documentId) || empty($templateName)) {
            Session::flash('error', 'Nombre de plantilla requerido.');
            return $this->redirect('/pdf/view/' . $documentId);
        }
        
        // Verificar propiedad del documento
        $document = Document::find($documentId);
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/pdf');
        }
        
        // Obtener áreas definidas para este documento
        $areas = DocumentArea::findByDocumentId($documentId);
        
        if (empty($areas)) {
            Session::flash('error', 'No hay áreas definidas para guardar como plantilla.');
            return $this->redirect('/pdf/view/' . $documentId);
        }
        
        try {
            // Crear nueva plantilla
            $template = new DocumentTemplate();
            $template->user_id = $userId;
            $template->name = $templateName;
            $template->description = $templateDescription;
            
            if (!$template->save()) {
                throw new Exception('Error al guardar la plantilla.');
            }
            
            // Guardar áreas de la plantilla
            foreach ($areas as $area) {
                $templateArea = new TemplateArea();
                $templateArea->template_id = $template->id;
                $templateArea->column_name = $area->column_name;
                $templateArea->x_pos = $area->x_pos;
                $templateArea->y_pos = $area->y_pos;
                $templateArea->width = $area->width;
                $templateArea->height = $area->height;
                $templateArea->page_number = $area->page_number;
                
                if (!$templateArea->save()) {
                    throw new Exception('Error al guardar un área de la plantilla.');
                }
            }
            
            Session::flash('success', 'Plantilla guardada correctamente.');
            return $this->redirect('/pdf/templates');
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al guardar la plantilla: ' . $e->getMessage());
            return $this->redirect('/pdf/view/' . $documentId);
        }
    }
    
    /**
     * Muestra la lista de plantillas del usuario
     */
    public function templates()
    {
        $userId = Session::get('user_id');
        $templates = DocumentTemplate::findByUserId($userId);
        
        return $this->view('pdf/templates', ['templates' => $templates]);
    }
    
    /**
     * Aplica una plantilla a un documento
     */
    public function applyTemplate()
    {
        $request = new Request();
        $userId = Session::get('user_id');
        
        $documentId = $request->post('document_id');
        $templateId = $request->post('template_id');
        
        // Validar datos
        if (empty($documentId) || empty($templateId)) {
            Session::flash('error', 'Datos incompletos.');
            return $this->redirect('/pdf/view/' . $documentId);
        }
        
        // Verificar propiedad del documento
        $document = Document::find($documentId);
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/pdf');
        }
        
        // Verificar propiedad de la plantilla
        $template = DocumentTemplate::find($templateId);
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/pdf/view/' . $documentId);
        }
        
        try {
            // Obtener áreas de la plantilla
            $templateAreas = TemplateArea::findByTemplateId($templateId);
            
            if (empty($templateAreas)) {
                throw new Exception('La plantilla no contiene áreas definidas.');
            }
            
            // Eliminar áreas existentes del documento
            DocumentArea::deleteByDocumentId($documentId);
            
            // Aplicar áreas de la plantilla al documento
            foreach ($templateAreas as $templateArea) {
                $documentArea = new DocumentArea();
                $documentArea->document_id = $documentId;
                $documentArea->column_name = $templateArea->column_name;
                $documentArea->x_pos = $templateArea->x_pos;
                $documentArea->y_pos = $templateArea->y_pos;
                $documentArea->width = $templateArea->width;
                $documentArea->height = $templateArea->height;
                $documentArea->page_number = $templateArea->page_number;
                
                if (!$documentArea->save()) {
                    throw new Exception('Error al aplicar un área de la plantilla.');
                }
            }
            
            Session::flash('success', 'Plantilla aplicada correctamente.');
            return $this->redirect('/pdf/view/' . $documentId);
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al aplicar la plantilla: ' . $e->getMessage());
            return $this->redirect('/pdf/view/' . $documentId);
        }
    }
    
    /**
     * Elimina una plantilla
     */
    public function deleteTemplate($id)
    {
        $userId = Session::get('user_id');
        
        // Verificar propiedad de la plantilla
        $template = DocumentTemplate::find($id);
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para eliminarla.');
            return $this->redirect('/pdf/templates');
        }
        
        try {
            // Eliminar áreas de la plantilla
            TemplateArea::deleteByTemplateId($id);
            
            // Eliminar plantilla
            if (!$template->delete()) {
                throw new Exception('Error al eliminar la plantilla.');
            }
            
            Session::flash('success', 'Plantilla eliminada correctamente.');
            return $this->redirect('/pdf/templates');
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al eliminar la plantilla: ' . $e->getMessage());
            return $this->redirect('/pdf/templates');
        }
    }
    
    /**
     * Procesa un lote de documentos PDF
     */
    public function processBatch()
    {
        $request = new Request();
        $userId = Session::get('user_id');
        
        // Obtener IDs de documentos seleccionados
        $documentIds = $request->post('document_ids', []);
        $templateId = $request->post('template_id');
        
        if (empty($documentIds) || empty($templateId)) {
            Session::flash('error', 'Debe seleccionar al menos un documento y una plantilla.');
            return $this->redirect('/pdf');
        }
        
        // Verificar propiedad de la plantilla
        $template = DocumentTemplate::find($templateId);
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/pdf');
        }
        
        // Obtener áreas de la plantilla
        $templateAreas = TemplateArea::findByTemplateId($templateId);
        
        if (empty($templateAreas)) {
            Session::flash('error', 'La plantilla no contiene áreas definidas.');
            return $this->redirect('/pdf');
        }
        
        try {
            // Preparar áreas para el procesamiento OCR
            $ocrAreas = [];
            foreach ($templateAreas as $area) {
                $ocrAreas[] = [
                    'column_name' => $area->column_name,
                    'x_pos' => $area->x_pos,
                    'y_pos' => $area->y_pos,
                    'width' => $area->width,
                    'height' => $area->height,
                    'page_number' => $area->page_number
                ];
            }
            
            // Preparar documentos para procesamiento
            $pdfPaths = [];
            foreach ($documentIds as $documentId) {
                $document = Document::find($documentId);
                
                if ($document && $document->user_id == $userId) {
                    $pdfPaths[$documentId] = APP_PATH . '/public/' . $document->file_path;
                }
            }
            
            if (empty($pdfPaths)) {
                throw new Exception('No se encontraron documentos válidos para procesar.');
            }
            
            // Procesar lote de documentos
            $batchResults = $this->ocrService->batchProcessDocuments($pdfPaths, $ocrAreas);
            
            // Generar archivo Excel con resultados
            $excelFile = $this->excelService->generateBatchExcel($batchResults, null, [
                'title' => 'Resultados de procesamiento por lotes',
                'author' => Session::get('user_name', 'Usuario')
            ]);
            
            // Preparar descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="resultados_lote_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            readfile($excelFile);
            
            // Eliminar archivo temporal
            @unlink($excelFile);
            exit;
            
        } catch (Exception $e) {
            Session::flash('error', 'Error al procesar el lote de documentos: ' . $e->getMessage());
            return $