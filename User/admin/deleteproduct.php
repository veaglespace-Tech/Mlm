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
    $id = (int)$_POST['productdelid'];
    
    $query = "UPDATE products SET active = 0 WHERE id=$id";
    if(mysqli_query($con, $query)) {
        $_SESSION['product_message'] = "Success: Product deactivated successfully!";
    } else {
        $_SESSION['product_message'] = "Error: Failed to deactivate product. " . mysqli_error($con);
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
