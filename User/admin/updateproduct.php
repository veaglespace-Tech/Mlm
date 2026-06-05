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
    $id = (int)$_POST['uproduct'];
    
    // Fetch existing product data
    $q = mysqli_query($con, "SELECT name, description, icon, emoji FROM products WHERE id=$id");
    $existing = mysqli_fetch_assoc($q);
    
    if($existing) {
        $prodname = !empty($_POST['prodname']) ? mysqli_real_escape_string($con, $_POST['prodname']) : $existing['name'];
        $proddetail = !empty($_POST['proddetail']) ? mysqli_real_escape_string($con, $_POST['proddetail']) : $existing['description'];
        $prodicon = !empty($_POST['prodicon']) ? mysqli_real_escape_string($con, $_POST['prodicon']) : $existing['icon'];
        $prodemoji = !empty($_POST['prodemoji']) ? mysqli_real_escape_string($con, $_POST['prodemoji']) : $existing['emoji'];

        $query = "UPDATE products SET name='$prodname', description='$proddetail', icon='$prodicon', emoji='$prodemoji' WHERE id=$id";
        
        if(mysqli_query($con, $query)) {
            $_SESSION['product_message'] = "Success: Product updated successfully!";
        } else {
            $_SESSION['product_message'] = "Error: Failed to update product. " . mysqli_error($con);
        }
    } else {
        $_SESSION['product_message'] = "Error: Product not found.";
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
