<?php
namespace App\Controllers;

use App\Models\Document;
use App\Models\DocumentArea;
use Core\Session;
use Core\Request;
use Core\Response;
use App\Controllers\OcrController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ExcelController {
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
    
    public function showConfigure($id) {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        $areas = $this->documentAreaModel->findByDocumentId($id);
        
        if (empty($areas)) {
            Session::setFlash('error', 'No hay áreas definidas para este documento');
            return Response::redirect('/pdf/area-selector/' . $id);
        }
        
        return view('excel/configure', [
            'document' => $document,
            'areas' => $areas
        ]);
    }
    
    public function configure(Request $request) {
        $data = $request->getBody();
        
        if (empty($data['document_id']) || empty($data['columns'])) {
            Session::setFlash('error', 'Faltan datos requeridos');
            return Response::redirect('/dashboard');
        }
        
        $documentId = $data['document_id'];
        $document = $this->documentModel->findById($documentId);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        // Actualizar los nombres de las columnas para cada área
        foreach ($data['columns'] as $areaId => $columnName) {
            $this->documentAreaModel->update($areaId, [
                'column_name' => $columnName
            ]);
        }
        
        Session::setFlash('success', 'Configuración guardada correctamente');
        return Response::redirect('/excel/download/' . $documentId);
    }
    
    public function download($id, $format = 'xlsx') {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        $areas = $this->documentAreaModel->findByDocumentId($id);
        
        if (empty($areas)) {
            Session::setFlash('error', 'No hay áreas definidas para este documento');
            return Response::redirect('/pdf/area-selector/' . $id);
        }
        
        try {
            // Obtener los resultados OCR de las áreas
            $ocrController = new OcrController();
            $ocrResults = $this->processOCR($id);
            
            if (!$ocrResults['success']) {
                Session::setFlash('error', 'Error al procesar OCR: ' . $ocrResults['message']);
                return Response::redirect('/dashboard');
            }
            
            // Generar el archivo Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Añadir encabezados
            $col = 1;
            foreach ($areas as $area) {
                $sheet->setCellValueByColumnAndRow($col++, 1, $area['column_name']);
            }
            
            // Añadir datos
            $row = 2;
            $dataRow = [];
            
            foreach ($areas as $area) {
                $columnName = $area['column_name'];
                $dataRow[] = $ocrResults['results'][$columnName] ?? '';
            }
            
            $col = 1;
            foreach ($dataRow as $value) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $value);
            }
            
            // Guardar el archivo
            $filename = 'documento_' . $id . '_' . date('Ymd_His');
            
            if ($format === 'csv') {
                $writer = new Csv($spreadsheet);
                $contentType = 'text/csv';
                $filename .= '.csv';
            } else {
                $writer = new Xlsx($spreadsheet);
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $filename .= '.xlsx';
            }
            
            // Enviar al navegador
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            Session::setFlash('error', 'Error al generar el archivo: ' . $e->getMessage());
            return Response::redirect('/dashboard');
        }
    }
    
    public function processOCR($documentId) {
        $document = $this->documentModel->findById($documentId);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            return ['success' => false, 'message' => 'Documento no encontrado'];
        }
        
        $areas = $this->documentAreaModel->findByDocumentId($documentId);
        
        if (empty($areas)) {
            return ['success' => false, 'message' => 'No hay áreas definidas para este documento'];
        }
        
        $pdfPath = $document['file_path'];
        $results = [];
        
        try {
            $ocrController = new OcrController();
            
            // Simular una request para el controlador OCR
            $request = new Request();
            $request->setMethod('POST');
            $request->setBody(['document_id' => $documentId]);
            
            // Obtener los resultados del procesamiento OCR
            $response = $ocrController->process($request);
            
            // Convertir la respuesta JSON a un array
            $responseData = json_decode($response->getContent(), true);
            
            return $responseData;
            
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error al procesar OCR: ' . $e->getMessage()
            ];
        }
    }
    
    public function showDownload($id) {
        $document = $this->documentModel->findById($id);
        
        if (!$document || $document['user_id'] != Session::get('user_id')) {
            Session::setFlash('error', 'Documento no encontrado');
            return Response::redirect('/dashboard');
        }
        
        return view('excel/download', [
            'document' => $document
        ]);
    }
}