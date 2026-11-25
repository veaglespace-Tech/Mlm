<?php
include_once ("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prod_id']) && isset($_POST['action'])) {
    verify_csrf_token();
    $prod_id = (int)$_POST['prod_id'];
    $action = $_POST['action'] === 'activate' ? 1 : 0;
    
    if ($prod_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET active = ? WHERE id = ?");
            $stmt->execute([$action, $prod_id]);
            $status_msg = $action === 1 ? "Product Activated Successfully!" : "Product Deactivated Successfully!";
            $_SESSION['product_message'] = $status_msg;
        } catch (PDOException $e) {
            $_SESSION['product_message'] = "Error updating product status.";
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
