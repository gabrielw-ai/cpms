<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/controller/conn.php';

// Only set JSON header for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                // Prevent self-assignment of CCS rules
                if ($_POST['nik'] === $_SESSION['user_nik']) {
                    throw new Exception("You cannot assign CCS rules to yourself");
                }

                // Handle adding new rule
                $required_fields = ['project', 'nik', 'name', 'role', 'case_chronology', 'ccs_rule', 'effective_date'];
                foreach ($required_fields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }

                // Handle file upload
                $document_path = null;
                if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = dirname(__DIR__) . '/uploads/ccs_docs/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                    $new_filename = uniqid('doc_') . '.' . $file_extension;
                    $document_path = 'uploads/ccs_docs/' . $new_filename;

                    if (!move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $new_filename)) {
                        throw new Exception("Error uploading file");
                    }
                }

                // Calculate end date based on CCS rule
                $effective_date = new DateTime($_POST['effective_date']);
                $end_date = clone $effective_date;
                
                if (strpos($_POST['ccs_rule'], 'WR') === 0) {
                    $end_date->modify('+1 year -1 day');
                } else {
                    $end_date->modify('+6 months -1 day');
                }

                // Strip 'kpi_' prefix from project name for database storage
                $projectName = preg_replace('/^kpi_/', '', $_POST['project']);
                
                // Insert new rule
                $stmt = $conn->prepare("INSERT INTO ccs_rules (project, nik, name, role, tenure, case_chronology, 
                                      consequences, effective_date, end_date, supporting_doc_url, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                
                if ($stmt->execute([
                    $projectName,  // Store without 'kpi_' prefix
                    $_POST['nik'],
                    $_POST['name'],
                    $_POST['role'],
                    $_POST['tenure'],
                    $_POST['case_chronology'],
                    $_POST['ccs_rule'],
                    $_POST['effective_date'],
                    $end_date->format('Y-m-d'),
                    $document_path
                ])) {
                    if (isAjaxRequest()) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Rule added successfully'
                        ]);
                    } else {
                        $_SESSION['success'] = "Rule added successfully";
                        header('Location: ' . $_SERVER['HTTP_REFERER']);
                    }
                    exit;
                } else {
                    throw new Exception("Error adding rule");
                }
                    break;

                case 'edit':
                // Validate required fields
                $required_fields = ['id', 'project', 'case_chronology', 'consequences', 'effective_date', 'end_date'];
                foreach ($required_fields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }

                try {
                    // Begin transaction
                    $conn->beginTransaction();

                    // Get the data
                    $id = $_POST['id'];
                    $project = $_POST['project'];
                    $case_chronology = $_POST['case_chronology'];
                    $consequences = $_POST['consequences'];
                    $effective_date = $_POST['effective_date'];
                    $end_date = $_POST['end_date'];

                    // Handle file upload if present
                    $document_path = null;
                    if (isset($_FILES['supporting_doc']) && $_FILES['supporting_doc']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = dirname(__DIR__) . '/uploads/ccs_docs/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_extension = strtolower(pathinfo($_FILES['supporting_doc']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid('doc_') . '.' . $file_extension;
                        $document_path = 'uploads/ccs_docs/' . $new_filename;

                        if (!move_uploaded_file($_FILES['supporting_doc']['tmp_name'], $upload_dir . $new_filename)) {
                            throw new Exception("Error uploading file");
                        }
                    }

                    // Prepare the SQL query
                    $sql = "UPDATE ccs_rules SET 
                            case_chronology = :case_chronology,
                            consequences = :consequences,
                            effective_date = :effective_date,
                            end_date = :end_date";

                    if ($document_path !== null) {
                        $sql .= ", supporting_doc_url = :document_path";
                    }

                    $sql .= " WHERE id = :id AND project = :project";

                    $stmt = $conn->prepare($sql);
                    $params = [
                        ':id' => $id,
                        ':project' => $project,
                        ':case_chronology' => $case_chronology,
                        ':consequences' => $consequences,
                        ':effective_date' => $effective_date,
                        ':end_date' => $end_date
                    ];

                    if ($document_path !== null) {
                        $params[':document_path'] = $document_path;
                    }

                    // Execute update
                    $stmt->execute($params);
                    
                    // Commit transaction
                    $conn->commit();

                    // Clean output buffer and set headers
                    ob_clean();
                    header('Content-Type: application/json');
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rule updated successfully'
                    ]);
                    exit;

                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollBack();
                    
                    // Clean output buffer and set headers
                    ob_clean();
                    header('Content-Type: application/json');
                    
                    echo json_encode([
                        'success' => false,
                        'message' => "Error updating rule: " . $e->getMessage()
                    ]);
                    exit;
                }
                break;

            case 'delete':
                error_log("Delete request received: " . print_r($_POST, true));
                
                // Validate required fields
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    error_log("Missing ID parameter");
                    throw new Exception("Missing rule ID");
                }
                if (!isset($_POST['project']) || empty($_POST['project'])) {
                    error_log("Missing project parameter");
                    throw new Exception("Missing project parameter");
                }

                try {
                    // Begin transaction
                    $conn->beginTransaction();
                    error_log("Starting delete transaction for ID: {$_POST['id']}, Project: {$_POST['project']}");
                    
                    // First check if rule exists
                    $checkStmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ? AND project = ?");
                    $checkStmt->execute([$_POST['id'], $_POST['project']]);
                    $rule = $checkStmt->fetch();

                    if (!$rule) {
                        throw new Exception("Rule not found");
                    }

                    // Delete supporting document if exists
                    if (!empty($rule['supporting_doc_url'])) {
                        $docPath = dirname(__DIR__) . '/' . $rule['supporting_doc_url'];
                        if (file_exists($docPath)) {
                            unlink($docPath);
                        }
                    }

                    // Delete the rule
                    $deleteStmt = $conn->prepare("DELETE FROM ccs_rules WHERE id = ? AND project = ?");
                    $deleteStmt->execute([$_POST['id'], $_POST['project']]);

                    // Commit transaction
                    $conn->commit();

                    // Make sure there's no whitespace or output before this
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rule deleted successfully'
                    ]);
                    exit;
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollBack();
                    
                    // Make sure there's no whitespace or output before this
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "Error deleting rule: " . $e->getMessage()
                    ]);
                    exit;
                }
                break;

            default:
                throw new Exception("Invalid action");
        }
    }
} catch (Exception $e) {
    if (isAjaxRequest()) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
        exit;
    }

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Helper function to get rule by ID
function getRuleById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
