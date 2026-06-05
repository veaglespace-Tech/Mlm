<?php
if(!isset($_SESSION)){
    session_start();
}
include_once("z_db.php");

// Safety check: if no registered username in session, redirect to signup
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

$signup_data = $_SESSION['signup_data'];
$firstname = $signup_data['fname'];
$email = $signup_data['email'];
$phone = $signup_data['mobile'];
$amount = '1000.00';

// Insert user securely into database before going to PayU (solves SameSite cookie session drop issue)
$queryUser = "SELECT Id, pcktaken FROM affiliateuser WHERE username = ?";
$stmtUser = $pdo->prepare($queryUser);
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();

if (!$user && isset($_SESSION['signup_data'])) {
    $ref = $signup_data['referedby'];
    
    // Dynamic BFS binary placement detection to prevent parent/child tree race conditions
    $sponsor = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ? LIMIT 1", [$ref]);
    $sponsorId = (int)($sponsor['Id'] ?? 0);
    $parentId = $sponsorId;
    $position = 'L'; // default

    if ($sponsorId > 0) {
        $queue = [$sponsorId];
        $found = false;
        
        while (!empty($queue) && !$found) {
            $currentId = array_shift($queue);
            
            // Check children of $currentId
            $children = mlmp_pdo_fetch_all($pdo, "SELECT Id, position FROM affiliateuser WHERE parent_id = ?", [$currentId]);
            $hasL = false;
            $hasR = false;
            $lId = 0;
            $rId = 0;
            
            foreach ($children as $child) {
                if ($child['position'] == 'L') {
                    $hasL = true;
                    $lId = (int)$child['Id'];
                } elseif ($child['position'] == 'R') {
                    $hasR = true;
                    $rId = (int)$child['Id'];
                }
            }
            
            if (!$hasL) {
                $parentId = $currentId;
                $position = 'L';
                $found = true;
            } elseif (!$hasR) {
                $parentId = $currentId;
                $position = 'R';
                $found = true;
            } else {
                // Both slots taken, push them to queue to check their children later
                // To maintain Top-to-Bottom, Left-to-Right order, push Left then Right
                $queue[] = $lId;
                $queue[] = $rId;
            }
        }
    }

    $sql = "INSERT INTO affiliateuser(username, password, fname, address, email, referedby, ipaddress, mobile, active, doj, country, payment, signupcode, tamount, pcktaken, expiry, parent_id, position) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'PayU', ?, 0, ?, ?, ?, ?)";
    mlmp_pdo_execute($pdo, $sql, [
        $username,
        $signup_data['password'],
        $signup_data['fname'],
        $signup_data['address'],
        $signup_data['email'],
        $signup_data['referedby'],
        $signup_data['ipaddress'],
        $signup_data['mobile'],
        $signup_data['doj'],
        $signup_data['country'],
        $signup_data['signupcode'],
        $signup_data['package'],
        $signup_data['expiry'],
        $parentId,
        $position
    ]);
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
$hashSequence = "$MERCHANT_KEY|$txnid|$amount|$productinfo|$firstname|$email|||||||||||$SALT";
$hashV1 = strtolower(hash('sha512', $hashSequence));
$hash = $hashV1;

// PayU checkout expects a JSON hash object only when the merchant account uses Salt v2.
if ($SALT_V2 !== '') {
    $hashPayload = ['v1' => $hashV1];
    if ($SALT_V2 !== '') {
        $v2SignedString = "$MERCHANT_KEY|$txnid|$amount|$productinfo|$firstname|$email|||||||||||";
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
            <div style="text-align:center; margin-bottom:24px;">
                <div style="color:var(--text-muted); font-size:13px; margin-bottom:8px;">Amount Payable</div>
                <div style="color:var(--accent-green); font-size:32px; font-weight:800;">INR 1,000.00</div>
            </div>

            <div style="background:var(--input-bg); border:1px solid var(--border-color); border-radius:12px; padding: 0 16px; margin-bottom:24px;">
                <div class="info-row">
                    <span class="info-label">Selected Kit</span>
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

            <form action="<?php echo $PAYU_BASE_URL; ?>" method="post" name="payuForm">
                <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY; ?>" />
                <input type="hidden" name="hash" value="<?php echo htmlspecialchars($hash, ENT_QUOTES, 'utf-8'); ?>" />
                <input type="hidden" name="txnid" value="<?php echo $txnid; ?>" />
                <input type="hidden" name="amount" value="<?php echo $amount; ?>" />
                <input type="hidden" name="firstname" value="<?php echo htmlspecialchars($firstname, ENT_QUOTES, 'utf-8'); ?>" />
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'utf-8'); ?>" />
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'utf-8'); ?>" />
                <input type="hidden" name="productinfo" value="<?php echo htmlspecialchars($productinfo, ENT_QUOTES, 'utf-8'); ?>" />
                <input type="hidden" name="udf1" value="" />
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
        </div>
    </div>
</div>

</body>
</html>
