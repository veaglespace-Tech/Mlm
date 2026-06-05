<?php
include_once ("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pkg_id']) && isset($_POST['action'])) {
    $pkg_id = (int)$_POST['pkg_id'];
    $action = $_POST['action'] === 'activate' ? 1 : 0;
    
    if ($pkg_id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE packages SET active=? WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $action, $pkg_id);
            if (mysqli_stmt_execute($stmt)) {
                $status_msg = $action === 1 ? "Package Activated Successfully!" : "Package Deactivated Successfully!";
                $_SESSION['package_message'] = $status_msg;
            } else {
                $_SESSION['package_message'] = "Error updating package status.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['package_message'] = "Database error.";
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
