<?php
include_once ("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prod_id']) && isset($_POST['action'])) {
    $prod_id = (int)$_POST['prod_id'];
    $action = $_POST['action'] === 'activate' ? 1 : 0;
    
    if ($prod_id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE products SET active=? WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $action, $prod_id);
            if (mysqli_stmt_execute($stmt)) {
                $status_msg = $action === 1 ? "Product Activated Successfully!" : "Product Deactivated Successfully!";
                $_SESSION['product_message'] = $status_msg;
            } else {
                $_SESSION['product_message'] = "Error updating product status.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['product_message'] = "Database error.";
        }
    } else {
        $_SESSION['product_message'] = "Invalid product ID.";
    }
} else {
    $_SESSION['product_message'] = "Invalid request.";
}

header("Location: productsettings.php");
exit;
?>
