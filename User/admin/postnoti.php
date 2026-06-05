<?php
session_start(); //starting session
include('z_db.php'); //connection details
if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
}
header( "refresh:1;url=notifications.php" );
$status = "OK"; //initial status
$msg="";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$nhead=mysqli_real_escape_string($con,$_POST['notihead'] ?? ''); //fetching details through post method
$nbody = mysqli_real_escape_string($con,$_POST['notibody'] ?? '');

if ( strlen($nhead) < 2 ){
$msg=$msg."Subject Should Have Minimum 2 Characters.<BR>";
$status= "NOTOK";}

if ( strlen($nbody) < 4 ){ //checking if body is greater then 4 or not
$msg=$msg."Body must contain more than 4 char length.<BR>";
$status= "NOTOK";}

if($status=="OK")
{
$stmt = mysqli_prepare($con, "INSERT INTO notifications (subject, body, posteddate, valid) VALUES (?, ?, NOW(), 1)");
$res1 = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, "ss", $nhead, $nbody);
$res1 = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}

if($res1)
{
print "Notification Posted...!!!";
}
else
{
print "error!!!! try again later or ask for help from your administrator.";
}


} 
else {
        
echo "<font face='Verdana' size='2' color=red>$msg</font><br><input type='button' value='Retry' onClick='history.go(-1)'>"; //printing errors
	 
}
} else {
    echo "Invalid request.";
}

?>
