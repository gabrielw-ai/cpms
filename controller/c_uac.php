<?php
// Change session_start() to check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conn.php';

// Create or update UAC table with correct columns
try {
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'uac'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create new table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS uac (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL,
            menu_access TEXT NOT NULL,
            `read` ENUM('0','1') DEFAULT '0',
            `write` ENUM('0','1') DEFAULT '0',
            `delete` ENUM('0','1') DEFAULT '0',
            created_by VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_by VARCHAR(50),
            updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role (role_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
    } else {
        // Check and add columns if they don't exist
        $columns = $conn->query("SHOW COLUMNS FROM uac")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('delete', $columns)) {
            $conn->exec("ALTER TABLE uac ADD COLUMN `delete` ENUM('0','1') DEFAULT '0' AFTER `write`");
        }
    }
} catch(PDOException $e) {
    error_log("Error managing UAC table: " . $e->getMessage());
}

// Function to get all UAC entries
function getAllUAC($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM uac ORDER BY role_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting UAC entries: " . $e->getMessage());
        return [];
    }
}

// Function to add new UAC entry
function addUAC($conn, $data) {
    try {
        error_log("Adding UAC with data: " . print_r($data, true));
        
        $sql = "INSERT INTO uac (
            role_name, 
            menu_access, 
            `read`,
            `write`,
            `delete`,
            created_by
        ) VALUES (
            :role_name, 
            :menu_access, 
            :read,
            :write,
            :delete,
            :created_by
        )";
        
        $params = [
            ':role_name' => $data['role_name'],
            ':menu_access' => json_encode($data['menu_access']),
            ':read' => $data['read'] ?? '0',
            ':write' => $data['write'] ?? '0',
            ':delete' => $data['delete'] ?? '0',
            ':created_by' => $_SESSION['user_nik']
        ];
        
        error_log("SQL: " . $sql);
        error_log("Parameters: " . print_r($params, true));
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$result) {
            error_log("SQL Error Info: " . print_r($stmt->errorInfo(), true));
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("PDO Error in addUAC: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        error_log("General Error in addUAC: " . $e->getMessage());
        throw $e;
    }
}

// Function to update UAC entry
function updateUAC($conn, $id, $data) {
    try {
        $sql = "UPDATE uac SET 
                role_name = :role_name,
                menu_access = :menu_access,
                `read` = :read,
                `write` = :write,
                `delete` = :delete,
                updated_by = :updated_by
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':role_name' => $data['role_name'],
            ':menu_access' => json_encode($data['menu_access']),
            ':read' => $data['read'] ?? '0',
            ':write' => $data['write'] ?? '0',
            ':delete' => $data['delete'] ?? '0',
            ':updated_by' => $_SESSION['user_nik']
        ]);
    } catch(PDOException $e) {
        error_log("Error updating UAC: " . $e->getMessage());
        return false;
    }
}

// Function to check user permissions
function checkUserAccess($conn, $role, $menu, $accessType = 'read') {
    try {
        $stmt = $conn->prepare("SELECT * FROM uac WHERE role_name = ?");
        $stmt->execute([$role]);
        $uac = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$uac) {
            return false;
        }

        $menuAccess = json_decode($uac['menu_access'], true);
        $hasMenuAccess = in_array($menu, $menuAccess);
        
        if ($accessType === 'read') {
            return $hasMenuAccess && $uac['read'] === '1';
        } else {
            return $hasMenuAccess && $uac['write'] === '1';
        }
    } catch(PDOException $e) {
        error_log("Error checking user access: " . $e->getMessage());
        return false;
    }
}

// Add this function to check menu access
function getUserMenuAccess($conn, $role) {
    try {
        $stmt = $conn->prepare("SELECT menu_access, `read`, `write`, `delete` FROM uac WHERE role_name = ?");
        $stmt->execute([$role]);
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($access) {
            // Decode menu access JSON
            $access['menu_access'] = json_decode($access['menu_access'], true);
            return $access;
        }
        
        // Return default no-access if role not found in UAC
        return [
            'menu_access' => [],
            'read' => '0',
            'write' => '0',
            'delete' => '0'
        ];
    } catch(PDOException $e) {
        error_log("Error getting user menu access: " . $e->getMessage());
        return false;
    }
}

// Add function to check if user has access to specific menu
function hasMenuAccess($menuAccess, $menuName, $accessType = 'read') {
    if (!$menuAccess) return false;
    
    // Check if menu exists in allowed menus
    $hasMenu = in_array($menuName, $menuAccess['menu_access']);
    
    // Check if user has required access type
    $hasAccess = $menuAccess[$accessType] === '1';
    
    return $hasMenu && $hasAccess;
}

// Add this to handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                try {
                    // Debug log
                    error_log("=== START Adding UAC ===");
                    error_log("POST data: " . print_r($_POST, true));
                    
                    if (empty($_POST['role_name'])) {
                        throw new Exception("Role name is required");
                    }
                    
                    if (empty($_POST['menu_access']) || !is_array($_POST['menu_access'])) {
                        throw new Exception("Menu access is required and must be an array");
                    }

                    $data = [
                        'role_name' => $_POST['role_name'],
                        'menu_access' => $_POST['menu_access'],
                        'read' => isset($_POST['read']) ? '1' : '0',
                        'write' => isset($_POST['write']) ? '1' : '0',
                        'delete' => isset($_POST['delete']) ? '1' : '0'
                    ];
                    
                    error_log("Processed data: " . print_r($data, true));
                    
                    if (addUAC($conn, $data)) {
                        $_SESSION['success'] = "Access control added successfully";
                    } else {
                        throw new Exception("Database error while adding access control");
                    }
                } catch (PDOException $e) {
                    error_log("PDO Error in add UAC: " . $e->getMessage());
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                } catch (Exception $e) {
                    error_log("General Error in add UAC: " . $e->getMessage());
                    $_SESSION['error'] = $e->getMessage();
                }
                break;

            case 'edit':
                if (empty($_POST['id'])) {
                    throw new Exception("ID is required");
                }
                
                $data = [
                    'role_name' => $_POST['role_name'],
                    'menu_access' => $_POST['menu_access'],
                    'read' => isset($_POST['read']) ? '1' : '0',
                    'write' => isset($_POST['write']) ? '1' : '0',
                    'delete' => isset($_POST['delete']) ? '1' : '0'
                ];
                
                if (updateUAC($conn, $_POST['id'], $data)) {
                    $_SESSION['success'] = "Access control updated successfully";
                } else {
                    throw new Exception("Failed to update access control");
                }
                break;

            case 'delete':
                if (empty($_POST['id'])) {
                    throw new Exception("ID is required");
                }
                
                $stmt = $conn->prepare("DELETE FROM uac WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    $_SESSION['success'] = "Access control deleted successfully";
                } else {
                    throw new Exception("Failed to delete access control");
                }
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ../view/uac.php');
    exit;
}
