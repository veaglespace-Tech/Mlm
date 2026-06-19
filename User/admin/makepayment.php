<?php
header("refresh:2;url=payrequest.php");
include_once("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    print "<script language='javascript'>window.location = 'index.php';</script>";
    exit;
}

ensure_payout_schema($pdo);

$tomake = (int)($_GET["payid"] ?? 0);
if ($tomake <= 0) {
    print "<center>Invalid payment<br/>Redirecting in 2 seconds...</center>";
    exit;
}

try {
    $pdo->beginTransaction();

    $payment = mlmp_pdo_fetch($pdo, "SELECT id, userid, payment_amount, payment_status FROM payments WHERE id = ? LIMIT 1 FOR UPDATE", [$tomake]);
    if (!$payment) {
        throw new RuntimeException("Payment request not found.");
    }

    if ((int)$payment['payment_status'] !== 0) {
        throw new RuntimeException("Only pending requests can be marked paid.");
    }

    $amount = (float)$payment['payment_amount'];
    $userId = (int)$payment['userid'];

    $stmtDeduct = $pdo->prepare("UPDATE affiliateuser SET tamount = tamount - ? WHERE Id = ? AND tamount >= ?");
    $stmtDeduct->execute([$amount, $userId, $amount]);
    if ($stmtDeduct->rowCount() !== 1) {
        throw new RuntimeException("User balance is no longer sufficient for this payout.");
    }

    mlmp_pdo_execute(
        $pdo,
        "UPDATE payments SET payment_status = 1, reviewed_at = NOW(), review_note = ? WHERE id = ?",
        ["Marked paid by " . $_SESSION['adminidusername'], $tomake]
    );

    $pdo->commit();
    print "<center>Status Updated and Wallet Deducted<br/>Redirecting in 2 seconds...</center>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    print "<center>Action could not be performed: " . mlmp_escape($e->getMessage()) . "<br/>Redirecting in 2 seconds...</center>";
}
?>
