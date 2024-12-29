<?php
require_once 'controller/conn.php';

if (isset($_GET['pj_name']) && isset($_GET['queue'])) {
    try {
        $pj_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['pj_name']);
        $tableName = "kpi_" . strtolower($pj_name);
        
        $stmt = $conn->prepare("SELECT DISTINCT kpi_metrics FROM `$tableName` WHERE queue = ?");
        $stmt->execute([$_GET['queue']]);
        
        $metrics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        header('Content-Type: application/json');
        echo json_encode($metrics);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 