<?php
header("refresh:2;url=payrequest.php");
include_once("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
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

    // Send automated Payout Settlement Email Notification
    try {
        require_once __DIR__ . '/../smtp_helper.php';
        $userRow = mlmp_pdo_fetch($pdo, "SELECT fname, email, bankname, accountno FROM affiliateuser WHERE Id = ?", [$userId]);
        if ($userRow && !empty($userRow['email'])) {
            $subject = "Withdrawal Payout Disbursed - INR " . number_format($amount, 2);
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                <h2 style='color: #4f46e5; margin-top: 0;'>Payout Disbursed Successfully!</h2>
                <p>Dear <strong>" . htmlspecialchars($userRow['fname']) . "</strong>,</p>
                <p>Your withdrawal request for <strong>INR " . number_format($amount, 2) . "</strong> has been processed and disbursed to your registered bank account.</p>
                <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #e2e8f0;'>
                    <p style='margin: 4px 0;'><strong>Payout Request ID:</strong> #" . $tomake . "</p>
                    <p style='margin: 4px 0;'><strong>Disbursed Amount:</strong> INR " . number_format($amount, 2) . "</p>
                    <p style='margin: 4px 0;'><strong>Bank Name:</strong> " . htmlspecialchars($userRow['bankname']) . "</p>
                    <p style='margin: 4px 0;'><strong>Account Number:</strong> " . htmlspecialchars($userRow['accountno']) . "</p>
                    <p style='margin: 4px 0;'><strong>Status:</strong> <span style='color: #16a34a; font-weight: bold;'>Completed / Paid</span></p>
                </div>
                <p>Thank you for your active participation in our affiliate network!</p>
                <p style='color: #64748b; font-size: 12px; margin-top: 20px;'>Veagle Space Finance & Support Team</p>
            </div>";
            mlmp_send_mail($userRow['email'], $subject, $body);
        }
    } catch (Exception $mailEx) {
        error_log("Failed to send payout notification email: " . $mailEx->getMessage());
    }

    print "<center>Status Updated, Wallet Deducted and Member Notified<br/>Redirecting in 2 seconds...</center>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    print "<center>Action could not be performed: " . mlmp_escape($e->getMessage()) . "<br/>Redirecting in 2 seconds...</center>";
}
?>
