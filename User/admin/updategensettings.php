<?php
header("refresh:1;url=gensettings.php");
include('z_db.php'); // Connection details
session_start();

if (!isset($_SESSION['adminidusername'])) {
    print "
        <script language='javascript'>
            window.location = 'index.php';
        </script>
    ";
    exit;
}

$status = "OK"; // Initial status
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetching details through post method
    $wlink = $_POST['wlink'] ?? '';
    $coname = $_POST['coname'] ?? '';
    $coemail = $_POST['coemail'] ?? '';
    $codetail = $_POST['codetail'] ?? '';
    $payemail = $_POST['payemail'] ?? ''; // Merchant Key
    $fb = $_POST['fblink'] ?? '';
    $tw = $_POST['twitterlink'] ?? '';
    $sno = $_POST['sno'] ?? '';
    $maintain = $_POST['maintain'] ?? '';

    $hdrtext = $_POST['hdrtext'] ?? '';
    $ftrtext = $_POST['ftrtext'] ?? '';
    $payzaid = $_POST['payzaid'] ?? ''; // Salt
    $solidid = $_POST['solidid'] ?? '';
    $solidbutton = $_POST['solidbuttonid'] ?? '';

    $alwdpaypal = $_POST['alwdpaypal'] ?? '0'; // PayU Enable
    $alwdpayment = $_POST['alwdpayment'] ?? '2'; // Default Bank manual
    $alwdpayza = $_POST['alwdpayza'] ?? '0';
    $alwdsolid = $_POST['alwdsolid'] ?? '0';

    $smtp_enabled = isset($_POST['smtp_enabled']) ? (int)$_POST['smtp_enabled'] : 0;
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_port = isset($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
    $smtp_username = $_POST['smtp_username'] ?? '';
    $smtp_password = $_POST['smtp_password'] ?? '';
    $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';

    $referral_bonus_referrer = (float)($_POST['referral_bonus_referrer'] ?? 50.00);
    $admin_referral_bonus = (float)($_POST['admin_referral_bonus'] ?? 25.00);

    if (strlen($wlink) < 2) {
        $msg .= "Website Link cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($coname) < 2) {
        $msg .= "Company Name cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($coemail) < 4) {
        $msg .= "Admin Email cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($codetail) < 4) {
        $msg .= "Company Address cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($payemail) < 2) {
        $msg .= "PayU Merchant Key cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($payzaid) < 2) {
        $msg .= "PayU Salt cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if (strlen($ftrtext) < 1) {
        $msg .= "Footer Text cannot be empty.<BR>";
        $status = "NOTOK";
    }

    if ($status == "OK") {
        try {
            $sql = "UPDATE settings SET 
                    header = ?, 
                    footer = ?, 
                    wlink = ?, 
                    coname = ?, 
                    invoicedetails = ?, 
                    email = ?, 
                    fblink = ?, 
                    twitterlink = ?, 
                    paypalid = ?, 
                    maintain = ?, 
                    alwdpayment = ?, 
                    payzaid = ?, 
                    solidtrustid = ?, 
                    solidbutton = ?,
                    smtp_enabled = ?,
                    smtp_host = ?,
                    smtp_port = ?,
                    smtp_username = ?,
                    smtp_password = ?,
                    smtp_encryption = ?,
                    referral_bonus_referrer = ?,
                    admin_referral_bonus = ?
                    WHERE sno = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $hdrtext,
                $ftrtext,
                $wlink,
                $coname,
                $codetail,
                $coemail,
                $fb,
                $tw,
                $payemail,
                $maintain,
                $alwdpayment,
                $payzaid,
                $solidid,
                $solidbutton,
                $smtp_enabled,
                $smtp_host,
                $smtp_port,
                $smtp_username,
                $smtp_password,
                $smtp_encryption,
                $referral_bonus_referrer,
                $admin_referral_bonus,
                $sno
            ]);
            
            // Update gateway statuses securely
            $stmt1 = $pdo->prepare("UPDATE paymentgateway SET status = ? WHERE id = 1");
            $stmt1->execute([$alwdpaypal]);
            
            $stmt2 = $pdo->prepare("UPDATE paymentgateway SET status = ? WHERE id = 3");
            $stmt2->execute([$alwdpayza]);
            
            $stmt3 = $pdo->prepare("UPDATE paymentgateway SET status = ? WHERE id = 4");
            $stmt3->execute([$alwdsolid]);
            
            print "System settings updated successfully! Redirecting...";
        } catch (Exception $e) {
            print "An error occurred while updating settings. Please try again. Redirecting...";
            error_log("Settings Update Failed: " . $e->getMessage());
        }
    } else {
        echo "<font face='Verdana' size='2' color=red>$msg</font><br><input type='button' value='Retry' onClick='history.go(-1)'>";
    }
} else {
    echo "Invalid request.";
}
?>
