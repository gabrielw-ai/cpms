<?php
require_once 'conn.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON headers
header('Content-Type: application/json');

// Add this mapping at the top of the file
$monthMapping = [
    'jan' => 'january',
    'feb' => 'february',
    'mar' => 'march',
    'apr' => 'april',
    'may' => 'may',
    'jun' => 'june',
    'jul' => 'july',
    'aug' => 'august',
    'sep' => 'september',
    'oct' => 'october',
    'nov' => 'november',
    'dec' => 'december'
];

// Create individual_staging table if not exists
function createStagingTable($conn) {
    try {
        error_log("Creating individual_staging table");
        
        $sql = "CREATE TABLE IF NOT EXISTS individual_staging (
            id INT AUTO_INCREMENT PRIMARY KEY,
            NIK VARCHAR(50) NOT NULL,
            employee_name VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            queue VARCHAR(255) NOT NULL,
            january DECIMAL(10,2) DEFAULT NULL,
            february DECIMAL(10,2) DEFAULT NULL,
            march DECIMAL(10,2) DEFAULT NULL,
            april DECIMAL(10,2) DEFAULT NULL,
            may DECIMAL(10,2) DEFAULT NULL,
            june DECIMAL(10,2) DEFAULT NULL,
            july DECIMAL(10,2) DEFAULT NULL,
            august DECIMAL(10,2) DEFAULT NULL,
            september DECIMAL(10,2) DEFAULT NULL,
            october DECIMAL(10,2) DEFAULT NULL,
            november DECIMAL(10,2) DEFAULT NULL,
            december DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_employee_kpi (NIK, kpi_metrics, queue)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
        error_log("individual_staging table created/verified successfully");
        return true;
    } catch (PDOException $e) {
        error_log("Error creating individual_staging table: " . $e->getMessage());
        throw $e;
    }
}

// Ensure staging table exists before processing any requests
createStagingTable($conn);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the raw POST data
        $rawInput = file_get_contents('php://input');
        error_log("Raw input received: " . $rawInput);
        
        // Try to decode JSON input
        $jsonInput = json_decode($rawInput, true);
        
        // Get action from either POST or JSON input
        $action = isset($_POST['action']) ? $_POST['action'] : ($jsonInput['action'] ?? null);
        
        if ($action === 'get_data') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $project = $input['project'];
                $kpiMetrics = $input['kpi_metrics'] ?? [];
                $queues = $input['queues'] ?? [];
                
                // Get the individual monthly table name for the project
                $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project)) . "_INDIVIDUAL_MON";
                
                error_log("Using table: " . $tableName);
                
                // Build query with filters
                $sql = "SELECT * FROM `$tableName` WHERE 1=1";
                $params = [];

                if (!empty($kpiMetrics)) {
                    $placeholders = str_repeat('?,', count($kpiMetrics) - 1) . '?';
                    $sql .= " AND kpi_metrics IN ($placeholders)";
                    $params = array_merge($params, $kpiMetrics);
                }

                if (!empty($queues)) {
                    $placeholders = str_repeat('?,', count($queues) - 1) . '?';
                    $sql .= " AND queue IN ($placeholders)";
                    $params = array_merge($params, $queues);
                }

                $sql .= " ORDER BY NIK, kpi_metrics, queue";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $data
                ]);
                
            } catch (Exception $e) {
                error_log("Error in get_data: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        } else if ($action === 'add') {
            try {
                // Check if record exists
                $stmt = $conn->prepare("
                    SELECT id FROM individual_staging 
                    WHERE NIK = ? AND kpi_metrics = ? AND queue = ?
                ");
                $stmt->execute([
                    $_POST['nik'],
                    $_POST['kpi_metrics'],
                    $_POST['queue']
                ]);
                
                $exists = $stmt->fetch();
                $month = $monthMapping[$_POST['month']];
                
                if ($exists) {
                    // Update existing record
                    $sql = "UPDATE individual_staging SET 
                            employee_name = ?, 
                            $month = ? 
                            WHERE NIK = ? AND kpi_metrics = ? AND queue = ?";
                    $params = [
                        $_POST['name'],
                        $_POST['value'],
                        $_POST['nik'],
                        $_POST['kpi_metrics'],
                        $_POST['queue']
                    ];
                } else {
                    // Insert new record
                    $sql = "INSERT INTO individual_staging 
                            (NIK, employee_name, kpi_metrics, queue, $month) 
                            VALUES (?, ?, ?, ?, ?)";
                    $params = [
                        $_POST['nik'],
                        $_POST['name'],
                        $_POST['kpi_metrics'],
                        $_POST['queue'],
                        $_POST['value']
                    ];
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                echo json_encode(['success' => true]);
                
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        } else if ($action === 'add_multiple') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                $project = $input['project'];
                $combinations = $input['data'];
                
                // Get the individual monthly table name for the project
                $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project)) . "_INDIVIDUAL_MON";
                
                $conn->beginTransaction();
                
                foreach ($combinations as $item) {
                    // Check if record exists
                    $stmt = $conn->prepare("
                        SELECT id FROM `$tableName` 
                        WHERE NIK = ? AND kpi_metrics = ? AND queue = ?
                    ");
                    $stmt->execute([
                        $item['nik'],
                        $item['kpi_metrics'],
                        $item['queue']
                    ]);
                    
                    $exists = $stmt->fetch();
                    $month = $monthMapping[$item['month']];
                    
                    if ($exists) {
                        // Update existing record
                        $sql = "UPDATE `$tableName` SET 
                                employee_name = ?, 
                                $month = ? 
                                WHERE NIK = ? AND kpi_metrics = ? AND queue = ?";
                        $params = [
                            $item['name'],
                            $item['value'],
                            $item['nik'],
                            $item['kpi_metrics'],
                            $item['queue']
                        ];
                    } else {
                        // Insert new record
                        $sql = "INSERT INTO `$tableName` 
                                (NIK, employee_name, kpi_metrics, queue, $month) 
                                VALUES (?, ?, ?, ?, ?)";
                        $params = [
                            $item['nik'],
                            $item['name'],
                            $item['kpi_metrics'],
                            $item['queue'],
                            $item['value']
                        ];
                    }
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                }
                
                $conn->commit();
                echo json_encode(['success' => true]);
                
            } catch (PDOException $e) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Critical error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>
