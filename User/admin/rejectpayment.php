<?php
header("refresh:2;url=payrequest.php");
include_once("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    print "<script language='javascript'>window.location = 'index.php';</script>";
    exit;
}

ensure_payout_schema($pdo);

$paymentId = (int)($_GET["payid"] ?? 0);
if ($paymentId <= 0) {
    print "<center>Invalid payment<br/>Redirecting in 2 seconds...</center>";
    exit;
}

$updated = mlmp_pdo_execute(
    $pdo,
    "UPDATE payments SET payment_status = 2, reviewed_at = NOW(), review_note = ? WHERE id = ? AND payment_status = 0",
    ["Rejected by " . $_SESSION['adminidusername'], $paymentId]
);

if ($updated) {
    print "<center>Withdrawal request rejected<br/>Redirecting in 2 seconds...</center>";
} else {
    print "<center>Action could not be performed, request may already be reviewed<br/>Redirecting in 2 seconds...</center>";
}
?>
