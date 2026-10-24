<?php
//session_start(); //starting session
include('z_db.php'); //connection details
include_once('../password_helper.php');
$status = "OK"; //initial status
$msg="";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$username = trim($_POST['username'] ?? ''); //fetching details through post method
$password = $_POST['password'] ?? '';

if ( strlen($username ?? '') < 3 ){
$msg=$msg."Username or Email must be at least 3 characters<BR>";
$status= "NOTOK";}

if ( strlen($password ?? '') < 6 ){ //checking if password is greater than or equal to 6
$msg=$msg."Password must be at least 6 characters<BR>";
$status= "NOTOK";}

if($status=="OK"){

// Retrieve admin details by username OR email and verify password
$adminRow = mlmp_pdo_fetch($pdo, "SELECT username, password FROM affiliateuser WHERE (username = ? OR email = ?) AND active = 1 AND level = 1 LIMIT 1", [$username, $username]);
$num = 0;
$adminUsername = '';
if ($adminRow && mlmp_password_matches($password, $adminRow['password'])) {
    $num = 1;
    $storedPassword = $adminRow['password'];
    $adminUsername = $adminRow['username'];
}

if (($num) == 1) {
mlmp_upgrade_password_hash($pdo, $adminUsername, $password, $storedPassword);
session_start();
        // Set username session variable
        $_SESSION['adminidusername'] = $adminUsername;
		
        // Jump to secured page
		print "
				<script language='javascript'>
					window.location = 'dashboard.php?page=dashboard%location=index.php';
				</script>";
}else{
echo "<br/><br/><br/><br/><br/><center>Username/Email and Password Not Matched Or May Be Your Account Is Inactive. <br/> Contact Admin For More Information.</center>";
}} 
else {
        
echo "<font face='Verdana' size='2' color=red>$msg</font><br><input type='button' value='Retry' onClick='history.go(-1)'>"; //printing errors
	 
}
} else {
    echo "Invalid request.";
}

?>
