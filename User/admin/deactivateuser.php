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
$tomake= trim($_GET["username"] ?? '');

if ($tomake === '') {
print "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
exit;
}

try {
    $stmt = $pdo->prepare("UPDATE affiliateuser SET active = 0 WHERE username = ?");
    $result = $stmt->execute([$tomake]);
} catch (PDOException $e) {
    $result = false;
}
if ($result)
{
print "<center>Profile Deactivated<br/>Redirecting in 2 seconds...</center>";
}
else
{
print "<center>Action could not be performed, Something went wrong. Check back again<br/>Redirecting in 2 seconds...</center>";
}

?>
