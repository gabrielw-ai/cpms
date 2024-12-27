<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_kpi_individual.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    $required = ['project', 'nik', 'name', 'kpi_metrics', 'queue', 'month', 'value'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $project = $_POST['project'];
    $nik = $_POST['nik'];
    $name = $_POST['name'];
    $kpiMetrics = $_POST['kpi_metrics'];
    $queue = $_POST['queue'];
    $month = strtolower($_POST['month']);
    $value = floatval($_POST['value']);

    // Convert table name to lowercase
    $tableName = "kpi_" . strtolower(str_replace(" ", "_", $project)) . "_individual_mon";

    error_log("Adding KPI for Project: $project, NIK: $nik, Month: $month, Value: $value");

    // Check if record exists
    $checkSql = "SELECT id FROM `$tableName` 
                 WHERE nik = ? 
                 AND kpi_metrics = ? 
                 AND queue = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$nik, $kpiMetrics, $queue]);
    $exists = $checkStmt->fetch();

    if ($exists) {
        // Update existing record
        $sql = "UPDATE `$tableName` 
                SET `$month` = ? 
                WHERE nik = ? 
                AND kpi_metrics = ? 
                AND queue = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$value, $nik, $kpiMetrics, $queue]);
    } else {
        // Insert new record
        $sql = "INSERT INTO `$tableName` 
                (nik, employee_name, kpi_metrics, queue, `$month`) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nik, $name, $kpiMetrics, $queue, $value]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'KPI added successfully'
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error in c_kpi_individual.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
