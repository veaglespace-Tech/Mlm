<?php
session_start(); //starting session
include('z_db.php'); //connection details
if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$ndel=(int)($_POST['notisub'] ?? 0); //fetching details through post method

if ($ndel <= 0) {
print "Invalid notification.";
header( "refresh:1;url=notifications.php" );
exit;
}

$stmt = mysqli_prepare($con, "DELETE FROM notifications WHERE id=?");
$res1 = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, "i", $ndel);
$res1 = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}

if($res1)
{
print "Notification deleted...!!!";
}
else
{
print "error!!!! try again later or ask for help from your administrator.";
}

header( "refresh:1;url=notifications.php" );
exit;
} else {
    echo "Invalid request.";
}
?>
