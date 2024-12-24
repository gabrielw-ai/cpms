<?php
session_start();
require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['importKPI']) && isset($_FILES['file'])) {
    try {
        $viewType = $_POST['view_type'] ?? 'weekly';
        
        $baseTableName = $_POST['table_name'];
        $baseTableName = preg_replace('/_MON$/', '', $baseTableName);
        
        $tableName = ($viewType === 'monthly') 
            ? $baseTableName . '_MON' 
            : $baseTableName;
        
        error_log("Import type: " . $viewType);
        error_log("Table name: " . $tableName);
        
        $inputFileName = $_FILES['file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Prepare statements
        $insertKPI = $conn->prepare("INSERT INTO `$tableName` 
            (queue, kpi_metrics, target, target_type) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            target = VALUES(target),
            target_type = VALUES(target_type)");

        $getKPIId = $conn->prepare("SELECT id FROM `$tableName` 
            WHERE queue = ? AND kpi_metrics = ?");

        // Set up period-specific variables
        if ($viewType === 'monthly') {
            $periodColumn = 'month';
            $periodCount = 12;
            $insertValue = $conn->prepare("INSERT INTO `{$tableName}_VALUES` 
                (kpi_id, month, value) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)");
        } else {
            $periodColumn = 'week';
            $periodCount = 52;
            $insertValue = $conn->prepare("INSERT INTO `{$tableName}_VALUES` 
                (kpi_id, week, value) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)");
        }
        
        $success = 0;
        $errors = [];
        
        foreach ($rows as $i => $row) {
            if (empty($row[0])) continue; // Skip empty rows
            
            try {
                if (count($row) < 4) {
                    throw new Exception("Row " . ($i + 2) . " has insufficient data");
                }

                $queue = $row[0];
                $kpi_metrics = $row[1];
                $target = $row[2];
                $target_type = $row[3];
                
                // Insert/Update KPI definition
                $insertKPI->execute([$queue, $kpi_metrics, $target, $target_type]);
                
                // Get KPI ID
                $getKPIId->execute([$queue, $kpi_metrics]);
                $kpiId = $getKPIId->fetchColumn();
                
                if (!$kpiId) {
                    throw new Exception("Failed to get KPI ID for $queue - $kpi_metrics");
                }
                
                // Process values - starting from column 4 (index 3)
                for ($period = 1; $period <= $periodCount; $period++) {
                    $columnIndex = $period + 3; // Excel columns start at index 4 (column E)
                    $value = isset($row[$columnIndex]) ? $row[$columnIndex] : null;
                    
                    if ($value !== null && $value !== '') {
                        if ($target_type === 'percentage') {
                            $value = str_replace('%', '', $value);
                        }
                        error_log("Inserting $periodColumn $period: $value (column index: $columnIndex)");
                        $insertValue->execute([$kpiId, $period, $value]);
                    }
                }
                
                $success++;
            } catch (Exception $e) {
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            $conn->commit();
            $_SESSION['success'] = "$success records imported successfully";
        } else {
            $conn->rollBack();
            $_SESSION['error'] = "Import failed: " . implode("; ", $errors);
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "Import failed: " . $e->getMessage();
    }
    
    $viewType = $_POST['view_type'] ?? 'weekly';
    header("Location: ../view/kpi_viewer.php?table=" . urlencode($tableName) . "&view=" . $viewType);
    exit;
}
?>
