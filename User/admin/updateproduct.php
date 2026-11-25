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
    $id = (int)($_POST['uproduct'] ?? 0);
    
    // Fetch existing product data
    $existing = mlmp_pdo_fetch($pdo, "SELECT name, description, icon, emoji FROM products WHERE id = ?", [$id]);
    
    if($existing) {
        $prodname = !empty($_POST['prodname']) ? trim($_POST['prodname']) : $existing['name'];
        $proddetail = !empty($_POST['proddetail']) ? trim($_POST['proddetail']) : $existing['description'];
        $prodicon = !empty($_POST['prodicon']) ? trim($_POST['prodicon']) : $existing['icon'];
        $prodemoji = !empty($_POST['prodemoji']) ? trim($_POST['prodemoji']) : $existing['emoji'];

        try {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, icon = ?, emoji = ? WHERE id = ?");
            $stmt->execute([$prodname, $proddetail, $prodicon, $prodemoji, $id]);
            $_SESSION['product_message'] = "Success: Product updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['product_message'] = "Error: Failed to update product.";
        }
    } else {
        $_SESSION['product_message'] = "Error: Product not found.";
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
