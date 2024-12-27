<?php
require_once 'conn.php';
require 'vendor/autoload.php';
global $conn;

use PhpOffice\PhpSpreadsheet\IOFactory;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_import_kpi_individual.php");

// Clean any output buffer
ob_clean();

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    if (!isset($_FILES['file']) || !isset($_POST['project'])) {
        throw new Exception('Missing required parameters');
    }

    $project = $_POST['project'];
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project)) . "_INDIVIDUAL_MON";
    
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    // Begin transaction
    $conn->beginTransaction();
    
    $processed = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        if (empty($row[0])) continue; // Skip empty rows
        
        try {
            $nik = trim($row[0]);
            $name = trim($row[1]);
            $kpiMetrics = trim($row[2]);
            $queue = trim($row[3]);
            
            // Process monthly values (columns 4-15)
            $monthlyValues = array_slice($row, 4, 12);
            $months = ['january', 'february', 'march', 'april', 'may', 'june', 
                      'july', 'august', 'september', 'october', 'november', 'december'];
            
            // Check if record exists
            $stmt = $conn->prepare("
                SELECT id FROM `$tableName` 
                WHERE NIK = ? AND kpi_metrics = ? AND queue = ?
            ");
            $stmt->execute([$nik, $kpiMetrics, $queue]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing record
                $updates = [];
                $params = [];
                foreach ($months as $i => $month) {
                    if (isset($monthlyValues[$i]) && $monthlyValues[$i] !== '') {
                        $updates[] = "`$month` = ?";
                        $params[] = floatval($monthlyValues[$i]);
                    }
                }
                
                if (!empty($updates)) {
                    $sql = "UPDATE `$tableName` SET " . implode(", ", $updates) . 
                           " WHERE NIK = ? AND kpi_metrics = ? AND queue = ?";
                    $params = array_merge($params, [$nik, $kpiMetrics, $queue]);
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                }
            } else {
                // Insert new record
                $columns = ['NIK', 'employee_name', 'kpi_metrics', 'queue'];
                $values = [$nik, $name, $kpiMetrics, $queue];
                
                foreach ($months as $i => $month) {
                    if (isset($monthlyValues[$i]) && $monthlyValues[$i] !== '') {
                        $columns[] = "`$month`";
                        $values[] = floatval($monthlyValues[$i]);
                    }
                }
                
                $sql = "INSERT INTO `$tableName` (" . implode(", ", $columns) . ") 
                        VALUES (" . str_repeat("?,", count($values)-1) . "?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($values);
            }
            
            $processed++;
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        $conn->commit();
        die(json_encode([
            'success' => true,
            'message' => "Successfully processed $processed records"
        ]));
    } else {
        throw new Exception(implode("\n", $errors));
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Import error: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
} 