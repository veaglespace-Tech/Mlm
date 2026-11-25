<?php
include_once ("z_db.php");
// Inialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
        print "
				<script language='javascript'>
					window.location = 'index.php';
				</script>
			";
        exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    verify_csrf_token();
    $id = (int)($_POST['productdelid'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['product_message'] = "Success: Product deactivated successfully!";
    } catch (PDOException $e) {
        $_SESSION['product_message'] = "Error: Failed to deactivate product.";
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
