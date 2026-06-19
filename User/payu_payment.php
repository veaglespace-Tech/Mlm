<?php
if(!isset($_SESSION)){
    session_start();
}
// Prevent browser caching so txnid regenerates on Back button
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once("z_db.php");

// Safety check: if no registered username in session, try to recover from pending_registrations via retry_username
$retry_user = $_GET['retry_username'] ?? '';
if (!empty($retry_user)) {
    ensure_pending_schema($pdo);
    $stmt_recover = $pdo->prepare("SELECT * FROM pending_registrations WHERE username = ?");
    $stmt_recover->execute([$retry_user]);
    $recovered = $stmt_recover->fetch();
    if ($recovered) {
        $_SESSION['reg_username'] = $recovered['username'];
        $_SESSION['signup_data'] = [
            'fname' => $recovered['fname'],
            'password' => $recovered['password'],
            'email' => $recovered['email'],
            'mobile' => $recovered['mobile'],
            'address' => $recovered['address'],
            'country' => $recovered['country'],
            'referedby' => $recovered['referedby'],
            'ipaddress' => $recovered['ipaddress'],
            'doj' => $recovered['doj'],
            'signupcode' => $recovered['signupcode'],
            'package' => $recovered['pcktaken'],
            'expiry' => $recovered['expiry']
        ];
        
        $stmt_pkg = $pdo->prepare("SELECT name FROM packages WHERE id = ?");
        $stmt_pkg->execute([$recovered['pcktaken']]);
        $pkg = $stmt_pkg->fetch();
        $_SESSION['selected_product'] = $pkg ? $pkg['name'] : 'Membership Package';
    }
}

if (!isset($_SESSION['reg_username'])) {
    header("Location: signup.php");
    exit;
}

$username = $_SESSION['reg_username'] ?? '';
$productinfo = $_SESSION['selected_product'] ?? 'MLM Affiliate Digital Kit';

if (empty($username) || !isset($_SESSION['signup_data'])) {
    header("Location: signup.php");
    exit;
}

// Prevent active users from paying again (e.g. if they hit Back after successful payment)
$stmtUser = $pdo->prepare("SELECT active FROM affiliateuser WHERE username = ?");
$stmtUser->execute([$username]);
$u = $stmtUser->fetch();
if ($u && $u['active'] == 1) {
    unset($_SESSION['reg_username']);
    unset($_SESSION['signup_data']);
    header("Location: dashboard.php");
    exit;
}

$signup_data = $_SESSION['signup_data'];
$firstname = $signup_data['fname'];
$email = $signup_data['email'];
$phone = $signup_data['mobile'];
$package_id = $signup_data['package'] ?? 0;
$stmt_pkg = $pdo->prepare("SELECT price FROM packages WHERE id = ?");
$stmt_pkg->execute([$package_id]);
$pkg_row = $stmt_pkg->fetch();
$amount = $pkg_row ? number_format((float)$pkg_row['price'], 2, '.', '') : '1000.00';

// Ensure pending schema is created
ensure_pending_schema($pdo);

// Insert user into pending_registrations (only if not already there, to avoid resetting status)
if (isset($_SESSION['signup_data'])) {
    $sql_pending = "INSERT IGNORE INTO pending_registrations(username, password, fname, address, email, referedby, mobile, country, ipaddress, doj, signupcode, pcktaken, expiry) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    mlmp_pdo_execute($pdo, $sql_pending, [
        $username,
        $signup_data['password'],
        $signup_data['fname'],
        $signup_data['address'],
        $signup_data['email'],
        $signup_data['referedby'],
        $signup_data['mobile'],
        $signup_data['country'],
        $signup_data['ipaddress'],
        $signup_data['doj'],
        $signup_data['signupcode'],
        $package_id,
        $signup_data['expiry']
    ]);
}

// Check if the sponsor is an Admin (level = 1)
$is_admin_referral = false;
$stmt_ref = $pdo->prepare("SELECT level FROM affiliateuser WHERE username = ?");
$stmt_ref->execute([$signup_data['referedby']]);
$ref_user = $stmt_ref->fetch();
if ($ref_user && $ref_user['level'] == 1) {
    $is_admin_referral = true;
}

$admin_approval_status = 'Pending';
$stmt_app = $pdo->prepare("SELECT admin_approval_status FROM pending_registrations WHERE username = ?");
$stmt_app->execute([$username]);
$app_row = $stmt_app->fetch();
if ($app_row) {
    $admin_approval_status = $app_row['admin_approval_status'];
}

$is_admin_pending = ($is_admin_referral && $admin_approval_status !== 'Approved');

// Send Initial Registration Email
if (isset($_SESSION['signup_data']) && !isset($_SESSION['reg_email_sent'])) {
    require_once "smtp_helper.php";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    
    $regSubject = "Registration Received - Action Required";
    if ($is_admin_pending) {
        $statusText = "Pending Admin Approval";
        $actionText = "Your sponsor is an Administrator. Your account is currently pending manual approval. You will be notified once approved to complete your payment.";
        $actionBtn = "";
    } else {
        $statusText = "Pending Payment";
        $actionText = "We have successfully received your registration details. To activate your account and start your journey, please complete your payment.";
        $actionBtn = '<div style="text-align: center; margin-top: 30px;"><a href="' . $protocol . '://' . $host . $basePath . '/payu_payment.php?retry_username=' . urlencode($username) . '" style="background: #7c3aed; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 16px;">Complete Payment</a></div>';
    }

    $regMessage = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 10px; overflow: hidden;">
        <div style="background: #11162d; color: #fff; padding: 20px; text-align: center;">
            <h2 style="margin: 0; font-size: 24px; color: #34d399;">Registration Received</h2>
        </div>
        <div style="padding: 30px; background: #fff; color: #333;">
            <p style="font-size: 16px;">Hi <strong>' . htmlspecialchars($signup_data['fname']) . '</strong>,</p>
            <p style="font-size: 16px; line-height: 1.5;">' . $actionText . '</p>
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 5px 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                <p style="margin: 5px 0;"><strong>Status:</strong> ' . $statusText . '</p>
            </div>
            ' . $actionBtn . '
        </div>
    </div>';
    
    mlmp_send_mail($signup_data['email'], $regSubject, $regMessage);
    $_SESSION['reg_email_sent'] = true; // prevent duplicate sending if they refresh
}

// Fetch PayU Credentials dynamically from database settings
$settings_row = mlmp_pdo_fetch($pdo, "SELECT paypalid, payzaid, solidtrustid FROM settings LIMIT 1");

// If settings contain legacy non-PayU values (like a PayPal email or "Payza"), use PayU sandbox credentials.
$storedKey = trim($settings_row['paypalid'] ?? '');
$storedSalt = trim($settings_row['payzaid'] ?? '');
$storedSaltV2 = trim($settings_row['solidtrustid'] ?? '');
$MERCHANT_KEY = ($storedKey !== '' && strpos($storedKey, '@') === false) ? $storedKey : "GKJE3Z";
$SALT = ($storedSalt !== '' && strtolower($storedSalt) !== 'payza') ? $storedSalt : "0zqiCnB4GslxAanSxjEAutWkWuggFiGs";
$SALT_V2 = ($storedSaltV2 !== '' && strlen($storedSaltV2) >= 16 && strtolower($storedSaltV2) !== 'not available') ? $storedSaltV2 : '';

$isSandbox = ($MERCHANT_KEY === "GKJE3Z" && $SALT === "0zqiCnB4GslxAanSxjEAutWkWuggFiGs");
$PAYU_BASE_URL = $isSandbox ? "https://test.payu.in/_payment" : "https://secure.payu.in/_payment";

$txnid = substr('MLMP' . strtoupper(hash('sha256', $username . random_int(100000, 999999) . microtime(true))), 0, 20);
// PayU hosted checkout hash: sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||salt)
$udf1 = $username;
$hashSequence = "$MERCHANT_KEY|$txnid|$amount|$productinfo|$firstname|$email|$udf1||||||||||$SALT";
$hashV1 = strtolower(hash('sha512', $hashSequence));
$hash = $hashV1;

// PayU checkout expects a JSON hash object only when the merchant account uses Salt v2.
if ($SALT_V2 !== '') {
    $hashPayload = ['v1' => $hashV1];
    if ($SALT_V2 !== '') {
        $v2SignedString = "$MERCHANT_KEY|$txnid|$amount|$productinfo|$firstname|$email|$udf1||||||||||";
        $hashPayload['v2'] = strtolower(hash_hmac('sha256', $v2SignedString, $SALT_V2));
    }
    $hash = json_encode($hashPayload, JSON_UNESCAPED_SLASHES);
}

$_SESSION['payu_txn'] = [
    'txnid' => $txnid,
    'amount' => $amount,
    'productinfo' => $productinfo,
    'firstname' => $firstname,
    'email' => $email,
    'hash_v1' => $hashV1,
];

// Auto-detect secure protocol and current host/port for redirects
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/User/payu_payment.php')), '/');
$basePath = ($scriptDir === '' || $scriptDir === '.') ? '' : $scriptDir;
$surl = "$protocol://$host$basePath/payu_success.php?username=" . urlencode($username);
$furl = "$protocol://$host$basePath/payu_failure.php?username=" . urlencode($username);

$_layout_brand = 'MLM';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Secure Checkout - <?php echo mlmp_escape($_layout_brand); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/dashboard-modern.css?v=2.0"/>
<style>
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
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid var(--border-color);
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 500;
}
.info-value {
    color: var(--text-primary);
    font-size: 14px;
    font-weight: 600;
}
.pay-btn {
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
    transition: opacity 0.2s, transform 0.1s;
}
.pay-btn:hover {
    opacity: 0.9;
}
.pay-btn:active {
    transform: scale(0.98);
}
</style>
</head>
<body class="dash-body">

<div class="checkout-wrapper">
    <div class="dash-panel checkout-panel">
        <div class="dash-panel-header" style="justify-content:center; padding: 24px;">
            <div class="panel-title" style="font-size: 18px;">
                <span class="panel-icon"><i class="fa-solid fa-shield-halved"></i></span>
                Secure Checkout
            </div>
        </div>
        
        <div class="dash-panel-body" style="padding: 24px;">
            <?php if ($is_admin_pending): ?>
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:var(--accent-purple); color:white; font-size:24px; margin-bottom:16px; box-shadow:0 8px 24px rgba(124,58,237,0.3);">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h3 style="color:var(--text-primary); font-size:20px; font-weight:800; margin-bottom:8px;">Pending Admin Approval</h3>
                    <p style="color:var(--text-muted); font-size:14px; line-height:1.6;">
                        Because you were referred directly by an Administrator, your account requires manual approval. 
                        You will be notified via email to complete your payment once approved.
                    </p>
                </div>

                <div style="background:var(--input-bg); border:1px solid var(--border-color); border-radius:12px; padding: 0 16px; margin-bottom:24px;">
                    <div class="info-row">
                        <span class="info-label">Selected Package</span>
                        <span class="info-value"><?php echo htmlspecialchars($productinfo, ENT_QUOTES, 'utf-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value" style="color:var(--accent-purple);">@<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value" style="color:#f59e0b;">Waiting for Approval</span>
                    </div>
                </div>

                <div style="text-align:center; color:var(--text-darker); font-size:13px; margin-top:16px;">
                    You will be notified once the admin approves your account. <br>
                    <a href="index.php" style="color:var(--accent-purple); font-weight:600; text-decoration:none; display:inline-block; margin-top:12px;">Return to Home</a>
                </div>
            <?php else: ?>
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="color:var(--text-muted); font-size:13px; margin-bottom:8px;">Amount Payable</div>
                    <div style="color:var(--accent-green); font-size:32px; font-weight:800;">INR <?php echo number_format((float)$amount, 2); ?></div>
                </div>

                <div style="background:var(--input-bg); border:1px solid var(--border-color); border-radius:12px; padding: 0 16px; margin-bottom:24px;">
                    <div class="info-row">
                        <span class="info-label">Selected Package</span>
                        <span class="info-value"><?php echo htmlspecialchars($productinfo, ENT_QUOTES, 'utf-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value" style="color:var(--accent-purple);">@<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($firstname, ENT_QUOTES, 'utf-8'); ?></span>
                    </div>
                </div>

                <form action="payu_checkout_redirect.php" method="post" name="payuForm">
                    <input type="hidden" name="PAYU_BASE_URL" value="<?php echo $PAYU_BASE_URL; ?>" />
                    <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY; ?>" />
                    <input type="hidden" name="hash" value="<?php echo htmlspecialchars($hash, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="txnid" value="<?php echo $txnid; ?>" />
                    <input type="hidden" name="amount" value="<?php echo $amount; ?>" />
                    <input type="hidden" name="firstname" value="<?php echo htmlspecialchars($firstname, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="productinfo" value="<?php echo htmlspecialchars($productinfo, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="udf1" value="<?php echo htmlspecialchars($udf1, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="udf2" value="" />
                    <input type="hidden" name="udf3" value="" />
                    <input type="hidden" name="udf4" value="" />
                    <input type="hidden" name="udf5" value="" />
                    <input type="hidden" name="surl" value="<?php echo htmlspecialchars($surl, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="furl" value="<?php echo htmlspecialchars($furl, ENT_QUOTES, 'utf-8'); ?>" />
                    <input type="hidden" name="curl" value="<?php echo htmlspecialchars($furl, ENT_QUOTES, 'utf-8'); ?>" />
                    
                    <button type="submit" class="pay-btn">
                        <i class="fa-solid fa-lock"></i> Pay Securely via PayU
                    </button>
                </form>
                
                <div style="text-align:center; color:var(--text-darker); font-size:12px; margin-top:16px;">
                    <i class="fa-solid fa-check-circle" style="color:var(--accent-green); margin-right:4px;"></i> 256-bit Secure Encryption
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
