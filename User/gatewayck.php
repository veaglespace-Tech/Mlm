<?php
include_once ("z_db.php");
if(!isset($_SESSION)){
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $pgateid = $_POST['paymentgateway'] ?? '';
    $userid = $_POST['username'] ?? '';

    $user = mlmp_pdo_fetch($pdo, "SELECT id, pcktaken FROM affiliateuser WHERE username = ?", [$userid]);
    if ($user) {
        $pck = $user['pcktaken'];
        $pkg = mlmp_pdo_fetch($pdo, "SELECT * FROM packages WHERE id = ?", [$pck]);
        
        if ($pkg) {
            $total = $pkg['price'] + $pkg['tax'];
            $pcur = $pkg['currency'];

            if($pgateid == 1) { // PayPal
                $_SESSION['paypalidsession'] = $userid;
                header("Location: thankyou.php?username=$userid");
                exit;
            } else {
                header("Location: gateway.php?username=$userid");
                exit;
            }
        }
    }
}
?>

