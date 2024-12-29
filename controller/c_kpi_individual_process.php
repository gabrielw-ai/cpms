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
    if (!isset($input['project'])) {
        throw new Exception('Project parameter is required');
    }

    $tableName = $input['project'] . '_individual_mon'; // Just append suffix, project already has kpi_ prefix
    
    error_log("Looking for data in table: " . $tableName);

    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
    if (!$tableExists) {
        throw new Exception('No data found for this project');
    }

    // Prepare placeholders for the IN clauses
    $metricPlaceholders = str_repeat('?,', count($input['metrics']) - 1) . '?';
    $queuePlaceholders = str_repeat('?,', count($input['queues']) - 1) . '?';

    // Build the query with explicit column selection
    $sql = "SELECT 
            nik,
            employee_name,
            kpi_metrics,
            queue,
            january,
            february,
            march,
            april,
            may,
            june,
            july,
            august,
            september,
            october,
            november,
            december
        FROM `$tableName` 
        WHERE kpi_metrics IN ($metricPlaceholders) 
        AND queue IN ($queuePlaceholders)
        ORDER BY nik, kpi_metrics, queue";

    error_log("SQL Query: " . $sql);
    error_log("Metrics: " . print_r($input['metrics'], true));
    error_log("Queues: " . print_r($input['queues'], true));

    $stmt = $conn->prepare($sql);
    
    // Combine parameters in the correct order
    $params = array_merge($input['metrics'], $input['queues']);
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
        // Normalize data keys to match DataTable columns
        $normalizedData = array_map(function($row) {
            return [
                'nik' => $row['nik'],
                'employee_name' => $row['employee_name'],
                'kpi_metrics' => $row['kpi_metrics'],
                'queue' => $row['queue'],
                'january' => $row['january'] ?? '-',
                'february' => $row['february'] ?? '-',
                'march' => $row['march'] ?? '-',
                'april' => $row['april'] ?? '-',
                'may' => $row['may'] ?? '-',
                'june' => $row['june'] ?? '-',
                'july' => $row['july'] ?? '-',
                'august' => $row['august'] ?? '-',
                'september' => $row['september'] ?? '-',
                'october' => $row['october'] ?? '-',
                'november' => $row['november'] ?? '-',
                'december' => $row['december'] ?? '-'
            ];
        }, $data);

        // Debug the final data
        error_log("Final normalized data: " . print_r($normalizedData, true));

        echo json_encode([
            'success' => true,
            'data' => $normalizedData
        ]);
    }
    exit;

} catch (Exception $e) {
    error_log("Error processing data: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 