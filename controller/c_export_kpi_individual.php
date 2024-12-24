<?php
require_once 'conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = ['NIK', 'Name', 'KPI Metrics', 'Queue', 'Month', 'Value'];
    foreach (range('A', 'F') as $i => $col) {
        $sheet->setCellValue($col . '1', $headers[$i]);
    }
    
    // Get data
    if (isset($_GET['template'])) {
        // Return empty template with headers only
    } else {
        // Get parameters
        $project = $_GET['project'];
        $kpiMetrics = json_decode($_GET['kpi'], true);
        $queues = json_decode($_GET['queue'], true);
        
        error_log("Exporting data for: " . json_encode(['project' => $project, 'kpi' => $kpiMetrics, 'queues' => $queues]));

        // Build query with filters
        $sql = "SELECT * FROM individual_staging WHERE 1=1";
        $params = [];

        if (!empty($kpiMetrics)) {
            $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
            $sql .= " AND kpi_metrics IN ($placeholders)";
            $params = array_merge($params, $kpiMetrics);
        }

        if (!empty($queues)) {
            $placeholders = str_repeat('?,', count($queues) - 1) . '?';
            $sql .= " AND queue IN ($placeholders)";
            $params = array_merge($params, $queues);
        }

        $sql .= " ORDER BY NIK, kpi_metrics, queue";
        
        error_log("Export SQL: " . $sql);
        error_log("Export params: " . json_encode($params));

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $row = 2;
        foreach ($data as $record) {
            foreach (['january', 'february', 'march', 'april', 'may', 'june', 
                     'july', 'august', 'september', 'october', 'november', 'december'] as $month) {
                if (!is_null($record[$month])) {
                    $sheet->setCellValue('A' . $row, $record['NIK']);
                    $sheet->setCellValue('B' . $row, $record['employee_name']);
                    $sheet->setCellValue('C' . $row, $record['kpi_metrics']);
                    $sheet->setCellValue('D' . $row, $record['queue']);
                    $sheet->setCellValue('E' . $row, ucfirst($month));
                    $sheet->setCellValue('F' . $row, $record[$month]);
                    $row++;
                }
            }
        }
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="kpi_data.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
} 