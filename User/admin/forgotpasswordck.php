<?php
//collecting data
include_once ("z_db.php");
include_once("../password_helper.php");

$html_response = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($con, $_POST['femail'] ?? '');
    $status = 1;
    
    if ($status == 1) {
        $status = "OK";
        $msg = "";
        
        //checking constraints
        if (strlen($email) < 1) {
            $msg = $msg . "Please Enter Your Email Id.<br>";
            $status = "NOTOK";
        }
        
        if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email)) {
            $msg = $msg . "Email Id is not valid. Please enter a correct email ID.<br>";
            $status = "NOTOK";
        }
        
        $result = mysqli_query($con, "SELECT count(*) FROM affiliateuser where email = '$email'");
        $row = mysqli_fetch_row($result);
        $numrows = $row[0];
        
        if (($numrows) == 0) {
            $msg = $msg . "Your account was not found or is currently inactive. Please contact the administrator.<br>";
            $status = "NOTOK";
        }
    }
    
    if ($status == "OK") {
        $newpassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*'), 0, 14);
        $hashedPassword = mysqli_real_escape_string($con, mlmp_hash_password($newpassword));
        $re = mysqli_query($con, "UPDATE affiliateuser SET password = '$hashedPassword' WHERE email = '$email'");
        
        // dispatching mail
        $to = $email;
        $subject = "Password Request";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: <no-reply@mlm-platform.local>' . "\r\n";
        
        $message = "As per your request, we have generated a new password.<br> Your new password is <b> $newpassword </b><br><br>Regards";
        @mail($to, $subject, $message, $headers);
        
        $html_response = '
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/10 text-emerald-600 mb-6 border border-emerald-500/20 shadow-lg shadow-emerald-500/5 animate-pulse">
                <i class="fa-solid fa-circle-check text-3xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3">Email Dispatched!</h2>
            <p class="text-slate-600 text-sm leading-relaxed mb-8">
                Your new administrative password has been sent to <span class="text-indigo-600 font-semibold">' . mlmp_escape($email) . '</span>. Please check your junk or spam folder if it doesn\'t appear in your inbox shortly.
            </p>
            <a href="index.php" class="inline-block w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transform active:scale-[0.98]">
                Proceed to Log In
            </a>
        </div>';
    } else {
        $html_response = '
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-500/10 text-rose-400 mb-6 border border-rose-500/20 shadow-lg shadow-rose-500/5">
                <i class="fa-solid fa-triangle-exclamation text-2xl animate-bounce"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3">Recovery Failed</h2>
            <div class="bg-rose-500/5 border border-rose-500/10 rounded-xl p-4 mb-8 text-rose-400 text-sm leading-relaxed text-left">
                <strong>Please resolve the following error:</strong><br>' . $msg . '
            </div>
            <button onclick="history.go(-1)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-slate-800 rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer border border-slate-200 shadow-md transform active:scale-[0.98]">
                <i class="fa-solid fa-arrow-left mr-2"></i> Go Back & Retry
            </button>
        </div>';
    }
} else {
    $html_response = '
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-500/10 text-amber-600 mb-6 border border-amber-500/20 shadow-lg shadow-amber-500/5">
            <i class="fa-solid fa-ban text-2xl"></i>
        </div>
        <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3">Invalid Access</h2>
        <p class="text-slate-600 text-sm leading-relaxed mb-8">
            This processing routine cannot be accessed directly. Please use the form interface to reset your password.
        </p>
        <a href="forgotpassword.php" class="inline-block w-full bg-indigo-600 hover:bg-indigo-700 text-slate-800 rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 border border-slate-200">
            Reset Password
        </a>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Admin Password Recovery</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
body { font-family: 'Inter', sans-serif; }
</style>
</head>
<body class="bg-[#090d16] text-slate-700 flex flex-col justify-between min-h-screen">

<main class="flex-grow flex items-center justify-center px-4 py-16 relative">
    <!-- Glowing background lights -->
    <div class="absolute top-1/4 left-1/4 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 translate-x-1/2 translate-y-1/2 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>

    <div class="max-w-md w-full bg-[#0d1222]/80 border border-slate-200 backdrop-blur-md rounded-2xl p-8 sm:p-10 shadow-2xl relative z-10">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-2 mb-8">
            <span class="text-md font-extrabold tracking-wider text-white">MLM Platform</span>
        </div>

        <?php echo $html_response; ?>
    </div>
</main>

</body>
</html>

