<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Request;
use App\Core\Response;
use App\Models\DocumentTemplate;
use App\Models\TemplateArea;
use App\Models\Document;
use App\Models\DocumentArea;

class TemplateController extends Controller
{
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
    }
    
    /**
     * Muestra la lista de plantillas del usuario
     */
    public function index()
    {
        $userId = Session::get('user_id');
        $templates = DocumentTemplate::findAllByUser($userId);
        
        return $this->view('templates/index', [
            'templates' => $templates
        ]);
    }
    
    /**
     * Muestra el formulario para crear una nueva plantilla
     */
    public function create()
    {
        return $this->view('templates/create');
    }
    
    /**
     * Guarda una nueva plantilla
     */
    public function store()
    {
        $userId = Session::get('user_id');
        $request = new Request();
        
        // Validar datos
        $name = $request->post('name');
        $description = $request->post('description');
        
        if (empty($name)) {
            Session::flash('error', 'El nombre de la plantilla es obligatorio.');
            return $this->redirect('/templates/create');
        }
        
        // Crear nueva plantilla
        $template = new DocumentTemplate();
        $template->user_id = $userId;
        $template->name = $name;
        $template->description = $description;
        
        if ($template->save()) {
            Session::flash('success', 'Plantilla creada correctamente.');
            return $this->redirect('/templates');
        } else {
            Session::flash('error', 'Error al crear la plantilla.');
            return $this->redirect('/templates/create');
        }
    }
    
    /**
     * Muestra el formulario para editar una plantilla
     */
    public function edit($id)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        // Obtener áreas de la plantilla
        $areas = TemplateArea::findAllByTemplate($id);
        
        return $this->view('templates/edit', [
            'template' => $template,
            'areas' => $areas
        ]);
    }
    
    /**
     * Actualiza una plantilla existente
     */
    public function update($id)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        $request = new Request();
        
        // Validar datos
        $name = $request->post('name');
        $description = $request->post('description');
        
        if (empty($name)) {
            Session::flash('error', 'El nombre de la plantilla es obligatorio.');
            return $this->redirect('/templates/edit/' . $id);
        }
        
        // Actualizar plantilla
        $template->name = $name;
        $template->description = $description;
        
        if ($template->save()) {
            Session::flash('success', 'Plantilla actualizada correctamente.');
            return $this->redirect('/templates');
        } else {
            Session::flash('error', 'Error al actualizar la plantilla.');
            return $this->redirect('/templates/edit/' . $id);
        }
    }
    
    /**
     * Elimina una plantilla
     */
    public function delete($id)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        // Eliminar plantilla (esto también eliminará las áreas asociadas)
        if ($template->delete()) {
            Session::flash('success', 'Plantilla eliminada correctamente.');
        } else {
            Session::flash('error', 'Error al eliminar la plantilla.');
        }
        
        return $this->redirect('/templates');
    }
    
    /**
     * Muestra la interfaz para definir áreas en una plantilla
     */
    public function defineAreas($id)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        // Obtener documentos del usuario para seleccionar uno como referencia
        $documents = Document::findAllByUser($userId);
        
        // Obtener áreas existentes de la plantilla
        $areas = TemplateArea::findAllByTemplate($id);
        
        return $this->view('templates/define_areas', [
            'template' => $template,
            'documents' => $documents,
            'areas' => $areas
        ]);
    }
    
    /**
     * Guarda un área para una plantilla
     */
    public function saveArea($id)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            return Response::json(['success' => false, 'message' => 'Plantilla no encontrada o no tiene permisos para acceder.']);
        }
        
        $request = new Request();
        
        // Validar datos
        $columnName = $request->post('column_name');
        $x = $request->post('x');
        $y = $request->post('y');
        $width = $request->post('width');
        $height = $request->post('height');
        $pageNumber = $request->post('page_number');
        
        if (empty($columnName) || !is_numeric($x) || !is_numeric($y) || !is_numeric($width) || !is_numeric($height) || !is_numeric($pageNumber)) {
            return Response::json(['success' => false, 'message' => 'Datos de área inválidos.']);
        }
        
        // Crear nueva área o actualizar existente
        $areaId = $request->post('area_id');
        
        if ($areaId) {
            $area = TemplateArea::find($areaId);
            
            // Verificar si el área existe y pertenece a la plantilla
            if (!$area || $area->template_id != $id) {
                return Response::json(['success' => false, 'message' => 'Área no encontrada o no pertenece a esta plantilla.']);
            }
        } else {
            $area = new TemplateArea();
            $area->template_id = $id;
        }
        
        // Actualizar datos del área
        $area->column_name = $columnName;
        $area->x_pos = $x;
        $area->y_pos = $y;
        $area->width = $width;
        $area->height = $height;
        $area->page_number = $pageNumber;
        
        if ($area->save()) {
            return Response::json([
                'success' => true, 
                'message' => 'Área guardada correctamente.',
                'area' => [
                    'id' => $area->id,
                    'column_name' => $area->column_name,
                    'x_pos' => $area->x_pos,
                    'y_pos' => $area->y_pos,
                    'width' => $area->width,
                    'height' => $area->height,
                    'page_number' => $area->page_number
                ]
            ]);
        } else {
            return Response::json(['success' => false, 'message' => 'Error al guardar el área.']);
        }
    }
    
    /**
     * Elimina un área de una plantilla
     */
    public function deleteArea($id, $areaId)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            return Response::json(['success' => false, 'message' => 'Plantilla no encontrada o no tiene permisos para acceder.']);
        }
        
        $area = TemplateArea::find($areaId);
        
        // Verificar si el área existe y pertenece a la plantilla
        if (!$area || $area->template_id != $id) {
            return Response::json(['success' => false, 'message' => 'Área no encontrada o no pertenece a esta plantilla.']);
        }
        
        if ($area->delete()) {
            return Response::json(['success' => true, 'message' => 'Área eliminada correctamente.']);
        } else {
            return Response::json(['success' => false, 'message' => 'Error al eliminar el área.']);
        }
    }
    
    /**
     * Aplica una plantilla a un documento
     */
    public function applyToDocument($id, $documentId)
    {
        $userId = Session::get('user_id');
        $template = DocumentTemplate::find($id);
        
        // Verificar si la plantilla existe y pertenece al usuario
        if (!$template || $template->user_id != $userId) {
            Session::flash('error', 'Plantilla no encontrada o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        $document = Document::find($documentId);
        
        // Verificar si el documento existe y pertenece al usuario
        if (!$document || $document->user_id != $userId) {
            Session::flash('error', 'Documento no encontrado o no tiene permisos para acceder.');
            return $this->redirect('/templates');
        }
        
        // Obtener áreas de la plantilla
        $templateAreas = TemplateArea::findAllByTemplate($id);
        
        if (empty($templateAreas)) {
            Session::flash('error', 'La plantilla no tiene áreas definidas.');
            return $this->redirect('/documents/view/' . $documentId);
        }
        
        // Eliminar áreas existentes del documento
        DocumentArea::deleteAllByDocument($documentId);
        
        // Crear nuevas áreas para el documento basadas en la plantilla
        foreach ($templateAreas as $templateArea) {
            $documentArea = $templateArea->toDocumentArea($documentId);
            $documentArea->save();
        }
        
        Session::flash('success', 'Plantilla aplicada correctamente al documento.');
        return $this->redirect('/documents/view/' . $documentId);
    }
}