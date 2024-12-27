<?php
// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting c_kpi_individual_process.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Received input: " . print_r($input, true));

    // Validate required fields
    if (!isset($input['project']) || !isset($input['metrics']) || !isset($input['queues'])) {
        throw new Exception('Missing required parameters');
    }

    if (empty($input['metrics']) || empty($input['queues'])) {
        throw new Exception('Please select KPI metrics and queues');
    }

    $project = $input['project'];
    $metrics = $input['metrics'];
    $queues = $input['queues'];

    // Get data from the monthly table
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project)) . "_INDIVIDUAL_MON";
    error_log("Looking for data in table: " . $tableName);

    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception('No data found for this project');
    }

    // Prepare placeholders for the IN clauses
    $metricPlaceholders = str_repeat('?,', count($metrics) - 1) . '?';
    $queuePlaceholders = str_repeat('?,', count($queues) - 1) . '?';

    // Build the query
    $sql = "SELECT * FROM `$tableName` 
            WHERE kpi_metrics IN ($metricPlaceholders) 
            AND queue IN ($queuePlaceholders)
            ORDER BY NIK, kpi_metrics, queue";

    error_log("SQL Query: " . $sql);
    error_log("Metrics: " . print_r($metrics, true));
    error_log("Queues: " . print_r($queues, true));

    $stmt = $conn->prepare($sql);
    
    // Combine parameters in the correct order
    $params = array_merge($metrics, $queues);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found data: " . print_r($data, true));

    if (empty($data)) {
        echo json_encode([
            'success' => true,
            'message' => 'No data found for the selected criteria',
            'data' => []
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
    exit;

} catch (Exception $e) {
    error_log("Error in c_kpi_individual_process.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 