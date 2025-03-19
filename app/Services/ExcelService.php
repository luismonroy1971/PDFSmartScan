<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Exception;

class ExcelService
{
    /**
     * Genera un archivo Excel a partir de los datos extraídos
     * 
     * @param array $data Datos estructurados por columna
     * @param string $outputPath Ruta donde guardar el archivo (opcional)
     * @param array $options Opciones de formato y configuración
     * @return string Ruta al archivo generado
     */
    public function generateExcel($data, $outputPath = null, $options = [])
    {
        // Opciones por defecto
        $defaultOptions = [
            'title' => 'Datos Extraídos',
            'author' => 'PDF Smart Scan',
            'sheetName' => 'Hoja1',
            'headerStyle' => true,
            'autoWidth' => true,
            'format' => 'xlsx'
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Crear nuevo documento Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar metadatos
        $spreadsheet->getProperties()
            ->setCreator($options['author'])
            ->setLastModifiedBy($options['author'])
            ->setTitle($options['title'])
            ->setSubject('Datos extraídos por OCR')
            ->setDescription('Documento generado automáticamente por PDF Smart Scan');
        
        // Renombrar hoja
        $sheet->setTitle($options['sheetName']);
        
        // Determinar si los datos están en formato de múltiples filas o una sola fila
        $isMultiRow = isset($data[0]) && is_array($data[0]);
        
        if ($isMultiRow) {
            // Formato de múltiples filas (array de arrays)
            $this->populateMultiRowData($sheet, $data, $options);
        } else {
            // Formato de una sola fila (array asociativo)
            $this->populateSingleRowData($sheet, $data, $options);
        }
        
        // Ajustar anchos de columna si está habilitado
        if ($options['autoWidth']) {
            $this->autoAdjustColumnWidths($sheet);
        }
        
        // Generar nombre de archivo si no se proporcionó
        if ($outputPath === null) {
            $tempDir = sys_get_temp_dir();
            $outputPath = $tempDir . '/' . uniqid('excel_') . '.' . $options['format'];
        }
        
        // Guardar archivo en el formato especificado
        if ($options['format'] === 'xlsx') {
            $writer = new Xlsx($spreadsheet);
        } else if ($options['format'] === 'csv') {
            $writer = new Csv($spreadsheet);
            // Configurar opciones específicas de CSV
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
        } else {
            throw new Exception("Formato de archivo no soportado: {$options['format']}");
        }
        
        $writer->save($outputPath);
        
        return $outputPath;
    }
    
    /**
     * Rellena la hoja con datos de múltiples filas
     */
    private function populateMultiRowData($sheet, $data, $options)
    {
        if (empty($data)) {
            return;
        }
        
        // Obtener encabezados (claves del primer elemento)
        $headers = array_keys($data[0]);
        
        // Escribir encabezados
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }
        
        // Aplicar estilo a encabezados si está habilitado
        if ($options['headerStyle']) {
            $lastColumn = count($headers);
            $headerRange = 'A1:' . $this->getColumnLetter($lastColumn) . '1';
            
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Fijar la primera fila
            $sheet->freezePane('A2');
        }
        
        // Escribir datos
        foreach ($data as $rowIndex => $rowData) {
            foreach ($headers as $colIndex => $header) {
                $value = isset($rowData[$header]) ? $rowData[$header] : '';
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }
    }
    
    /**
     * Rellena la hoja con datos de una sola fila
     */
    private function populateSingleRowData($sheet, $data, $options)
    {
        if (empty($data)) {
            return;
        }
        
        // Escribir encabezados (claves) en columna A y valores en columna B
        $rowIndex = 1;
        foreach ($data as $key => $value) {
            $sheet->setCellValue('A' . $rowIndex, $key);
            $sheet->setCellValue('B' . $rowIndex, $value);
            $rowIndex++;
        }
        
        // Aplicar estilo a encabezados si está habilitado
        if ($options['headerStyle']) {
            $lastRow = count($data);
            $headerRange = 'A1:A' . $lastRow;
            
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    '