<?php
require_once 'conn.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->query("SELECT nik, employee_name FROM employee_active ORDER BY employee_name");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($employees);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 