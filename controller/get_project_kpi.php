<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting get_project_kpi.php");

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

    $project = $_GET['project'];
    error_log("Project requested: " . $project);
    
    // Get KPI metrics from the project's KPI metrics table
    $tableName = "KPI_" . str_replace(" ", "_", strtolower($project));
    error_log("Looking for KPI metrics in table: " . $tableName);
    
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    error_log("Table exists: " . ($tableExists ? 'yes' : 'no'));
    
    if (!$tableExists) {
        error_log("Table not found: " . $tableName);
        echo json_encode([
            'success' => false,
            'message' => 'No KPI metrics found for this project',
            'data' => []
        ]);
        exit;
    }

    // Get distinct KPI metrics from the project's KPI table
    $stmt = $conn->prepare("SELECT DISTINCT kpi_metrics FROM `$tableName` ORDER BY kpi_metrics");
    error_log("SQL Query: " . "SELECT DISTINCT kpi_metrics FROM `$tableName` ORDER BY kpi_metrics");
    $stmt->execute();
    $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Found metrics: " . print_r($metrics, true));
    
    if (empty($metrics)) {
        echo json_encode([
            'success' => false,
            'message' => 'No KPI metrics defined for this project',
            'data' => []
        ]);
        exit;
    }
    
    $response = [
        'success' => true,
        'data' => $metrics
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Error in get_project_kpi.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
    exit;
} 