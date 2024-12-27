<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Function to safely encode JSON response
function sendJsonResponse($success, $data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

try {
    // Validate request
    if (!isset($_FILES['file']) || !isset($_POST['project'])) {
        sendJsonResponse(false, ['error' => 'File and project are required']);
    }

    // Log debugging information
    error_log("Project: " . $_POST['project']);
    error_log("File upload info: " . print_r($_FILES['file'], true));

    // Validate file upload
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMessage = isset($uploadErrors[$_FILES['file']['error']]) 
            ? $uploadErrors[$_FILES['file']['error']] 
            : 'Unknown upload error';
        sendJsonResponse(false, ['error' => $errorMessage]);
    }

    $project = $_POST['project'];
    $baseTableName = "KPI_" . str_replace(" ", "_", strtoupper($project));
    $monthlyTableName = $baseTableName . "_MON";

    // Log table names
    error_log("Base table name: " . $baseTableName);
    error_log("Monthly table name: " . $monthlyTableName);

    // Validate file exists
    if (!file_exists($_FILES['file']['tmp_name'])) {
        sendJsonResponse(false, ['error' => 'Uploaded file not found']);
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    } catch (Exception $e) {
        error_log("Excel load error: " . $e->getMessage());
        sendJsonResponse(false, ['error' => 'Failed to load Excel file: ' . $e->getMessage()]);
    }

    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Log row count
    error_log("Number of rows: " . count($rows));

    // Validate we have data
    if (count($rows) < 2) {
        sendJsonResponse(false, ['error' => 'File contains no data']);
    }

    // Remove header row
    array_shift($rows);

    // Start transaction
    $conn->beginTransaction();

    $processed = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        if (empty($row[0])) continue;

        try {
            $queue = trim($row[0]);
            $kpiMetrics = trim($row[1]);
            $target = trim($row[2]);
            $targetType = strtolower(trim($row[3]));

            // Log row data
            error_log("Processing row " . ($index + 2) . ": " . implode(", ", $row));

            // Validate data
            if (empty($queue) || empty($kpiMetrics) || $target === '') {
                throw new Exception("Missing required data in row " . ($index + 2));
            }

            // Validate target type
            if (!in_array($targetType, ['percentage', 'number'])) {
                throw new Exception("Invalid target type '$targetType' in row " . ($index + 2));
            }

            // Update both weekly and monthly tables
            $tables = [$baseTableName, $monthlyTableName];
            
            foreach ($tables as $table) {
                // Check if KPI exists
                $stmt = $conn->prepare("SELECT id FROM `$table` WHERE queue = ? AND kpi_metrics = ?");
                $stmt->execute([$queue, $kpiMetrics]);
                $exists = $stmt->fetch();

                if ($exists) {
                    $stmt = $conn->prepare("
                        UPDATE `$table` 
                        SET target = ?, target_type = ?
                        WHERE queue = ? AND kpi_metrics = ?
                    ");
                    $stmt->execute([$target, $targetType, $queue, $kpiMetrics]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO `$table` 
                        (queue, kpi_metrics, target, target_type)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$queue, $kpiMetrics, $target, $targetType]);
                }
            }

            $processed++;

        } catch (Exception $e) {
            error_log("Row error: " . $e->getMessage());
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $conn->commit();
        sendJsonResponse(true, [
            'message' => "Successfully processed $processed records",
            'processed' => $processed
        ]);
    } else {
        $conn->rollBack();
        sendJsonResponse(false, ['error' => implode(", ", $errors)]);
    }

} catch (Exception $e) {
    error_log("Import error in " . __FILE__ . ": " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    sendJsonResponse(false, [
        'error' => 'Import failed: ' . $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ]);
} 