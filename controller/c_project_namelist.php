<?php
require_once 'conn.php';

// Create table if it doesn't exist
try {
    $sql = "CREATE TABLE IF NOT EXISTS project_namelist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        main_project VARCHAR(255) NOT NULL,
        project_name VARCHAR(255) NOT NULL,
        unit_name VARCHAR(255) NOT NULL,
        job_code VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}

// Add new project
if (isset($_POST['add_project'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO project_namelist (main_project, project_name, unit_name, job_code) 
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['main_project'],
            $_POST['project_name'],
            $_POST['unit_name'],
            $_POST['job_code']
        ]);
        
        header("Location: ../view/project_namelist.php?message=Project added successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: ../view/project_namelist.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Update project
if (isset($_POST['update_project'])) {
    try {
        $stmt = $conn->prepare("UPDATE project_namelist 
                               SET main_project = ?, project_name = ?, unit_name = ?, job_code = ? 
                               WHERE id = ?");
        $stmt->execute([
            $_POST['main_project'],
            $_POST['project_name'],
            $_POST['unit_name'],
            $_POST['job_code'],
            $_POST['edit_id']
        ]);
        
        header("Location: ../view/project_namelist.php?message=Project updated successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: ../view/project_namelist.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Delete project
if (isset($_GET['delete_project'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM project_namelist WHERE id = ?");
        $stmt->execute([$_GET['delete_project']]);
        
        header("Location: ../view/project_namelist.php?message=Project deleted successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: ../view/project_namelist.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Get project data for editing
if (isset($_GET['get_project'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM project_namelist WHERE id = ?");
        $stmt->execute([$_GET['get_project']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($project);
        exit();
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
