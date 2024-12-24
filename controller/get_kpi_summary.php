<?php
require_once 'conn.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    $project = $_GET['project'];
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project));

    // Debug log
    error_log("Checking table: " . $tableName);

    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    error_log("Table exists: " . ($tableExists ? 'Yes' : 'No'));

    if (!$tableExists) {
        echo json_encode(['success' => true, 'kpis' => []]);
        exit;
    }

    // Get KPI data
    $sql = "SELECT id, queue, kpi_metrics, target, target_type 
            FROM `$tableName` 
            ORDER BY queue, kpi_metrics";
    
    error_log("Executing query: " . $sql);
    
    $stmt = $conn->query($sql);
    $kpis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found KPIs: " . count($kpis));

    $response = [
        'success' => true,
        'kpis' => $kpis
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_kpi_summary: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 