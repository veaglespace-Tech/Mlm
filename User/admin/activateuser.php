<?php
header("refresh:3;url=users.php");
include_once("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    print "<script language='javascript'>window.location = 'index.php';</script>";
    exit;
}

$tomake = trim($_GET["username"] ?? '');
if ($tomake === '' || !ctype_alnum($tomake)) {
    print "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
    exit;
}

$result = mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET active = 1 WHERE username = ?", [$tomake]);
if ($result) {
    print "<center>Profile Activated<br/>Redirecting in 2 seconds...</center>";
} else {
    print "<center>Action could not be performed, Something went wrong. Check back again<br/>Redirecting in 2 seconds...</center>";
}
?>
