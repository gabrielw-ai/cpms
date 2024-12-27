<?php
session_start();

// Include required files
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Create Router instance to use url() method
$router = new Router();

// Ensure clean output
if (ob_get_level()) ob_end_clean();

// Ensure no output before JSON response
header('Content-Type: application/json');

// Add debugging
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('GET params: ' . print_r($_GET, true));
error_log('POST params: ' . print_r($_POST, true));

// Verify connection
if (!isset($conn)) {
    error_log('Database connection not established');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Handle GET requests (fetch and delete)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['get_project'])) {
            try {
                // Fetch project details
                $stmt = $conn->prepare("SELECT * FROM project_namelist WHERE id = ?");
                $stmt->execute([$_GET['get_project']]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($project) {
                    // Clear any output buffer
                    if (ob_get_level()) ob_end_clean();
                    
                    header('Content-Type: application/json');
                    echo json_encode($project);
                    exit;
                } else {
                    throw new Exception("Project not found");
                }
            } catch (Exception $e) {
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        }
        else if (isset($_GET['delete_project'])) {
            error_log('Attempting to delete project: ' . $_GET['delete_project']);
            
            // Delete project
            $stmt = $conn->prepare("DELETE FROM project_namelist WHERE id = ?");
            $result = $stmt->execute([$_GET['delete_project']]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Project deleted successfully'
                ]);
            } else {
                throw new Exception("Failed to delete project");
            }
            exit; // Ensure no additional output
        }
    }
    // Handle POST requests (add and edit)
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_project'])) {
            $stmt = $conn->prepare("INSERT INTO project_namelist (main_project, project_name, unit_name, job_code) 
                                  VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([
                $_POST['main_project'],
                $_POST['project_name'],
                $_POST['unit_name'],
                $_POST['job_code']
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Project added successfully';
                header('Location: ' . $router->url('projects'));
                exit;
            } else {
                throw new Exception("Failed to add project");
            }
        }
        else if (isset($_POST['update_project'])) {
            // Clear any output buffer first
            if (ob_get_level()) ob_end_clean();
            
            try {
                // Update project
                $stmt = $conn->prepare("UPDATE project_namelist 
                                      SET main_project = ?, project_name = ?, unit_name = ?, job_code = ? 
                                      WHERE id = ?");
                $result = $stmt->execute([
                    $_POST['main_project'],
                    $_POST['project_name'],
                    $_POST['unit_name'],
                    $_POST['job_code'],
                    $_POST['edit_id']
                ]);
                
                if ($result) {
                    $_SESSION['success_message'] = 'Project updated successfully';
                    header('Location: ' . $router->url('projects'));
                    exit;
                } else {
                    throw new Exception("Failed to update project");
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: ' . $router->url('projects'));
                exit;
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ' . $router->url('projects'));
    exit;
}

// If we get here, redirect back with error
$_SESSION['error_message'] = 'Invalid request';
header('Location: ' . $router->url('projects'));
exit;
?>
