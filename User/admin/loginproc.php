<?php
//session_start(); //starting session
include('z_db.php'); //connection details
include_once('../password_helper.php');
$status = "OK"; //initial status
$msg="";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$username = $_POST['username'] ?? ''; //fetching details through post method
$password = $_POST['password'] ?? '';

if ( strlen($username ?? '') < 8 ){
$msg=$msg."Username must be more than 8 char legth<BR>";
$status= "NOTOK";}

if ( strlen($password ?? '') < 8 ){ //checking if password is greater then 8 or not
$msg=$msg."Password must be more than 8 char legth<BR>";
$status= "NOTOK";}

if($status=="OK"){

// Retrieve admin details and verify the password in PHP so old plaintext
// passwords can be migrated to password_hash without breaking login.
$adminRow = mlmp_pdo_fetch($pdo, "SELECT password FROM affiliateuser WHERE username = ? AND active = 1 AND level = 1 LIMIT 1", [$username]);
$num = 0;
if ($adminRow && mlmp_password_matches($password, $adminRow['password'])) {
    $num = 1;
    $storedPassword = $adminRow['password'];
}

if (($num) == 1) {
mlmp_upgrade_password_hash($con, $username, $password, $storedPassword);
session_start();
        // Set username session variable
        $_SESSION['adminidusername'] = $username;
		
        // Jump to secured page
		print "
				<script language='javascript'>
					window.location = 'dashboard.php?page=dashboard%location=index.php';
				</script>";
}else{
echo "<br/><br/><br/><br/><br/><center>Username And Password Not Matched Or May Be Your Account Is Inactive. <br/> Contact Admin For More Information.</center>";
}} 
else {
        
echo "<font face='Verdana' size='2' color=red>$msg</font><br><input type='button' value='Retry' onClick='history.go(-1)'>"; //printing errors
	 
}
} else {
    echo "Invalid request.";
}

?>
