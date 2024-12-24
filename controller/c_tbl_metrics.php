<?php
require_once 'conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to create tables for a project
function createProjectTables($conn, $baseTableName) {
    try {
        // Create weekly table (base table)
        $weeklySQL = "CREATE TABLE IF NOT EXISTS `$baseTableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            target VARCHAR(50) NOT NULL,
            target_type VARCHAR(20) NOT NULL,
            week1 DECIMAL(10,2) DEFAULT NULL,
            week2 DECIMAL(10,2) DEFAULT NULL,
            week3 DECIMAL(10,2) DEFAULT NULL,
            week4 DECIMAL(10,2) DEFAULT NULL,
            week5 DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_queue_kpi (queue, kpi_metrics)
        )";
        
        error_log("Creating weekly table with SQL: " . $weeklySQL);
        $conn->exec($weeklySQL);

        // Create monthly table
        $monthlyTableName = $baseTableName . "_MON";
        $monthlySQL = "CREATE TABLE IF NOT EXISTS `$monthlyTableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            target VARCHAR(50) NOT NULL,
            target_type VARCHAR(20) NOT NULL,
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
            UNIQUE KEY unique_queue_kpi (queue, kpi_metrics)
        )";
        
        error_log("Creating monthly table with SQL: " . $monthlySQL);
        $conn->exec($monthlySQL);

        // Create individual weekly table
        $individualWeeklyTable = $baseTableName . "_INDIVIDUAL";
        $individualWeeklySQL = "CREATE TABLE IF NOT EXISTS `$individualWeeklyTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            NIK VARCHAR(50) NOT NULL,
            employee_name VARCHAR(255) NOT NULL,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
            week1 DECIMAL(10,2) DEFAULT NULL,
            week2 DECIMAL(10,2) DEFAULT NULL,
            week3 DECIMAL(10,2) DEFAULT NULL,
            week4 DECIMAL(10,2) DEFAULT NULL,
            week5 DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_employee_kpi (NIK, queue, kpi_metrics)
        )";
        
        error_log("Creating individual weekly table with SQL: " . $individualWeeklySQL);
        $conn->exec($individualWeeklySQL);

        // Create individual monthly table
        $individualMonthlyTable = $baseTableName . "_INDIVIDUAL_MON";
        $individualMonthlySQL = "CREATE TABLE IF NOT EXISTS `$individualMonthlyTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            NIK VARCHAR(50) NOT NULL,
            employee_name VARCHAR(255) NOT NULL,
            queue VARCHAR(255) NOT NULL,
            kpi_metrics VARCHAR(255) NOT NULL,
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
            UNIQUE KEY unique_employee_kpi (NIK, queue, kpi_metrics)
        )";
        
        error_log("Creating individual monthly table with SQL: " . $individualMonthlySQL);
        $conn->exec($individualMonthlySQL);

        // Create weekly values table
        $weeklyValuesTable = $baseTableName . "_VALUES";
        $weeklyValuesSQL = "CREATE TABLE IF NOT EXISTS `$weeklyValuesTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_id INT NOT NULL,
            week INT NOT NULL,
            value DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_record (kpi_id, week),
            FOREIGN KEY (kpi_id) REFERENCES `$baseTableName`(id) ON DELETE CASCADE
        )";
        
        error_log("Creating weekly values table with SQL: " . $weeklyValuesSQL);
        $conn->exec($weeklyValuesSQL);

        // Create monthly values table
        $monthlyValuesTable = $baseTableName . "_MON_VALUES";
        $monthlyValuesSQL = "CREATE TABLE IF NOT EXISTS `$monthlyValuesTable` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_id INT NOT NULL,
            month INT NOT NULL,
            value DECIMAL(10,2) DEFAULT NULL,
            UNIQUE KEY unique_record (kpi_id, month),
            FOREIGN KEY (kpi_id) REFERENCES `{$baseTableName}_MON`(id) ON DELETE CASCADE
        )";
        
        error_log("Creating monthly values table with SQL: " . $monthlyValuesSQL);
        $conn->exec($monthlyValuesSQL);

        return true;
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
        throw $e;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        error_log("Processing form submission");
        error_log("POST data: " . print_r($_POST, true));
        
        // Validate required fields
        $requiredFields = ['project', 'queue', 'kpi_metrics', 'target', 'target_type'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Get the base table name for the selected project
        $baseTableName = "KPI_" . str_replace(" ", "_", strtoupper($_POST['project']));
        error_log("Base table name: " . $baseTableName);

        // Create both weekly and monthly tables
        createProjectTables($conn, $baseTableName);

        // Insert KPI definition into both tables
        $tables = [$baseTableName, $baseTableName . "_MON"];
        
        foreach ($tables as $tableName) {
            $stmt = $conn->prepare("
                INSERT INTO `$tableName` (queue, kpi_metrics, target, target_type) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['queue'],
                $_POST['kpi_metrics'],
                $_POST['target'],
                $_POST['target_type']
            ]);
            
            error_log("KPI definition inserted into $tableName");
        }

        // Redirect with success message
        header("Location: ../view/kpi_viewer.php?table=" . urlencode($baseTableName) . "&message=KPI created successfully");
        exit();
        
    } catch (Exception $e) {
        error_log("Error in form processing: " . $e->getMessage());
        header("Location: ../view/tbl_metrics.php?error=" . urlencode($e->getMessage()));
        exit();
    }

    // Add this to your existing POST handling section
    if ($_POST['action'] === 'update') {
        try {
            // Validate required fields
            $requiredFields = ['id', 'project', 'queue', 'kpi_metrics', 'target', 'target_type'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("$field is required");
                }
            }

            $tableName = "KPI_" . str_replace(" ", "_", strtoupper($_POST['project']));
            
            // Update KPI
            $stmt = $conn->prepare("
                UPDATE `$tableName` 
                SET queue = ?, 
                    kpi_metrics = ?, 
                    target = ?, 
                    target_type = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['queue'],
                $_POST['kpi_metrics'],
                $_POST['target'],
                $_POST['target_type'],
                $_POST['id']
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log("Error updating KPI: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>

