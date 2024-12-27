<?php
session_start();
require_once dirname(__DIR__) . '/controller/conn.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['id', 'case_chronology', 'consequences', 
                       'effective_date', 'end_date'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Handle file upload if new file is provided
    $supporting_doc_sql = '';
    $params = [
        ':id' => $_POST['id'],
        ':case_chronology' => $_POST['case_chronology'],
        ':consequences' => $_POST['consequences'],
        ':effective_date' => $_POST['effective_date'],
        ':end_date' => $_POST['end_date']
    ];

    if (!empty($_FILES['supporting_doc']['name'])) {
        $upload_dir = 'uploads/supporting_docs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['supporting_doc']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('doc_') . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['supporting_doc']['tmp_name'], $file_path)) {
            $supporting_doc_sql = ', supporting_doc_url = :supporting_doc_url';
            $params[':supporting_doc_url'] = $file_path;
        }
    }

    $sql = "UPDATE ccs_rules SET 
            case_chronology = :case_chronology,
            consequences = :consequences,
            effective_date = :effective_date,
            end_date = :end_date" . 
            $supporting_doc_sql . 
            " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Rule updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update rule');
    }

} catch (Exception $e) {
    error_log("Error in c_viewer_update.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
