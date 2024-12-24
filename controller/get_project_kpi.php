<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['project'])) {
    try {
        error_log("Fetching KPI metrics for project: " . $_GET['project']);
        
        // Convert project name to table name format (e.g., "KPI_GEC_ST")
        $tableName = "KPI_" . str_replace(" ", "_", strtoupper($_GET['project']));
        error_log("Looking in table: " . $tableName);
        
        // First check if table exists
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ");
        $checkStmt->execute([$tableName]);
        $tableExists = $checkStmt->fetchColumn() > 0;
        
        if ($tableExists) {
            // Get distinct KPI metrics from the project table
            $sql = "SELECT DISTINCT kpi_metrics FROM " . $tableName . " ORDER BY kpi_metrics";
            $stmt = $conn->query($sql);
            $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            error_log("Found metrics: " . print_r($metrics, true));
            
            if (!empty($metrics)) {
                echo json_encode([
                    'success' => true,
                    'metrics' => $metrics
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'No KPI metrics found in table',
                    'table' => $tableName
                ]);
            }
        } else {
            error_log("Table does not exist: " . $tableName);
            echo json_encode([
                'success' => false,
                'error' => 'No KPI table found for selected project',
                'table' => $tableName
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'sqlState' => $e->getCode()
        ]);
    }
} else {
    error_log("Project parameter missing");
    echo json_encode([
        'success' => false,
        'error' => 'Project parameter is required'
    ]);
} 