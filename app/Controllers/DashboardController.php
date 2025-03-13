<?php
namespace App\Controllers;

use App\Models\Document;
use Core\Session;
use Core\Request;
use Core\Response;

class DashboardController {
    private $documentModel;
    
    public function __construct() {
        $this->documentModel = new Document();
        
        // Verificar si el usuario está autenticado
        if (!Session::get('user_id')) {
            Response::redirect('/login');
        }
    }
    
    /**
     * Muestra el dashboard con los documentos del usuario
     * 
     * @param Request $request Objeto de solicitud
     * @return string Vista renderizada
     */
    public function index(Request $request) {
        $userId = Session::get('user_id');
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $limit = 10; // Documentos por página
        $offset = ($page - 1) * $limit;
        
        // Obtener documentos del usuario con paginación
        $documents = $this->documentModel->findByUserId($userId, $limit, $offset);
        $totalDocuments = $this->documentModel->countByUserId($userId);
        $totalPages = ceil($totalDocuments / $limit);
        
        // Datos para la vista
        $data = [
            'documents' => $documents,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalDocuments' => $totalDocuments
        ];
        
        // Si hay algún mensaje flash, añadirlo a los datos
        if (Session::hasFlash('success')) {
            $data['success'] = Session::getFlash('success');
        }
        
        if (Session::hasFlash('error')) {
            $data['error'] = Session::getFlash('error');
        }
        
        return view('dashboard/index', $data);
    }
    
    /**
     * Muestra las estadísticas de uso del sistema
     * 
     * @return string Vista renderizada
     */
    public function stats() {
        // Verificar si el usuario es administrador
        if (Session::get('user_role') !== 'admin') {
            Session::setFlash('error', 'Acceso denegado');
            return Response::redirect('/dashboard');
        }
        
        // Aquí se obtendrían estadísticas como:
        // - Número total de documentos
        // - Número total de extracciones OCR
        // - Distribución de tipos de documento
        // - Usuarios más activos
        
        $data = [
            'totalDocuments' => $this->documentModel->countAll(),
            'recentDocuments' => $this->documentModel->findRecent(5),
            'statsPerUser' => $this->documentModel->getStatsPerUser(),
            'statsPerMonth' => $this->documentModel->getStatsPerMonth()
        ];
        
        return view('dashboard/stats', $data);
    }
    
    /**
     * Muestra el historial de actividad del usuario
     * 
     * @return string Vista renderizada
     */
    public function history() {
        $userId = Session::get('user_id');
        
        // Obtener historial de documentos procesados
        $history = $this->documentModel->getProcessingHistory($userId);
        
        return view('dashboard/history', [
            'history' => $history
        ]);
    }
    
    /**
     * Muestra el panel de configuración del usuario
     * 
     * @return string Vista renderizada
     */
    public function settings() {
        $userId = Session::get('user_id');
        
        // Obtener usuario desde un modelo de usuario
        $userModel = new \App\Models\User();
        $user = $userModel->findById($userId);
        
        // Obtener plantillas guardadas del usuario
        $templateModel = new \App\Models\DocumentTemplate();
        $templates = $templateModel->findByUserId($userId);
        
        return view('dashboard/settings', [
            'user' => $user,
            'templates' => $templates
        ]);
    }
    
    /**
     * Actualiza la configuración del usuario
     * 
     * @param Request $request Objeto de solicitud
     * @return mixed Redirección
     */
    public function updateSettings(Request $request) {
        $userId = Session::get('user_id');
        $data = $request->getBody();
        
        // Validar datos
        if (empty($data['name']) || empty($data['email'])) {
            Session::setFlash('error', 'Nombre y email son obligatorios');
            return Response::redirect('/dashboard/settings');
        }
        
        // Actualizar datos del usuario
        $userModel = new \App\Models\User();
        $result = $userModel->update($userId, [
            'name' => $data['name'],
            'email' => $data['email'],
            // Otros campos que puedan ser actualizados
        ]);
        
        if ($result) {
            // Actualizar datos de sesión
            Session::set('user_name', $data['name']);
            Session::set('user_email', $data['email']);
            
            Session::setFlash('success', 'Configuración actualizada correctamente');
        } else {
            Session::setFlash('error', 'Error al actualizar la configuración');
        }
        
        return Response::redirect('/dashboard/settings');
    }
    
    /**
     * Elimina un documento y redirige al dashboard
     * 
     * @param int $id ID del documento a eliminar
     * @return mixed Redirección
     */
    public function deleteDocument($id) {
        // Esta función podría estar en PdfController, pero se incluye aquí
        // para mostrar cómo se podría implementar la eliminación desde el dashboard
        
        $userId = Session::get('user_id');
        $document = $this->documentModel->findById($id);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document['user_id'] != $userId) {
            Session::setFlash('error', 'Documento no encontrado o sin permisos');
            return Response::redirect('/dashboard');
        }
        
        // Eliminar el archivo físico
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        // Eliminar el documento de la base de datos
        $result = $this->documentModel->delete($id);
        
        if ($result) {
            Session::setFlash('success', 'Documento eliminado correctamente');
        } else {
            Session::setFlash('error', 'Error al eliminar el documento');
        }
        
        return Response::redirect('/dashboard');
    }
}