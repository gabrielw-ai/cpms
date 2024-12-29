<?php
require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    $project = $_GET['project'];
    // Project name already contains 'kpi_' prefix, no need to add it again
    $tableName = $project . "_individual_mon";
    
    error_log("Exporting data from table: " . $tableName);

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()->setTitle("KPI Summary Export");
    
    // Add header row
    $sheet->setCellValue('A1', 'Queue')
          ->setCellValue('B1', 'KPI Metrics')
          ->setCellValue('C1', 'Target')
          ->setCellValue('D1', 'Target Type');

    // Style header row
    $sheet->getStyle('A1:D1')->applyFromArray([
        'font' => [
            'bold' => true
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'E2EFDA'
            ]
        ]
    ]);

    // Get data from database
    $stmt = $conn->query("
        SELECT 
            queue,
            kpi_metrics,
            target,
            target_type
        FROM `$tableName`
        ORDER BY queue, kpi_metrics
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add data to spreadsheet
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['queue'])
              ->setCellValue('B' . $row, $record['kpi_metrics'])
              ->setCellValue('C' . $row, $record['target'])
              ->setCellValue('D' . $row, $record['target_type']);
        $row++;
    }

    // Auto size columns
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set the filename - remove 'kpi_' from project name if it exists
    $projectName = str_replace('kpi_', '', $project);
    $filename = "kpi_summary_{$projectName}_" . date('Y-m-d') . ".xlsx";

    // Redirect output to a client's web browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 