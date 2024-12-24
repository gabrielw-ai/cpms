<?php
require 'vendor/autoload.php';
require_once 'conn.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['table'])) {
    try {
        $tableName = $_GET['table'];
        // Remove any duplicate _MON suffixes
        $tableName = preg_replace('/_MON_MON$/', '_MON', $tableName);
        $viewType = (strpos($tableName, '_MON') !== false) ? 'monthly' : 'weekly';
        
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set basic headers
        $sheet->setCellValue('A1', 'Queue');
        $sheet->setCellValue('B1', 'KPI Metrics');
        $sheet->setCellValue('C1', 'Target');
        $sheet->setCellValue('D1', 'Target Type');
        
        // Set period headers based on view type
        if ($viewType === 'monthly') {
            $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                      'July', 'August', 'September', 'October', 'November', 'December'];
            foreach ($months as $i => $month) {
                $col = getColumnLetter($i + 5); // Start from column E
                $sheet->setCellValue($col . '1', substr($month, 0, 3));
            }
            $periodCount = 12;
        } else {
            for ($i = 1; $i <= 52; $i++) {
                $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                $col = getColumnLetter($i + 4);
                $sheet->setCellValue($col . '1', "WK$weekNum");
            }
            $periodCount = 52;
        }
        
        // Get data with JOIN - update the query to handle both weekly and monthly
        if ($viewType === 'monthly') {
            $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, 
                           v.month, v.value
                    FROM `$tableName` k
                    LEFT JOIN `{$tableName}_VALUES` v ON k.id = v.kpi_id
                    ORDER BY k.queue, k.kpi_metrics, v.month";
        } else {
            $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, 
                           v.week, v.value
                    FROM `$tableName` k
                    LEFT JOIN `{$tableName}_VALUES` v ON k.id = v.kpi_id
                    ORDER BY k.queue, k.kpi_metrics, v.week";
        }
        
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process data into rows
        $currentRow = 2;
        $currentKPI = null;
        $rowData = [];
        
        foreach ($data as $row) {
            $kpiKey = $row['queue'] . '|' . $row['kpi_metrics'];
            
            if (!isset($rowData[$kpiKey])) {
                $rowData[$kpiKey] = [
                    'queue' => $row['queue'],
                    'kpi_metrics' => $row['kpi_metrics'],
                    'target' => $row['target'],
                    'target_type' => $row['target_type'],
                    'values' => array_fill(1, $periodCount, null)
                ];
            }
            
            // Store value using the correct period (week or month)
            $period = $viewType === 'monthly' ? $row['month'] : $row['week'];
            if ($period !== null) {
                $rowData[$kpiKey]['values'][$period] = $row['value'];
            }
        }
        
        // Write data to sheet
        foreach ($rowData as $row) {
            $sheet->setCellValue('A' . $currentRow, $row['queue']);
            $sheet->setCellValue('B' . $currentRow, $row['kpi_metrics']);
            $sheet->setCellValue('C' . $currentRow, $row['target']);
            $sheet->setCellValue('D' . $currentRow, $row['target_type']);
            
            // Write period values
            for ($i = 1; $i <= $periodCount; $i++) {
                $col = getColumnLetter($i + 4);
                $value = $row['values'][$i];
                if ($value !== null) {
                    if ($row['target_type'] === 'percentage') {
                        $value .= '%';
                    }
                    $sheet->setCellValue($col . $currentRow, $value);
                }
            }
            $currentRow++;
        }
        
        // Style headers
        $lastCol = $viewType === 'monthly' ? 'P' : 'BA'; // P for 12 months (E to P), BA for 52 weeks
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA']
            ]
        ]);
        
        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create writer and output file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $tableName . '_export.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save to output
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Helper function to convert number to Excel column letter
function getColumnLetter($n) {
    $letter = '';
    while ($n > 0) {
        $n--;
        $letter = chr(65 + ($n % 26)) . $letter;
        $n = intdiv($n, 26);
    }
    return $letter;
}
?>
