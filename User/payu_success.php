<?php
if(!isset($_SESSION)){
    session_start();
}
include_once("z_db.php");

// Fetch username from GET/POST (simulation/direct redirect) or session (live fallback)
$username = $_GET['username'] ?? $_POST['username'] ?? $_SESSION['reg_username'] ?? '';
$txnid = $_POST['txnid'] ?? '';
$amount = $_POST['amount'] ?? 1000.00;
$productinfo = $_POST['productinfo'] ?? $_SESSION['selected_product'] ?? 'MLM Affiliate Digital Kit';
$firstname = $_POST['firstname'] ?? ($_SESSION['payu_txn']['firstname'] ?? '');
$email = $_POST['email'] ?? ($_SESSION['payu_txn']['email'] ?? '');
$status = $_POST['status'] ?? '';
$postedHash = strtolower($_POST['hash'] ?? '');
$isSimulation = isset($_POST['username']) && $postedHash === '';

if (empty($username)) {
    header("Location: signup.php");
    exit;
}

if (!$isSimulation) {
    if (strtolower($status) !== 'success') {
        header("Location: payu_failure.php");
        exit;
    }

    $settings_row = mlmp_pdo_fetch($pdo, "SELECT paypalid, payzaid FROM settings LIMIT 1");
    $storedKey = trim($settings_row['paypalid'] ?? '');
    $storedSalt = trim($settings_row['payzaid'] ?? '');
    $merchantKey = ($storedKey !== '' && strpos($storedKey, '@') === false) ? $storedKey : "GKJE3Z";
    $salt = ($storedSalt !== '' && strtolower($storedSalt) !== 'payza') ? $storedSalt : "0zqiCnB4GslxAanSxjEAutWkWuggFiGs";

    $expectedTxn = $_SESSION['payu_txn']['txnid'] ?? '';
    if ($expectedTxn !== '' && !hash_equals($expectedTxn, $txnid)) {
        header("Location: payu_failure.php");
        exit;
    }

    $reverseHashSequence = "$salt|$status|||||||||||$email|$firstname|$productinfo|$amount|$txnid|$merchantKey";
    $expectedHash = strtolower(hash('sha512', $reverseHashSequence));
    if ($postedHash === '' || !hash_equals($expectedHash, $postedHash)) {
        header("Location: payu_failure.php");
        exit;
    }
}

// 1. Fetch user from database
$queryUser = "SELECT Id, pcktaken FROM affiliateuser WHERE username = ?";
$stmtUser = $pdo->prepare($queryUser);
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();

if (!$user) {
    die("User registration record not found.");
}

$uaid = $user['id'] ?? $user['Id'] ?? null;
$pcktaken = $user['pcktaken'] ?? 1;

// 2. Activate user and propagate binary commission matching engine
mlmp_launch_profile($pdo, $username);
$queryActivate = "UPDATE affiliateuser SET payment = 'PayU' WHERE username = ?";
$stmtActivate = $pdo->prepare($queryActivate);
$stmtActivate->execute([$username]);

// Clean up captured session data
unset($_SESSION['signup_data']);

// 3. Record the transaction in the payu_payments table
$queryPaymentExists = "SELECT COUNT(*) FROM payu_payments WHERE transacid = ?";
$stmtCheck = $pdo->prepare($queryPaymentExists);
$stmtCheck->execute([$txnid]);
$paymentCount = $stmtCheck->fetchColumn();

if ($paymentCount == 0 && !empty($txnid)) {
    $queryInsertPayment = "INSERT INTO payu_payments (orderid, transacid, price, currency, date, pckid, gateway) VALUES (?, ?, ?, 'INR', NOW(), ?, 'PayU')";
    $stmtInsert = $pdo->prepare($queryInsertPayment);
    $stmtInsert->execute([$uaid, $txnid, $amount, $pcktaken]);

    // Send Welcome Email
    require_once "smtp_helper.php";
    
    $userFname = $user['fname'] ?? 'Affiliate';
    $userEmail = $user['email'] ?? '';
    
    if(!empty($userEmail)) {
        $welcomeSubject = "Welcome to MLM Platform - Account Activated!";
        $welcomeMessage = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden;">
            <div style="background: #11162d; color: #fff; padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 24px; color: #34d399;">Welcome to MLM Platform!</h2>
            </div>
            <div style="padding: 30px; background: #fff; color: #333;">
                <p style="font-size: 16px;">Hi <strong>' . htmlspecialchars($userFname) . '</strong>,</p>
                <p style="font-size: 16px; line-height: 1.5;">Your account has been successfully created and activated. You can now access your affiliate dashboard, build your network, and track your metrics.</p>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <p style="margin: 5px 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                    <p style="margin: 5px 0;"><strong>Status:</strong> Active</p>
                </div>
                <p style="font-size: 16px;">Get started now by logging into your dashboard.</p>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="' . $protocol . '://' . $host . $basePath . '/login.php" style="background: #7c3aed; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 16px;">Login to Dashboard</a>
                </div>
            </div>
            <div style="background: #f1f5f9; padding: 15px; text-align: center; color: #64748b; font-size: 12px;">
                &copy; ' . date("Y") . ' MLM Platform. All rights reserved.
            </div>
        </div>';
        
        mlmp_send_mail($userEmail, $welcomeSubject, $welcomeMessage);
    }
}

// 4. Automatically log the user in by setting the session variable!
$_SESSION['username'] = $username;

// Clean temporary signup variables
unset($_SESSION['reg_username']);
unset($_SESSION['selected_product']);
unset($_SESSION['payu_txn']);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Payment Successful - MLM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/dashboard-modern.css?v=2.0"/>`n<style>
.checkout-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: var(--bg-body);
}
.checkout-panel {
    max-width: 480px;
    width: 100%;
}
.pop-check {
    animation: popCheck 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes popCheck {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.auto-loader {
    width: 24px;
    height: 24px;
    border: 2px solid var(--border-color);
    border-top-color: var(--accent-purple);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.btn-dash {
    width: 100%;
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
    text-decoration: none;
    transition: opacity 0.2s, transform 0.1s;
}
.btn-dash:hover {
    opacity: 0.9;
    color: white;
}
.btn-dash:active {
    transform: scale(0.98);
}
</style>
</head>
<body class="dash-body">

<div class="checkout-wrapper">
    <div class="dash-panel checkout-panel">
        <div class="dash-panel-body" style="padding: 40px 24px; text-align:center;">
            
            <div class="pop-check" style="width:72px; height:72px; background:rgba(5, 150, 105, 0.1); border:1px solid rgba(5, 150, 105, 0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 24px auto;">
                <i class="fa-solid fa-circle-check" style="color:var(--accent-green); font-size: 32px;"></i>
            </div>

            <h2 style="color:var(--text-primary); font-size:22px; font-weight:700; margin-bottom:12px;">Payment Successful!</h2>
            <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin-bottom:32px;">
                Welcome, <strong style="color:var(--accent-purple);">@<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?></strong>. Your affiliate account is active and your starter product <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($productinfo, ENT_QUOTES, 'utf-8'); ?></strong> has been successfully registered.
            </p>

            <div style="border-top: 1px solid var(--border-color); margin: 24px 0; padding-top: 24px; display:flex; flex-direction:column; align-items:center; gap:12px;">
                <span style="font-size:12px; color:var(--text-secondary);">Redirecting to your dashboard...</span>
                <div class="auto-loader"></div>
            </div>

            <a href="dashboard.php" class="btn-dash">
                Go to Dashboard <i class="fa-solid fa-arrow-right" style="font-size:12px;"></i>
            </a>

        </div>
    </div>
</div>

<script>
setTimeout(function() {
    window.location = "dashboard.php";
}, 3000);
</script>
</body>
</html>