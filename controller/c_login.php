<?php
session_start();
require_once 'conn.php';
require_once dirname(__DIR__) . '/routing.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nik = $_POST['nik'];
        $password = $_POST['password'];

        // Query should use lowercase column names
        $sql = "SELECT nik, employee_name, employee_email, role, project, password 
                FROM employee_active 
                WHERE nik = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nik]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            error_log("User found, checking password");
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_nik'] = $user['nik'];
                $_SESSION['user_name'] = $user['employee_name'];
                $_SESSION['user_role'] = $user['role'];
                
                error_log("Login successful for user: " . $user['employee_name']);
                header('Location: ' . Router::url('dashboard'));
                exit;
            } else {
                error_log("Password verification failed");
                $_SESSION['error'] = "Invalid NIK or password";
                header('Location: ../view/login.php');
                exit;
            }
        } else {
            error_log("No user found with NIK: " . $nik);
            $_SESSION['error'] = "Invalid NIK or password";
            header('Location: ../view/login.php');
            exit;
        }

    } else {
        // If not POST request, redirect to login
        header('Location: ../view/login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Database error in login: " . $e->getMessage());
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: ../view/login.php');
    exit;
}

// Close connection
$conn = null;
?> 