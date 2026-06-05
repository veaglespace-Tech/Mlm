<?php
header( "refresh:3;url=users.php" );
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
$tomake= trim($_GET["username"] ?? '');

if ($tomake === '') {
print "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
exit;
}

$stmt = mysqli_prepare($con, "UPDATE affiliateuser SET active=1 WHERE username=?");
$result = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, "s", $tomake);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}
if ($result)
{
print "<center>Profile Activated<br/>Redirecting in 2 seconds...</center>";
}
else
{
print "<center>Action could not be performed, Something went wrong. Check back again<br/>Redirecting in 2 seconds...</center>";
}

?>
