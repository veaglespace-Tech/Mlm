<?php
session_start(); //starting session
include('z_db.php'); //connection details
if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
verify_csrf_token();
$ndel=(int)($_POST['notisub'] ?? 0); //fetching details through post method

if ($ndel <= 0) {
print "Invalid notification.";
header( "refresh:1;url=notifications.php" );
exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $res1 = $stmt->execute([$ndel]);
} catch (PDOException $e) {
    $res1 = false;
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
