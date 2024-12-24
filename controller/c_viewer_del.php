<?php
require_once 'conn.php';

if (isset($_POST['delete_kpi'])) {
    try {
        $tableName = $_POST['table_name'];
        $queue = $_POST['queue'];
        $kpi_metrics = $_POST['kpi_metrics'];
        $viewType = $_POST['view_type'];

        // Delete from weekly table
        $weeklyTable = $tableName;
        $stmt = $conn->prepare("DELETE FROM `$weeklyTable` WHERE queue = ? AND kpi_metrics = ?");
        $stmt->execute([$queue, $kpi_metrics]);

        // Delete from monthly table
        $monthlyTable = $tableName . "_MON";
        $stmt = $conn->prepare("DELETE FROM `$monthlyTable` WHERE queue = ? AND kpi_metrics = ?");
        $stmt->execute([$queue, $kpi_metrics]);

        header("Location: ../view/kpi_viewer.php?table=" . urlencode($tableName) . "&view=" . $viewType . "&message=KPI deleted successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: ../view/kpi_viewer.php?table=" . urlencode($tableName) . "&view=" . $viewType . "&error=" . urlencode($e->getMessage()));
        exit();
    }
}
?>
