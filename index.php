<?php
session_start();
$page_title = "Dashboard";
ob_start();

// Check if user is logged in
if (!isset($_SESSION['user_nik'])) {
    header('Location: view/login.php');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
    </div>
    <div class="card-body">
        <p>You are logged in as: <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
        <p>Your NIK: <?php echo htmlspecialchars($_SESSION['user_nik']); ?></p>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'main_navbar.php';
?>