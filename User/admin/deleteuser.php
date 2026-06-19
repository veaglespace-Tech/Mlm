<?php
header("refresh:2;url=users.php");
include_once("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    print "<script language='javascript'>window.location = 'index.php';</script>";
    exit;
}

$todelete = trim($_GET["username"] ?? '');
if ($todelete === '' || $todelete === $_SESSION['adminidusername'] || !ctype_alnum($todelete)) {
    print "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
    exit;
}

try {
    $deleted = mlmp_pdo_execute($pdo, "DELETE FROM affiliateuser WHERE username = ? AND level <> 1", [$todelete]);
    if ($deleted) {
        ensure_binary_schema($pdo);
        mlmp_recalculate_network_counts($pdo);
        print "<center>User deleted<br/>Redirecting in 2 seconds...</center>";
    } else {
        print "<center>Action could not be performed, check back again<br/>Redirecting in 2 seconds...</center>";
    }
} catch (Exception $e) {
    print "<center>Action could not be performed: " . mlmp_escape($e->getMessage()) . "<br/>Redirecting in 2 seconds...</center>";
}
?>
