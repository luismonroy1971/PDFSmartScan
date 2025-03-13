<?php
namespace App\Controllers;

use App\Models\Document;
use App\Models\DocumentArea;
use Core\Session;
use Core\Request;
use Core\Response;

class PdfController {
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
    
    public function showUpload() {
        return view('pdf/upload');
    }
    
    public function upload(Request $request) {
        $files = $request->getFiles();
        
        if (empty($files['pdf_file'])) {
            Session::setFlash('error', 'Por favor selecciona un archivo PDF');
            return Response::redirect('/pdf/upload');
        }
        
        $file = $files['pdf_file'];
        
        // Validar que sea un archivo PDF
        $mime = $file['type'];
        if ($mime !== 'application/pdf') {
            Session::setFlash('error', 'El archivo debe ser un PDF');
            return Response::redirect('/pdf/upload');
        }
        
        // Validar tamaño máximo (10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            Session::setFlash('error', 'El archivo no debe superar los 10MB');
            return Response::redirect('/pdf/upload');
        }
        
        // Crear directorio de uploads si no existe
        $uploadDir = UPLOAD_PATH . '/' . Session::get('user_id');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $filename = uniqid() . '.pdf';
        $destination = $uploadDir . '/' . $filename;
        
        // Mover el archivo al directorio de uploads
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Guardar información del documento en la base de datos
            $documentId = $this->documentModel->create([
                'user_id' => Session::get('user_id'),
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_size' => $file['size'],
                'file_path' => $destination
            ]);
            
            if ($documentId) {
                // Ejemplo en PdfController.php después de subir un documento
                $activityData = [
                    'user_id' => Session::get('user_id'),
                    'document_id' => $documentId,
                    'type' => 'upload',
                    'details' => 'Documento subido: ' . $file['name'],
                    'status' => 'success'
                ];
                $this->documentModel->logActivity($activityData);
                Session::setFlash('success', 'Archivo PDF subido correctamente');
                return Response::redirect('/pdf/view/' . $documentId);
            } else {
                // Si falla al guardar en la base de datos, eliminar el archivo
                unlink($destination);
                Session::setFlash('error', 'Error al guardar la información del documento');
                return Response::redirect('/pdf/upload');
            }
        } else {
            Session::setFlash('error', 'Error al subir el archivo');
            return Response::redirect('/pdf/upload');
        }
    }
    
    public function view($id) {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        return view('pdf/view', ['document' => $document]);
    }
    
    public function showAreaSelector($id) {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        $areas = $this->documentAreaModel->findByDocumentId($id);
        
        return view('pdf/area-selector', [
            'document' => $document,
            'areas' => $areas
        ]);
    }
    
    public function saveAreas(Request $request) {
        $data = $request->getBody();
        
        if (empty($data['document_id']) || empty($data['areas'])) {
            return Response::json(['success' => false, 'message' => 'Faltan datos requeridos']);
        }
        
        $documentId = $data['document_id'];
        $document = $this->documentModel->findById($documentId);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            return Response::json(['success' => false, 'message' => 'Documento no encontrado']);
        }
        
        // Eliminar áreas existentes
        $this->documentAreaModel->deleteByDocumentId($documentId);
        
        // Guardar nuevas áreas
        foreach ($data['areas'] as $area) {
            $this->documentAreaModel->create([
                'document_id' => $documentId,
                'column_name' => $area['column_name'],
                'x_pos' => $area['x'],
                'y_pos' => $area['y'],
                'width' => $area['width'],
                'height' => $area['height'],
                'page_number' => $area['page'] ?? 1
            ]);
        }
        
        return Response::json(['success' => true, 'message' => 'Áreas guardadas correctamente']);
    }
    
    public function delete($id) {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        // Eliminar el archivo físico
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        // Eliminar el documento de la base de datos
        $this->documentModel->delete($id);
        
        Session::setFlash('success', 'Documento eliminado correctamente');
        return Response::redirect('/dashboard');
    }
}