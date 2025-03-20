<?php

namespace App\Controllers;

use App\Models\Document;
use App\Models\DocumentArea;
use App\Core\Session;
use App\Core\Validator;
use App\Core\Response;
use App\Services\OcrService;
use App\Services\PdfService;
use App\Services\ExcelService;

class DocumentController extends BaseController
{
    /**
     * Muestra la lista de documentos del usuario
     */
    public function index()
    {
        $userId = Session::get('user_id');
        $documents = Document::findAllByUser($userId);
        
        return $this->view('documents/index', [
            'documents' => $documents
        ]);
    }
    
    /**
     * Muestra el formulario para subir un nuevo documento
     */
    public function create()
    {
        return $this->view('documents/create');
    }
    
    /**
     * Procesa la subida de un nuevo documento
     */
    public function store()
    {
        // Verificar si se ha subido un archivo
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Error al subir el archivo. Por favor, inténtelo de nuevo.');
            return $this->redirect('/documents/create');
        }
        
        $file = $_FILES['pdf_file'];
        
        // Validar el archivo
        $validator = new Validator([
            'file' => $file
        ]);
        
        // Validar tipo MIME y extensión
        $allowedMimes = ['application/pdf'];
        $allowedExtensions = ['pdf'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file['type'], $allowedMimes) || !in_array($fileExtension, $allowedExtensions)) {
            Session::flash('error', 'El archivo debe ser un PDF válido.');
            return $this->redirect('/documents/create');
        }
        
        // Validar tamaño (máximo 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB en bytes
        if ($file['size'] > $maxSize) {
            Session::flash('error', 'El archivo no debe superar los 10MB.');
            return $this->redirect('/documents/create');
        }
        
        $userId = Session::get('user_id');
        
        // Generar nombre único para el archivo
        $filename = uniqid('doc_') . '.pdf';
        $uploadDir = UPLOAD_PATH . '/' . $userId;
        $filePath = $uploadDir . '/' . $filename;
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Mover el archivo subido
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            Session::flash('error', 'Error al guardar el archivo. Por favor, inténtelo de nuevo.');
            return $this->redirect('/documents/create');
        }
        
        // Crear registro en la base de datos
        $document = new Document();
        $document->user_id = $userId;
        $document->filename = $filename;
        $document->original_filename = $file['name'];
        $document->file_size = $file['size'];
        $document->file_path = $filePath;
        $document->save();
        
        Session::flash('success', 'Documento subido correctamente.');
        return $this->redirect('/documents/view/' . $document->id);
    }
    
    /**
     * Muestra un documento para su visualización y selección de áreas
     */
    public function view($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Obtener áreas ya definidas para este documento
        $areas = DocumentArea::findAllByDocument($id);
        
        // Obtener información del PDF (número de páginas, dimensiones)
        $pdfService = new PdfService();
        $pdfInfo = $pdfService->getDocumentInfo($document->file_path);
        
        // Retornar la vista con los datos
        return $this->view('documents/view', [
            'document' => $document,
            'areas' => $areas,
            'pdfInfo' => $pdfInfo
        ]);
    }
    
    /**
     * Guarda un área seleccionada en un documento
     */
    public function saveArea()
    {
        // Validar datos de entrada
        $validator = new Validator($_POST);
        $validator->required(['document_id', 'column_name', 'x_pos', 'y_pos', 'width', 'height', 'page_number']);
        
        if (!$validator->isValid()) {
            return Response::json([
                'success' => false,
                'message' => 'Datos incompletos o inválidos.'
            ]);
        }
        
        $userId = Session::get('user_id');
        $documentId = $_POST['document_id'];
        
        // Verificar propiedad del documento
        $document = Document::find($documentId);
        if (!$document || $document->user_id != $userId) {
            return Response::json([
                'success' => false,
                'message' => 'Documento no encontrado o no tiene permisos para acceder.'
            ]);
        }
        
        // Crear o actualizar área
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Actualizar área existente
            $area = DocumentArea::find($_POST['id']);
            
            if (!$area || $area->document_id != $documentId) {
                return Response::json([
                    'success' => false,
                    'message' => 'Área no encontrada o no pertenece a este documento.'
                ]);
            }
        } else {
            // Crear nueva área
            $area = new DocumentArea();
            $area->document_id = $documentId;
        }
        
        // Actualizar datos del área
        $area->column_name = $_POST['column_name'];
        $area->x_pos = (float) $_POST['x_pos'];
        $area->y_pos = (float) $_POST['y_pos'];
        $area->width = (float) $_POST['width'];
        $area->height = (float) $_POST['height'];
        $area->page_number = (int) $_POST['page_number'];
        $area->save();
        
        return Response::json([
            'success' => true,
            'message' => 'Área guardada correctamente.',
            'area_id' => $area->id
        ]);
    }
    
    /**
     * Elimina un área de un documento
     */
    public function deleteArea($id)
    {
        $userId = Session::get('user_id');
        $area = DocumentArea::find($id);
        
        // Verificar si el área existe y pertenece a un documento
        if (!$area) {
            return Response::json([
                'success' => false,
                'message' => 'Área no encontrada.'
            ]);
        }
        
        $document = Document::find($area->document_id);
        if (!$document || $document->user_id != $userId) {
            return Response::json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar esta área.'
            ]);
        }
        
        // Eliminar área
        $area->delete();
        
        return Response::json([
            'success' => true,
            'message' => 'Área eliminada correctamente.'
        ]);
    }
    
    /**
     * Procesa un documento para extraer texto mediante OCR
     */
    public function processOcr($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            return Response::json([
                'success' => false,
                'message' => 'Documento no encontrado o no tiene permisos para acceder.'
            ]);
        }
        
        // Obtener áreas definidas para este documento
        $areas = DocumentArea::findAllByDocument($id);
        
        if (empty($areas)) {
            return Response::json([
                'success' => false,
                'message' => 'No hay áreas definidas para extraer. Por favor, seleccione al menos un área.'
            ]);
        }
        
        // Inicializar servicio OCR
        $ocrService = new OcrService();
        $pdfService = new PdfService();
        
        $results = [];
        
        // Procesar cada área
        foreach ($areas as $area) {
            // Extraer imagen del área del PDF
            $imagePath = $pdfService->extractAreaImage(
                $document->file_path,
                $area->getCoordinates()
            );
            
            if (!$imagePath) {
                $results[$area->id] = [
                    'success' => false,
                    'text' => '',
                    'error' => 'Error al extraer imagen del área'
                ];
                continue;
            }
            
            // Procesar OCR en la imagen
            $text = $ocrService->processImage($imagePath);
            
            // Guardar resultado
            $results[$area->id] = [
                'success' => true,
                'text' => $text,
                'column_name' => $area->column_name
            ];
            
            // Eliminar imagen temporal
            @unlink($imagePath);
        }
        
        return Response::json([
            'success' => true,
            'results' => $results
        ]);
    }
    
    /**
     * Exporta los resultados del OCR a Excel
     */
    public function exportToExcel($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Obtener áreas definidas para este documento
        $areas = DocumentArea::findAllByDocument($id);
        
        if (empty($areas)) {
            Session::flash('error', 'No hay áreas definidas para exportar. Por favor, seleccione al menos un área.');
            return $this->redirect('/documents/view/' . $id);
        }
        
        // Inicializar servicios
        $ocrService = new OcrService();
        $pdfService = new PdfService();
        $excelService = new ExcelService();
        
        $data = [];
        $headers = [];
        
        // Procesar cada área
        foreach ($areas as $area) {
            // Añadir nombre de columna a los encabezados
            $headers[] = $area->column_name;
            
            // Extraer imagen del área del PDF
            $imagePath = $pdfService->extractAreaImage(
                $document->file_path,
                $area->getCoordinates()
            );
            
            if (!$imagePath) {
                $data[] = 'Error al extraer imagen';
                continue;
            }
            
            // Procesar OCR en la imagen
            $text = $ocrService->processImage($imagePath);
            $data[] = $text;
            
            // Eliminar imagen temporal
            @unlink($imagePath);
        }
        
        // Generar archivo Excel
        $excelPath = $excelService->generateExcel([$headers, $data], $document->original_filename);
        
        if (!$excelPath) {
            Session::flash('error', 'Error al generar el archivo Excel.');
            return $this->redirect('/documents/view/' . $id);
        }
        
        // Descargar archivo
        return Response::download($excelPath, pathinfo($document->original_filename, PATHINFO_FILENAME) . '.xlsx');
    }
    
    /**
     * Muestra el formulario para editar un documento
     */
    public function edit($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        return $this->view('documents/edit', [
            'document' => $document
        ]);
    }
    
    /**
     * Actualiza un documento existente
     */
    public function update($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Verificar si se ha subido un nuevo archivo
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf_file'];
            
            // Validar el archivo
            $validator = new Validator([
                'file' => $file
            ]);
            
            // Validar tipo MIME y extensión
            $allowedMimes = ['application/pdf'];
            $allowedExtensions = ['pdf'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file['type'], $allowedMimes) || !in_array($fileExtension, $allowedExtensions)) {
                Session::flash('error', 'El archivo debe ser un PDF válido.');
                return $this->redirect('/documents/edit/' . $id);
            }
            
            // Validar tamaño (máximo 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB en bytes
            if ($file['size'] > $maxSize) {
                Session::flash('error', 'El archivo no debe superar los 10MB.');
                return $this->redirect('/documents/edit/' . $id);
            }
            
            // Eliminar archivo anterior
            $oldFilePath = APP_PATH . '/public/' . $document->file_path;
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
            
            // Generar nombre único para el nuevo archivo
            $filename = uniqid('doc_') . '.pdf';
            $uploadDir = UPLOAD_PATH . '/' . $userId;
            $filePath = $uploadDir . '/' . $filename;
            
            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Mover el archivo subido
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                Session::flash('error', 'Error al guardar el archivo. Por favor, inténtelo de nuevo.');
                return $this->redirect('/documents/edit/' . $id);
            }
            
            // Actualizar información del documento
            $document->filename = $filename;
            $document->original_filename = $file['name'];
            $document->file_size = $file['size'];
            $document->file_path = $filePath;
        }
        
        // Actualizar otros campos si es necesario (por ejemplo, etiquetas o categorías)
        if (isset($_POST['tags'])) {
            $this->updateTag($document->id, $_POST['tags']);
        }
        
        // Guardar cambios
        if ($document->save()) {
            Session::flash('success', 'Documento actualizado correctamente.');
            return $this->redirect('/documents/view/' . $document->id);
        } else {
            Session::flash('error', 'Error al actualizar el documento.');
            return $this->redirect('/documents/edit/' . $id);
        }
    }
    
    /**
     * Actualiza las etiquetas de un documento
     * 
     * @param int $documentId ID del documento
     * @param string $tags Etiquetas separadas por comas
     * @return bool Éxito o fracaso de la operación
     */
    public function updateTag($documentId, $tags)
    {
        $userId = Session::get('user_id');
        $document = Document::find($documentId);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            return false;
        }
        
        // Aquí se implementaría la lógica para guardar las etiquetas
        // Por ahora, como no existe un campo tags en la tabla documents,
        // simplemente retornamos true para evitar errores
        return true;
    }
    
    /**
     * Elimina un documento
     */
    public function delete($id)
    {
        $userId = Session::get('user_id');
        $document = Document::find($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/documents');
        }
        
        // Eliminar documento (esto también eliminará el archivo físico y las áreas asociadas)
        if ($document->delete()) {
            Session::flash('success', 'Documento eliminado correctamente.');
        } else {
            Session::flash('error', 'Error al eliminar el documento.');
        }
        
        return $this->redirect('/documents');
    }

}
