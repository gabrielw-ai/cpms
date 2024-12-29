<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Debug logging
error_log("=== KPI Metrics Request ===");
error_log("Raw project parameter: " . print_r($_GET, true));

if (isset($_GET['project'])) {
    // Don't modify the project name if it's already formatted
    $tableName = strtolower($_GET['project']); // Force everything to lowercase
    error_log("Final table name to query: " . $tableName);
    
    try {
        // Log the query we're about to execute
        $query = "SELECT DISTINCT kpi_metrics FROM $tableName ORDER BY kpi_metrics";
        error_log("Executing query: " . $query);
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'data' => $metrics]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error fetching metrics']);
    }
} else {
    error_log("No project parameter received");
    echo json_encode(['success' => false, 'error' => 'Project not specified']);
}
?> 