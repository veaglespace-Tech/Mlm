<?php
header( "refresh:2;url=payrequest.php" );
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
$tomake= (int)($_GET["payid"] ?? 0);

if ($tomake <= 0) {
print "<center>Invalid payment<br/>Redirecting in 2 seconds...</center>";
exit;
}

$stmt = mysqli_prepare($con, "UPDATE payments SET payment_status=1 WHERE id=?");
$result = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, "i", $tomake);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}
if ($result)
{
print "<center>Status Updated<br/>Redirecting in 2 seconds...</center>";
}
else
{
print "<center>Action could not be performed, Something went wrong. Check back again<br/>Redirecting in 2 seconds...</center>";
}

?>
