<?php
include_once ("z_db.php");
include_once("password_helper.php");
include_once("smtp_helper.php");


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['femail']))
{

$email=$_POST['femail'] ?? '';
$status=1;
if($status==1){

$status = "OK";
$msg="";
//checking constraints
if ( strlen($email) < 1 ){
$msg=$msg."Please Enter Your Email Id.<BR>";
$status= "NOTOK";}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
$msg=$msg."Email Id Not Valid, Please Enter The Correct Email Id .<BR>";
$status= "NOTOK";
}


$numrows = mlmp_pdo_count($pdo, "SELECT count(*) FROM affiliateuser where email = ?", [$email]);

if(($numrows) == 0) {
$msg=$msg."Your account not found or your account is inactive. Please contact your administrator.<BR>";
$status= "NOTOK";}

$row2 = mlmp_pdo_fetch($pdo, "SELECT wlink FROM settings where sno=0");
if($row2) {
    $wlink=$row2['wlink']; //assigning website address
}
}

$newpassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*') , 0 , 14 );

if ( strlen($newpassword) < 8 ){
$msg=$msg."Password Can not generated, system error. Try again.<BR>";
$status= "NOTOK";}


if($status=="OK")
{
$row2111 = mlmp_pdo_fetch($pdo, "SELECT etext FROM emailtext where code='FORGOTPASSWORD'");
if($row2111) {
    $emailtext=$row2111['etext']; //assigning email text for email
}

$hashedPassword = mlmp_hash_password($newpassword);
$stmt = $pdo->prepare("UPDATE affiliateuser SET password = ? WHERE email = ?");
$re = $stmt->execute([$hashedPassword, $email]);
if($re)
{

$message = $emailtext . "This is your new password : <b> $newpassword </b><br><br>";
$to = $email;
$subject = "Password Request";
mlmp_send_mail($to, $subject, $message);

echo "<br><center><font face='Verdana' size='2' color=red>Your password has been sent to your registered mail id. Please check your junk or spam folder if you do not find in your inbox. </font><br>";
}
else
{
 print "<center><font face='Verdana' size='2' color=red><br>We have found some technical glitch and unable to process your request. Please Ask Admin or try again after some time.</font><br>";
}
//updating status if validation passes

}
else{
$errormsg = "<div class='alert'>$msg</div>"; //priting error if found in validation


}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Recover Password - MLM Platform</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style type="text/css">
:root {
    --bg-main: linear-gradient(180deg, #8fc8ea 0%, #c9e7f9 58%, #eef7fd 100%);
    --bg-card: rgba(255, 255, 255, 0.68);
    --bg-input: #eef5fb;
    --bg-input-focus: #e6f1fa;
    --border-color: rgba(121, 163, 193, 0.34);
    --border-input: rgba(121, 163, 193, 0.42);
    --text-primary: #0d1b2a;
    --text-secondary: #425f75;
    --text-muted: #748da1;
    --glow-color-1: rgba(123, 174, 210, 0.28);
    --glow-color-2: rgba(173, 212, 238, 0.28);
    --card-shadow: 0 24px 52px rgba(72, 114, 142, 0.20);
    --btn-primary: linear-gradient(180deg, #1a1f2d 0%, #0c1019 100%);
    --btn-primary-hover: linear-gradient(180deg, #2a3142 0%, #111827 100%);
    --logo-bg: rgba(255, 255, 255, 0.74);
    --alert-bg: rgba(255, 119, 119, 0.12);
    --alert-border: rgba(223, 115, 115, 0.28);
    --alert-text: #b14d4d;
    --glass-blur: blur(12px);
}

html, body {`r`n    height: 100%;`r`n    font-family: 'Inter', sans-serif;`r`n    background: var(--bg-main);`r`n    background-attachment: fixed;`r`n    color: var(--text-secondary);
    transition: background 0.4s cubic-bezier(0.4, 0, 0.2, 1), color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.custom-card {
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.custom-input {
    background-color: var(--bg-input);
    border: 1px solid var(--border-input);
    color: var(--text-primary);
    transition: all 0.2s ease-in-out;
}

.custom-input:focus {
    background-color: var(--bg-input-focus);
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

.custom-input::placeholder {
    color: var(--text-muted);
}

.text-primary-custom {
    color: var(--text-primary);
    transition: color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.text-secondary-custom {
    color: var(--text-secondary);
    transition: color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.text-muted-custom {
    color: var(--text-muted);
    transition: color 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.alert {
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid var(--alert-border);
    background: var(--alert-bg);
    color: var(--alert-text);
    font-size: 13px;
    margin-bottom: 20px;
    position: relative;
    line-height: 1.5;
    transition: all 0.3s ease;
}

.alert .close {
    position: absolute;
    right: 12px;
    top: 10px;
    background: none;
    border: none;
    color: var(--alert-text);
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    opacity: 0.8;
    transition: opacity 0.2s ease;
}

.alert .close:hover {
    opacity: 1;
}

.btn-primary-gradient {
    background: var(--btn-primary);
    transition: background 0.4s ease;
}
.btn-primary-gradient:hover {
    background: var(--btn-primary-hover);
}

@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}

.floating-glow-1 {
    animation: float 8s ease-in-out infinite;
    background-color: var(--glow-color-1);
    transition: background-color 0.4s ease;
}

.floating-glow-2 {
    animation: float 12s ease-in-out infinite;
    background-color: var(--glow-color-2);
    transition: background-color 0.4s ease;
}
</style>
<div id="google_translate_element" class="absolute top-4 right-4 z-50"></div>
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, multilanguagePage: true}, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden">

<!-- Global Glowing Background Elements (Floating freely across the whole screen!) -->
<div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] floating-glow-1 pointer-events-none z-0"></div>
<div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] floating-glow-2 pointer-events-none z-0"></div>
<div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] floating-glow-1 pointer-events-none z-0 opacity-40"></div>

<main class="flex-grow flex flex-col lg:flex-row relative z-10">
  
  <!-- Left Side: Dynamic Premium Branding Visuals (Desktop Only) -->
  <div class="hidden lg:flex lg:w-7/12 relative bg-transparent flex-col justify-between p-16 transition-all duration-500">
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <div class="p-1.5 rounded-lg bg-[var(--logo-bg)] border border-[var(--border-color)] transition-colors">
      </div>
      <span class="text-xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Main Hero Text -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-500/10 border border-indigo-500/20 rounded-full inline-block mb-6 tracking-wide">RECOVER ACCESS</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-primary-custom leading-tight mb-6 tracking-tight">
        Recover your network workspace access.
      </h1>
      <p class="text-secondary-custom text-lg leading-relaxed mb-8">
        Provide your registered email address to instantly receive a newly generated, secure password and regain access to your member dashboard.
      </p>

      <!-- Metric Badges -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="custom-card p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-primary-custom text-xl font-extrabold">20 Levels</div>
          <div class="text-[10px] text-muted-custom font-bold tracking-wider uppercase mt-1">Matrix Plan</div>
        </div>
        <div class="custom-card p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-emerald-500 text-xl font-extrabold">INR Payouts</div>
          <div class="text-[10px] text-muted-custom font-bold tracking-wider uppercase mt-1">Automated</div>
        </div>
        <div class="custom-card p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-primary-custom text-xl font-extrabold">24/7</div>
          <div class="text-[10px] text-muted-custom font-bold tracking-wider uppercase mt-1">Live Access</div>
        </div>
      </div>
    </div>

    <!-- Branding Footer -->
    <div class="text-xs text-muted-custom relative z-10">
      &copy; 2026 MLM Platform Inc. All rights reserved. Secure SSL Encrypted Connection.
    </div>
  </div>

  <!-- Right Side: Centered Recover Password Form -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 pt-24 pb-12 sm:pt-28 lg:py-12 relative bg-transparent transition-colors duration-400">
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2.5 mb-10 relative z-10">
      <div class="p-1.5 rounded-lg bg-[var(--logo-bg)] border border-[var(--border-color)]">
      </div>
      <span class="text-lg font-bold tracking-wider text-primary-custom">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10">
      
      <!-- Welcome Card Container -->
      <div class="custom-card p-8 sm:p-10 rounded-2xl">
        <!-- Welcome Header -->
        <div class="mb-8">
          <h2 class="text-2xl font-extrabold text-primary-custom tracking-tight mb-2">Recover Password</h2>
          <p class="text-secondary-custom text-sm">Enter your registered email address below, and we will send you a new secure password.</p>
        </div>

        <!-- Error message alerts -->
        <?php 
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($errormsg) && ($errormsg!="")) {
          print $errormsg;
        }
        ?>

        <!-- Form Container -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post" class="space-y-6">
          
          <!-- E-mail Input -->
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="femail">Registered E-Mail</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                <i class="fa-solid fa-envelope text-xs"></i>
              </span>
              <input type="email" id="femail" name="femail" placeholder="Enter your email address" required
                     class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" 
                  class="w-full btn-primary-gradient text-white rounded-xl py-3 text-sm font-bold tracking-wide transition-all duration-300 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/25 transform active:scale-[0.98]">
            Send Me Password
          </button>

          <!-- Divider -->
          <div class="flex items-center my-6">
            <div class="flex-grow border-t border-[var(--border-color)]"></div>
            <span class="mx-3 text-[10px] text-muted-custom font-bold uppercase tracking-wider">Remember password?</span>
            <div class="flex-grow border-t border-[var(--border-color)]"></div>
          </div>

          <!-- Secondary Sign In CTA -->
          <a href="index.php" 
             class="w-full flex items-center justify-center bg-[var(--logo-bg)] hover:bg-[var(--border-color)] border border-[var(--border-color)] text-primary-custom rounded-xl py-3 text-sm font-bold transition-all duration-200 text-center">
            Sign In Now
          </a>

          <!-- Create Account CTA -->
          <div class="text-center pt-2">
            <a href="signup.php" class="text-xs font-bold text-muted-custom hover:text-indigo-500 dark:hover:text-indigo-600 transition duration-150">
              Don't have an account? <span class="text-indigo-500 dark:text-indigo-600">Register Here</span>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

</body>
</html>
