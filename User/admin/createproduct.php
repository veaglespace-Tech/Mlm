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
    $prodname = mysqli_real_escape_string($con, $_POST['prodname']);
    $proddetail = mysqli_real_escape_string($con, $_POST['proddetail']);
    $prodicon = mysqli_real_escape_string($con, $_POST['prodicon']);
    $prodemoji = mysqli_real_escape_string($con, $_POST['prodemoji']);
    if(empty($prodemoji)) {
        $prodemoji = "🌟";
    }

    $query = "INSERT INTO products (name, description, icon, emoji, active) VALUES ('$prodname', '$proddetail', '$prodicon', '$prodemoji', 1)";
    if(mysqli_query($con, $query)) {
        $_SESSION['product_message'] = "Success: Product '$prodname' created successfully!";
    } else {
        $_SESSION['product_message'] = "Error: Failed to create product. " . mysqli_error($con);
    }
    
    header("Location: productsettings.php");
    exit;
}
?>
