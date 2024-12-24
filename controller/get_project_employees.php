<?php
require_once 'conn.php';

header('Content-Type: application/json');

if (isset($_GET['project'])) {
    try {
        $stmt = $conn->prepare("
            SELECT NIK, employee_name 
            FROM employee_active 
            WHERE project = ?
            ORDER BY employee_name
        ");
        
        $stmt->execute([$_GET['project']]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $employees
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Project parameter is required'
    ]);
} 