<?php
session_start();
include_once("../z_db.php");

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['approval_msg'] = "Invalid request method.";
    header("Location: approvals.php");
    exit;
}

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';

if (empty($action) || empty($username)) {
    $_SESSION['approval_msg'] = "Missing parameters.";
    header("Location: approvals.php");
    exit;
}

// Ensure the user exists in pending_registrations and sponsor is level=1
$stmtPending = $pdo->prepare("SELECT p.*, a.level FROM pending_registrations p JOIN affiliateuser a ON p.referedby = a.username WHERE p.username = ? AND a.level = 1");
$stmtPending->execute([$username]);
$pendingUser = $stmtPending->fetch();

if (!$pendingUser) {
    $_SESSION['approval_msg'] = "Pending registration not found or invalid sponsor.";
    header("Location: approvals.php");
    exit;
}

if ($action === 'reject') {
    // Insert into rejected history table
    $stmtHistory = $pdo->prepare("INSERT INTO rejected_registrations (username, fname, email, referedby, mobile, country) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtHistory->execute([
        $pendingUser['username'],
        $pendingUser['fname'],
        $pendingUser['email'],
        $pendingUser['referedby'],
        $pendingUser['mobile'],
        $pendingUser['country']
    ]);

    $stmtReject = $pdo->prepare("UPDATE pending_registrations SET admin_approval_status = 'Rejected' WHERE username = ?");
    $stmtReject->execute([$username]);

    // Send Rejection Email
    require_once "../smtp_helper.php";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); // go up one dir
    
    $rejectedLink = $protocol . '://' . $host . $basePath . '/rejected.php?username=' . urlencode($username);

    $rejectSubject = "Application Rejected";
    $rejectMessage = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden;">
        <div style="background: #11162d; color: #fff; padding: 20px; text-align: center;">
            <h2 style="margin: 0; font-size: 24px; color: #f87171;">Application Rejected</h2>
        </div>
        <div style="padding: 30px; background: #fff; color: #333;">
            <p style="font-size: 16px;">Hi <strong>' . htmlspecialchars($pendingUser['fname']) . '</strong>,</p>
            <p style="font-size: 16px; line-height: 1.5;">We regret to inform you that your request to join the network has been rejected by the administrator.</p>
            <p style="font-size: 16px; line-height: 1.5;">Please join with another sponsor\'s ID or retry later. You can find more details by clicking the button below:</p>
            
            <div style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
                <a href="' . $rejectedLink . '" style="background: #ef4444; color: #fff; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;">View Status</a>
            </div>
            
            <p style="font-size: 14px; color: #666;">If the button does not work, copy and paste this link into your browser:<br><br>' . $rejectedLink . '</p>
        </div>
    </div>';
    
    mlmp_send_mail($pendingUser['email'], $rejectSubject, $rejectMessage);

    $_SESSION['approval_msg'] = "User @$username has been REJECTED. They have been sent an email notifying them.";
    header("Location: approvals.php");
    exit;
}

if ($action === 'approve') {
    // Just update the status instead of activating!
    $stmtApprove = $pdo->prepare("UPDATE pending_registrations SET admin_approval_status = 'Approved' WHERE username = ?");
    $stmtApprove->execute([$username]);

    // Send Approval Email with Payment Link
    require_once "../smtp_helper.php";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); // go up one dir
    
    $paymentLink = $protocol . '://' . $host . $basePath . '/payu_payment.php?retry_username=' . urlencode($username);

    $approvalSubject = "Application Approved - Action Required";
    $approvalMessage = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden;">
        <div style="background: #11162d; color: #fff; padding: 20px; text-align: center;">
            <h2 style="margin: 0; font-size: 24px; color: #34d399;">Application Approved!</h2>
        </div>
        <div style="padding: 30px; background: #fff; color: #333;">
            <p style="font-size: 16px;">Hi <strong>' . htmlspecialchars($pendingUser['fname']) . '</strong>,</p>
            <p style="font-size: 16px; line-height: 1.5;">Great news! Your application to join the network has been approved by the administrator.</p>
            <p style="font-size: 16px; line-height: 1.5;">To officially activate your account and secure your position in the network, please complete your package payment by clicking the button below:</p>
            
            <div style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
                <a href="' . $paymentLink . '" style="background: #7c3aed; color: #fff; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;">Complete Payment Now</a>
            </div>
            
            <p style="font-size: 14px; color: #666;">If the button does not work, copy and paste this link into your browser:<br><br>' . $paymentLink . '</p>
        </div>
    </div>';
    
    mlmp_send_mail($pendingUser['email'], $approvalSubject, $approvalMessage);

    $_SESSION['approval_msg'] = "User @$username has been APPROVED. They have been sent an email to complete their payment.";
    header("Location: approvals.php");
    exit;
}

$_SESSION['approval_msg'] = "Unknown action.";
header("Location: approvals.php");
exit;
?>
