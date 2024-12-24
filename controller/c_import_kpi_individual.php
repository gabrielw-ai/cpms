<?php
require_once 'conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }
    
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    // Begin transaction
    $conn->beginTransaction();
    
    $monthMapping = [
        'January' => 'january',
        'February' => 'february',
        'March' => 'march',
        'April' => 'april',
        'May' => 'may',
        'June' => 'june',
        'July' => 'july',
        'August' => 'august',
        'September' => 'september',
        'October' => 'october',
        'November' => 'november',
        'December' => 'december'
    ];
    
    $processed = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        if (empty($row[0])) continue; // Skip empty rows
        
        try {
            $nik = trim($row[0]);
            $name = trim($row[1]);
            $kpiMetrics = trim($row[2]);
            $queue = trim($row[3]);
            $month = trim($row[4]);
            $value = trim($row[5]);
            
            if (!isset($monthMapping[$month])) {
                throw new Exception("Invalid month format: $month");
            }
            
            $monthColumn = strtolower($monthMapping[$month]);
            
            error_log("Processing row " . ($index + 2) . ": " . json_encode([
                'nik' => $nik,
                'name' => $name,
                'kpi' => $kpiMetrics,
                'queue' => $queue,
                'month' => $monthColumn,
                'value' => $value
            ]));
            
            // Check if record exists
            $stmt = $conn->prepare("
                SELECT id FROM individual_staging 
                WHERE NIK = ? AND kpi_metrics = ? AND queue = ?
            ");
            $stmt->execute([$nik, $kpiMetrics, $queue]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing record
                $sql = "UPDATE individual_staging SET 
                        employee_name = ?,
                        $monthColumn = ?
                        WHERE NIK = ? AND kpi_metrics = ? AND queue = ?";
                $params = [$name, $value, $nik, $kpiMetrics, $queue];
            } else {
                // Insert new record
                $sql = "INSERT INTO individual_staging 
                        (NIK, employee_name, kpi_metrics, queue, $monthColumn) 
                        VALUES (?, ?, ?, ?, ?)";
                $params = [$nik, $name, $kpiMetrics, $queue, $value];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $processed++;
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "Successfully processed $processed records"
        ]);
    } else {
        throw new Exception("Errors occurred:\n" . implode("\n", $errors));
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Import error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 