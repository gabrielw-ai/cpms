<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['project']) && isset($_GET['kpi'])) {
    try {
        // Debug logging
        error_log("Fetching queues for project: " . $_GET['project']);
        error_log("KPI metrics received: " . print_r($_GET['kpi'], true));
        
        // Get table name for the project
        $tableName = "KPI_" . str_replace(" ", "_", strtoupper($_GET['project'])) . "_MON";
        error_log("Looking in table: " . $tableName);

        // Check if table exists
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ");
        $stmt->execute([$tableName]);
        $tableExists = $stmt->fetchColumn() > 0;

        if (!$tableExists) {
            throw new Exception("Table $tableName does not exist");
        }

        // Since kpi_metrics is now an array, we need to modify the query
        $kpiMetrics = json_decode($_GET['kpi']);
        if (!is_array($kpiMetrics)) {
            throw new Exception("Invalid KPI metrics format");
        }

        // Create placeholders for the IN clause
        $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
        
        $sql = "SELECT DISTINCT queue 
                FROM `$tableName` 
                WHERE kpi_metrics IN ($placeholders)
                ORDER BY queue";
                
        error_log("Executing SQL: " . $sql);
        error_log("With parameters: " . print_r($kpiMetrics, true));

        $stmt = $conn->prepare($sql);
        $stmt->execute($kpiMetrics);
        $queues = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Found queues: " . print_r($queues, true));
        
        if (!empty($queues)) {
            echo json_encode([
                'success' => true,
                'queues' => $queues
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No queues found for selected KPI metrics',
                'debug' => [
                    'table' => $tableName,
                    'kpi_metrics' => $kpiMetrics
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'debug' => [
                'sql_state' => $e->getCode(),
                'error_info' => $e->errorInfo
            ]
        ]);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    error_log("Missing required parameters");
    echo json_encode([
        'success' => false,
        'error' => 'Project and KPI parameters are required',
        'debug' => [
            'received' => $_GET
        ]
    ]);
} 