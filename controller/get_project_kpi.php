<?php
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Ensure clean output
ob_clean();
header('Content-Type: application/json');

if (isset($_GET['project'])) {
    $tableName = strtolower($_GET['project']);
    
    try {
        // First check if table exists
        $checkTable = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        if ($checkTable->rowCount() === 0) {
            error_log("Table does not exist: {$tableName}");
            throw new PDOException("Table '{$tableName}' does not exist");
        }

        error_log("Found table: {$tableName}, fetching metrics...");
        
        $stmt = $conn->prepare("SELECT DISTINCT kpi_metrics FROM `{$tableName}` ORDER BY kpi_metrics");
        $stmt->execute();
        $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Successfully fetched " . count($metrics) . " metrics from {$tableName}");
        
        // Ensure clean JSON response
        $response = ['success' => true, 'data' => $metrics];
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        error_log("Database error for table {$tableName}: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Error fetching metrics: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Project not specified']);
    exit;
} 