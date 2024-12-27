<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['id'])) {
        throw new Exception('Missing rule ID');
    }

    $sql = "DELETE FROM ccs_rules WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([':id' => $_POST['id']]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Rule deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete rule');
    }

} catch (Exception $e) {
    error_log("Error in c_viewer_del.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
