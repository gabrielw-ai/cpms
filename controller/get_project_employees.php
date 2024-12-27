<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent any output before JSON
ob_clean();

require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Starting get_project_employees.php");

// Ensure clean headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if (!isset($_GET['project'])) {
        throw new Exception('Project parameter is required');
    }

    $project = $_GET['project'];
    $currentUserNIK = $_SESSION['user_nik'] ?? '';
    
    error_log("Project requested: " . $project);
    error_log("Current user NIK: " . $currentUserNIK);

    // Query excluding the current user's NIK
    $sql = "SELECT NIK, employee_name 
            FROM employee_active 
            WHERE project = :project 
            AND NIK != :current_user
            ORDER BY employee_name";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':project', $project);
    $stmt->bindParam(':current_user', $currentUserNIK);
    
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found employees: " . print_r($employees, true));

    echo json_encode([
        'success' => true,
        'data' => $employees
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error in get_project_employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
