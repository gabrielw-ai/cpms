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
    if (!isset($_GET['project']) || !isset($_GET['kpi'])) {
        throw new Exception('Project and KPI metrics parameters are required');
    }

    $tableName = strtolower($_GET['project']); // Already has kpi_ prefix from the frontend
    $kpiMetrics = json_decode($_GET['kpi']);
    
    error_log("Looking for queues in table: " . $tableName);
    
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($checkTable->rowCount() === 0) {
        error_log("Table does not exist: {$tableName}");
        throw new PDOException("Table '{$tableName}' does not exist");
    }

    // Get distinct queues from the project's KPI table for selected metrics
    $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
    $sql = "SELECT DISTINCT queue FROM `{$tableName}` WHERE kpi_metrics IN ($placeholders) ORDER BY queue";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($kpiMetrics);
    $queues = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Found " . count($queues) . " queues in {$tableName}");
    
    echo json_encode([
        'success' => true,
        'data' => $queues
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error in get_project_queues.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
} 