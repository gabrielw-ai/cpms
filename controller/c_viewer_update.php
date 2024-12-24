<?php
require_once 'conn.php';
header('Content-Type: application/json');

try {
    // Check required parameters
    if (!isset($_POST['id']) || !isset($_POST['project'])) {
        throw new Exception('Missing required parameters');
    }

    $id = $_POST['id'];
    $project = $_POST['project'];
    $queue = $_POST['queue'] ?? '';
    $kpi_metrics = $_POST['kpi_metrics'] ?? '';
    $target = $_POST['target'] ?? '';
    $target_type = $_POST['target_type'] ?? '';

    // Create table name
    $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project));

    // Update KPI
    $sql = "UPDATE `$tableName` SET 
            queue = :queue,
            kpi_metrics = :kpi_metrics,
            target = :target,
            target_type = :target_type
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':queue' => $queue,
        ':kpi_metrics' => $kpi_metrics,
        ':target' => $target,
        ':target_type' => $target_type,
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'KPI updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No changes made or KPI not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
