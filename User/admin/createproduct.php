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
    $prodname = trim($_POST['prodname'] ?? '');
    $proddetail = trim($_POST['proddetail'] ?? '');
    $prodicon = trim($_POST['prodicon'] ?? '');
    $prodemoji = trim($_POST['prodemoji'] ?? '');
    if(empty($prodemoji)) {
        $prodemoji = "🌟";
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, icon, emoji, active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$prodname, $proddetail, $prodicon, $prodemoji]);
        $_SESSION['product_message'] = "Success: Product '$prodname' created successfully!";
    } catch (PDOException $e) {
        $_SESSION['product_message'] = "Error: Failed to create product.";
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
