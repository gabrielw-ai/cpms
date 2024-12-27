<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting get_project_queues.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    if (!isset($_GET['kpi'])) {
        throw new Exception('KPI metrics parameter is required');
    }

    $project = $_GET['project'];
    $kpiMetrics = json_decode($_GET['kpi']);
    
    error_log("Project requested: " . $project);
    error_log("KPI metrics requested: " . print_r($kpiMetrics, true));
    
    // Get queues from the project's KPI metrics table
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project));
    error_log("Looking for queues in table: " . $tableName);
    
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    error_log("Table exists: " . ($tableExists ? 'yes' : 'no'));
    
    if (!$tableExists) {
        error_log("Table not found: " . $tableName);
        echo json_encode([
            'success' => false,
            'message' => 'No queues found for this project',
            'data' => []
        ]);
        exit;
    }

    // Get distinct queues from the project's KPI table for selected metrics
    $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
    $sql = "SELECT DISTINCT queue FROM `$tableName` WHERE kpi_metrics IN ($placeholders) ORDER BY queue";
    $stmt = $conn->prepare($sql);
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($kpiMetrics, true));
    
    $stmt->execute($kpiMetrics);
    $queues = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Found queues: " . print_r($queues, true));
    
    if (empty($queues)) {
        echo json_encode([
            'success' => false,
            'message' => 'No queues defined for selected KPI metrics',
            'data' => []
        ]);
        exit;
    }
    
    $response = [
        'success' => true,
        'data' => $queues
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Error in get_project_queues.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
    exit;
} 