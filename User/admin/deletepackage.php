<?php
header( "refresh:2;url=pacsettings.php" );
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
verify_csrf_token();
$tomake= (int)($_POST["packagedelid"] ?? 0);

if ($tomake <= 0) {
print "<center>Invalid package<br/>Redirecting in 2 seconds...</center>";
exit;
}

try {
    $stmt = $pdo->prepare("UPDATE packages SET active = 0 WHERE id = ?");
    $result = $stmt->execute([$tomake]);
} catch (PDOException $e) {
    $result = false;
}
if ($result)
{
print "<center>Package Deactivated<br/>Redirecting in 2 seconds...</center>";
}
else
{
print "<center>Action could not be performed, Something went wrong. Check back again<br/>Redirecting in 2 seconds...</center>";
}
} else {
    echo "Invalid request.";
}

?>
