<?php
require_once 'conn.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file']) || !isset($_POST['project'])) {
        throw new Exception('File and project are required');
    }

    $project = $_POST['project'];
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project));

    // Load the Excel file
    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remove header row
    array_shift($rows);

    // Start transaction
    $conn->beginTransaction();

    $processed = 0;
    $errors = [];

    foreach ($rows as $index => $row) {
        if (empty($row[0])) continue; // Skip empty rows

        try {
            $queue = trim($row[0]);
            $kpiMetrics = trim($row[1]);
            $target = trim($row[2]);
            $targetType = strtolower(trim($row[3]));

            // Validate target type
            if (!in_array($targetType, ['percentage', 'number'])) {
                throw new Exception("Invalid target type: $targetType");
            }

            // Check if KPI exists
            $stmt = $conn->prepare("
                SELECT id FROM `$tableName` 
                WHERE queue = ? AND kpi_metrics = ?
            ");
            $stmt->execute([$queue, $kpiMetrics]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Update existing KPI
                $stmt = $conn->prepare("
                    UPDATE `$tableName` 
                    SET target = ?, target_type = ?
                    WHERE queue = ? AND kpi_metrics = ?
                ");
                $stmt->execute([$target, $targetType, $queue, $kpiMetrics]);
            } else {
                // Insert new KPI
                $stmt = $conn->prepare("
                    INSERT INTO `$tableName` 
                    (queue, kpi_metrics, target, target_type)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$queue, $kpiMetrics, $target, $targetType]);
            }

            $processed++;

        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'processed' => $processed
        ]);
    } else {
        throw new Exception(implode(", ", $errors));
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