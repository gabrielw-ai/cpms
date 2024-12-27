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
    // Convert table name to lowercase
    $tableName = "kpi_" . strtolower(str_replace(" ", "_", $project)) . "_individual_mon";
    
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    $conn->beginTransaction();
    $processed = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        try {
            if (empty($row[0])) continue; // Skip empty rows
            
            // Prepare data
            $data = [
                'nik' => $row[0],
                'employee_name' => $row[1],
                'kpi_metrics' => $row[2],
                'queue' => $row[3],
                'january' => $row[4],
                'february' => $row[5],
                'march' => $row[6],
                'april' => $row[7],
                'may' => $row[8],
                'june' => $row[9],
                'july' => $row[10],
                'august' => $row[11],
                'september' => $row[12],
                'october' => $row[13],
                'november' => $row[14],
                'december' => $row[15]
            ];
            
            // Build columns and values for SQL
            $columns = array_keys($data);
            $values = array_values($data);
                
            $sql = "INSERT INTO `$tableName` (" . implode(", ", $columns) . ") 
                    VALUES (" . str_repeat("?,", count($values)-1) . "?)";
                
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            
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