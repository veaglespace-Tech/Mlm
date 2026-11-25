<?php
include_once ("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pkg_id']) && isset($_POST['action'])) {
    verify_csrf_token();
    $pkg_id = (int)$_POST['pkg_id'];
    $action = $_POST['action'] === 'activate' ? 1 : 0;
    
    if ($pkg_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE packages SET active = ? WHERE id = ?");
            $stmt->execute([$action, $pkg_id]);
            $status_msg = $action === 1 ? "Package Activated Successfully!" : "Package Deactivated Successfully!";
            $_SESSION['package_message'] = $status_msg;
        } catch (PDOException $e) {
            $_SESSION['package_message'] = "Error updating package status.";
        }
    } else {
        $_SESSION['package_message'] = "Invalid package ID.";
    }
} else {
    $_SESSION['package_message'] = "Invalid request.";
}

header("Location: pacsettings.php");
exit;
?>
