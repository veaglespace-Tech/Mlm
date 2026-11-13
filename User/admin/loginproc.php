<?php
//session_start(); //starting session
include('z_db.php'); //connection details
include_once('../password_helper.php');
$status = "OK"; //initial status
$msg="";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$username = trim($_POST['username'] ?? ''); //fetching details through post method
$password = $_POST['password'] ?? '';

if ( strlen($username ?? '') < 3 ){
$msg=$msg."Username or Email must be at least 3 characters<BR>";
$status= "NOTOK";}

if ( strlen($password ?? '') < 6 ){ //checking if password is greater than or equal to 6
$msg=$msg."Password must be at least 6 characters<BR>";
$status= "NOTOK";}

if($status=="OK"){

// Retrieve admin details by username OR email and verify password
$adminRow = mlmp_pdo_fetch($pdo, "SELECT Id, username, email, password FROM affiliateuser WHERE (username = ? OR email = ?) AND active = 1 AND level = 1 LIMIT 1", [$username, $username]);
$num = 0;
$adminUsername = '';
if ($adminRow && mlmp_password_matches($password, $adminRow['password'])) {
    $num = 1;
    $storedPassword = $adminRow['password'];
    $adminUsername = $adminRow['username'];
}

if (($num) == 1) {
    mlmp_upgrade_password_hash($pdo, $adminUsername, $password, $storedPassword);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Admin login requires OTP verification
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['admin_otp_pending'] = [
        'id'         => $adminRow['Id'] ?? 1,
        'username'   => $adminUsername,
        'email'      => $adminRow['email'] ?? '',
        'otp'        => $otp,
        'expires_at' => time() + 600, // 10 minutes
        'created_at' => time(),
        'attempts'   => 0
    ];

    include_once("../smtp_helper.php");
    $subject = "Admin Login Verification Code: " . $otp;
    $body = "
    <div style='font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;'>
        <h2 style='color:#1e1b4b;margin-bottom:8px;'>Admin Two-Factor Authentication</h2>
        <p style='color:#475569;font-size:14px;line-height:1.5;'>A login request to the administrative command center was initiated for account: <b>" . htmlspecialchars($adminUsername) . "</b>.</p>
        <div style='background:#f1f5f9;border-radius:12px;padding:18px;text-align:center;margin:20px 0;'>
            <div style='font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:700;'>Your One-Time Password (OTP)</div>
            <div style='font-size:34px;font-weight:800;letter-spacing:8px;color:#4f46e5;margin-top:6px;'>$otp</div>
        </div>
        <p style='color:#64748b;font-size:12px;line-height:1.4;'>Valid for 10 minutes. If you did not initiate this login, please secure your admin account.</p>
    </div>";
    @mlmp_send_mail($adminRow['email'] ?? '', $subject, $body);

    header("Location: ../index.php");
    exit;
} else {
    echo "<br/><br/><br/><br/><br/><center>Username/Email and Password Not Matched Or May Be Your Account Is Inactive. <br/> Contact Admin For More Information.</center>";
}} 
else {
        
echo "<font face='Verdana' size='2' color=red>$msg</font><br><input type='button' value='Retry' onClick='history.go(-1)'>"; //printing errors
	 
}
} else {
    echo "Invalid request.";
}

?>
