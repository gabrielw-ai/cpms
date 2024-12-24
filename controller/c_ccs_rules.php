<?php
// Change session_start() to check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'conn.php';

// Drop and recreate table with status column
try {
    // Create table only if it doesn't exist (remove DROP TABLE)
    $sql = "CREATE TABLE IF NOT EXISTS ccs_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        nik VARCHAR(50) NOT NULL,
        role VARCHAR(100) NOT NULL,
        tenure VARCHAR(100) NOT NULL,
        case_chronology TEXT,
        consequences VARCHAR(50) NOT NULL,
        effective_date DATE NOT NULL,
        end_date DATE NOT NULL,
        supporting_doc_url VARCHAR(255),
        status ENUM('active', 'deactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
} catch(PDOException $e) {
    error_log("Error creating table: " . $e->getMessage());
}

// Add this function to update status based on end_date
function updateCCSRuleStatus($conn) {
    try {
        // Set timezone to GMT+7
        date_default_timezone_set('Asia/Bangkok');
        
        $sql = "UPDATE ccs_rules 
                SET status = CASE 
                    WHEN end_date < CURDATE() THEN 'deactive' 
                    ELSE 'active' 
                END";
        $conn->exec($sql);
    } catch(PDOException $e) {
        error_log("Error updating status: " . $e->getMessage());
    }
}

// Add these functions after updateCCSRuleStatus function
function deleteRule($conn, $id) {
    try {
        // First get the document URL to delete the file
        $stmt = $conn->prepare("SELECT supporting_doc_url FROM ccs_rules WHERE id = ?");
        $stmt->execute([$id]);
        $docUrl = $stmt->fetchColumn();

        // Delete the physical file if exists
        if ($docUrl) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $docUrl;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete the record
        $stmt = $conn->prepare("DELETE FROM ccs_rules WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting rule: " . $e->getMessage());
        return false;
    }
}

function getRuleById($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM ccs_rules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting rule: " . $e->getMessage());
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'delete':
                    if (empty($_POST['id'])) {
                        throw new Exception('Rule ID is required');
                    }
                    if (deleteRule($conn, $_POST['id'])) {
                        $_SESSION['success'] = "Rule deleted successfully";
                    } else {
                        throw new Exception('Failed to delete rule');
                    }
                    header('Location: ../view/ccs_viewer.php');
                    exit;
                    break;

                case 'edit':
                    if (empty($_POST['id'])) {
                        throw new Exception('Rule ID is required');
                    }

                    // Handle file upload if new file is provided
                    $supportingDoc = null;
                    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                        // Delete old file first
                        $stmt = $conn->prepare("SELECT supporting_doc_url FROM ccs_rules WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        $oldDoc = $stmt->fetchColumn();
                        if ($oldDoc) {
                            $oldPath = $_SERVER['DOCUMENT_ROOT'] . $oldDoc;
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        // Upload new file
                        $fileExtension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                        $fileName = uniqid() . '_' . date('Ymd') . '.' . $fileExtension;
                        $uploadFile = $uploadDir . $fileName;

                        if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadFile)) {
                            $supportingDoc = '/storage/ccs_docs/' . $fileName;
                        }
                    }

                    // Calculate new end date
                    $effectiveDate = new DateTime($_POST['effective_date']);
                    $endDate = clone $effectiveDate;
                    if (strpos($_POST['ccs_rule'], 'WR') === 0) {
                        // Written Reminder: 1 year
                        $endDate->modify('+1 year -1 day');
                    } else {
                        // Warning Letter: 6 months
                        $endDate->modify('+6 months -1 day');
                    }

                    // Update the record
                    $sql = "UPDATE ccs_rules SET 
                            project = :project,
                            case_chronology = :case_chronology,
                            consequences = :consequences,
                            effective_date = :effective_date,
                            end_date = :end_date" .
                            ($supportingDoc ? ", supporting_doc_url = :supporting_doc_url" : "") .
                            " WHERE id = :id";

                    $params = [
                        ':project' => $_POST['project'],
                        ':case_chronology' => $_POST['case_chronology'],
                        ':consequences' => $_POST['ccs_rule'],
                        ':effective_date' => $_POST['effective_date'],
                        ':end_date' => $endDate->format('Y-m-d'),
                        ':id' => $_POST['id']
                    ];

                    if ($supportingDoc) {
                        $params[':supporting_doc_url'] = $supportingDoc;
                    }

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);

                    // Update status after edit
                    updateCCSRuleStatus($conn);

                    $_SESSION['success'] = "Rule updated successfully";
                    header('Location: ../view/ccs_viewer.php');
                    exit;
                    break;

                default:
                    // Handle new rule creation (existing logic)
                    break;
            }
        }
        // Validate NIK first
        if (empty($_POST['nik'])) {
            throw new Exception('Employee NIK is required');
        }

        // Get employee name from employee_active table using NIK
        $stmt = $conn->prepare("SELECT employee_name FROM employee_active WHERE NIK = ?");
        $stmt->execute([$_POST['nik']]);
        $employeeName = $stmt->fetchColumn();

        if (!$employeeName) {
            throw new Exception('Employee not found');
        }

        // Handle file upload first
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/ccs_docs/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $supportingDoc = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . date('Ymd') . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadFile)) {
                $supportingDoc = '/storage/ccs_docs/' . $fileName;
            } else {
                throw new Exception('Failed to upload file');
            }
        }

        // Calculate end date based on CCS rule
        $effectiveDate = new DateTime($_POST['effective_date']);
        $endDate = clone $effectiveDate;
        
        if (strpos($_POST['ccs_rule'], 'WR') === 0) {
            // Written Reminder: 1 year
            $endDate->modify('+1 year -1 day');
        } else {
            // Warning Letter: 6 months
            $endDate->modify('+6 months -1 day');
        }

        // Insert into database using the fetched employee name
        $sql = "INSERT INTO ccs_rules (
            project, name, nik, role, tenure, case_chronology, 
            consequences, effective_date, end_date, supporting_doc_url, status
        ) VALUES (
            :project, :name, :nik, :role, :tenure, :case_chronology,
            :consequences, :effective_date, :end_date, :supporting_doc_url, :status
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':project' => $_POST['project'],
            ':name' => $employeeName,
            ':nik' => $_POST['nik'],
            ':role' => $_POST['role'],
            ':tenure' => $_POST['tenure'],
            ':case_chronology' => $_POST['case_chronology'],
            ':consequences' => $_POST['ccs_rule'],
            ':effective_date' => $_POST['effective_date'],
            ':end_date' => $endDate->format('Y-m-d'),
            ':supporting_doc_url' => $supportingDoc,
            ':status' => 'active'
        ]);

        // After successful insert, update statuses
        updateCCSRuleStatus($conn);

        $_SESSION['success'] = "CCS Rule added successfully";
        header('Location: ../view/ccs_rules_mgmt.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ../view/ccs_rules_mgmt.php');
        exit;
    }
} else {
    // Update statuses when viewing
    updateCCSRuleStatus($conn);
}
?>
