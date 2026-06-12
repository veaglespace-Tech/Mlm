<?php
header( "refresh:2;url=users.php" );
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
$todelete= trim($_GET["username"] ?? '');

if ($todelete === '' || $todelete === $_SESSION['adminidusername']) {
print "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
exit;
}

$stmt = mysqli_prepare($con, "DELETE FROM affiliateuser WHERE username=? AND level <> 1");
$result = false;
if ($stmt) {
mysqli_stmt_bind_param($stmt, "s", $todelete);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
}
if ($result)
{
    // Re-calculate left and right counts to automatically clear old network tree caches
    if (isset($pdo)) {
        mlmp_recalculate_network_counts($pdo);
    }
print "<center>User deleted<br/>Redirecting in 2 seconds...</center>";
}
else
{
print "<center>Action could not be performed, check back again<br/>Redirecting in 2 seconds...</center>";
}

?>
