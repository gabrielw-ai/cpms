<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['table'])) {
    try {
        // Add error logging
        error_log("Fetching KPI metrics for table: " . $_GET['table']);
        
        $tableName = strtolower($_GET['table']);
        $sql = "SELECT DISTINCT kpi_metrics FROM `$tableName` ORDER BY kpi_metrics";
        
        error_log("SQL Query: " . $sql);
        
        $stmt = $conn->query($sql);
        $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Found metrics: " . print_r($metrics, true));
        
        echo json_encode($metrics);
    } catch (PDOException $e) {
        error_log("Error in get_kpi_metrics.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Table parameter is required']);
}
?> 