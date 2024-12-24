<?php
require_once 'conn.php';

// Function to create role_mgmt table if it doesn't exist
function createRoleTable($conn) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS role_mgmt (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL UNIQUE
        )";
        
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating role table: " . $e->getMessage());
        return false;
    }
}

// Function to add new role
function addRole($conn, $role) {
    try {
        $sql = "INSERT INTO role_mgmt (role) VALUES (:role)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':role' => $role]);
    } catch (PDOException $e) {
        error_log("Error adding role: " . $e->getMessage());
        return false;
    }
}

// Function to update role
function updateRole($conn, $id, $role) {
    try {
        $sql = "UPDATE role_mgmt SET role = :role WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':id' => $id, ':role' => $role]);
    } catch (PDOException $e) {
        error_log("Error updating role: " . $e->getMessage());
        return false;
    }
}

// Function to delete role
function deleteRole($conn, $id) {
    try {
        $sql = "DELETE FROM role_mgmt WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error deleting role: " . $e->getMessage());
        return false;
    }
}

// Function to get all roles
function getAllRoles($conn) {
    try {
        $sql = "SELECT * FROM role_mgmt ORDER BY role";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting roles: " . $e->getMessage());
        return [];
    }
}

// Function to get role by ID
function getRoleById($conn, $id) {
    try {
        $sql = "SELECT * FROM role_mgmt WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting role: " . $e->getMessage());
        return null;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            if (!empty($_POST['role'])) {
                if (addRole($conn, $_POST['role'])) {
                    header('Location: ../view/role_mgmt.php?success=added');
                } else {
                    header('Location: ../view/role_mgmt.php?error=add_failed');
                }
            } else {
                header('Location: ../view/role_mgmt.php?error=role_required');
            }
            break;
            
        case 'update':
            if (!empty($_POST['id']) && !empty($_POST['role'])) {
                if (updateRole($conn, $_POST['id'], $_POST['role'])) {
                    header('Location: ../view/role_mgmt.php?success=updated');
                } else {
                    header('Location: ../view/role_mgmt.php?error=update_failed');
                }
            } else {
                header('Location: ../view/role_mgmt.php?error=invalid_data');
            }
            break;
            
        case 'delete':
            if (!empty($_POST['id'])) {
                if (deleteRole($conn, $_POST['id'])) {
                    header('Location: ../view/role_mgmt.php?success=deleted');
                } else {
                    header('Location: ../view/role_mgmt.php?error=delete_failed');
                }
            } else {
                header('Location: ../view/role_mgmt.php?error=invalid_id');
            }
            break;
    }
    exit;
}

// Create table if it doesn't exist
createRoleTable($conn);
?>
