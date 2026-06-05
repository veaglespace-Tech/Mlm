<?php
include_once("z_db.php");
include_once("password_helper.php");
$settings = mlmp_pdo_fetch($pdo, "SELECT maintain FROM settings WHERE sno=0");
$main = $settings['maintain'] ?? 0;
if($main==1 || $main==3)
{
	print "
				<script language='javascript'>
					window.location = 'maintain.php';
				</script>
			";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']))
{
$status = "OK"; //initial status
$msg="";
	$username=$_POST['username'] ?? ''; //fetching details through post method
     $password = $_POST['password'] ?? '';

if ( strlen($username) < 6 ){
$msg=$msg."Username must be more than 5 char legth<BR>";
$status= "NOTOK";}

if ( strlen($password) < 6 ){ //checking if password is greater then 8 or not
$msg=$msg."Password must be more than 5 char legth<BR>";
$status= "NOTOK";}

if($status=="OK"){

// Retrieve user details and verify the password in PHP so old plaintext
// passwords can be migrated to password_hash without breaking login.
$userRow = mlmp_pdo_fetch($pdo, "SELECT password FROM affiliateuser WHERE username = ? AND active = 1 AND level = 2 LIMIT 1", [$username]);
$num = 0;
if ($userRow && mlmp_password_matches($password, $userRow['password'])) {
    $num = 1;
    $storedPassword = $userRow['password'];
}

if (($num) == 1) {

mlmp_upgrade_password_hash($con, $username, $password, $storedPassword);
session_start();
        // Set username session variable
        $_SESSION['username'] = $username;
		
        // Jump to secured page
		print "
				<script language='javascript'>
					window.location = 'dashboard.php?page=dashboard%location=index.php';
				</script>"; 
}



else{
$errormsg= "
<div class='alert alert-danger'>
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                    <i class='fa fa-ban-circle'></i><strong>Please Fix Below Errors : </br></strong>Username And Password Does Not Match Or Your Account Is Inactive.</div>"; //printing error if found in validation
				
}} 
else {
        
$errormsg= "
<div class='alert alert-danger'>
                    <button type='button' class='close' data-dismiss='alert'>&times;</button>
                    <i class='fa fa-ban-circle'></i><strong>Please Fix Below Errors : </br></strong>".$msg."</div>"; //printing error if found in validation
				
	 
}
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Sign In - MLM Platform</title>
<meta name="description" content="Grow your network with a cleaner affiliate command center." />
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

html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background: var(--bg-main);
    background-attachment: fixed;
    color: var(--text-secondary);
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

/* Beautiful custom floating animations for glows */
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

/* Modern styling for Google Translate element */
#google_translate_element select {
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 9999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    outline: none;
    cursor: pointer;
    transition: all 0.3s ease;
}
#google_translate_element select:hover {
    border-color: #6366f1;
}
.goog-te-gadget {
    color: transparent !important;
}
.goog-te-gadget span {
    display: none !important;
}
.btn-primary-gradient {
    background: var(--btn-primary);
    transition: background 0.4s ease;
}
.btn-primary-gradient:hover {
    background: var(--btn-primary-hover);
}
body {
    top: 0px !important;
}
</style>
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

<!-- Top Control Bar (Language only) -->
<div class="absolute top-4 right-4 z-50 flex items-center gap-3">
    <!-- Google Translate Element -->
    <div id="google_translate_element" class="opacity-90 hover:opacity-100 transition-opacity"></div>
</div>

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
      <span class="px-3 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-600 bg-indigo-500/10 border border-indigo-500/20 rounded-full inline-block mb-6 tracking-wide">AFFILIATE PORTAL</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-primary-custom leading-tight mb-6 tracking-tight">
        Grow your network with a cleaner command center.
      </h1>
      <p class="text-secondary-custom text-lg leading-relaxed mb-8">
        Track referrals, active packages, real-time earnings, and secure automated payouts from one beautiful member dashboard.
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

  <!-- Right Side: Centered Modern Glassmorphism Login Card -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 pt-24 pb-12 sm:pt-28 lg:py-12 relative bg-transparent transition-colors duration-400">
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2.5 mb-10 relative z-10">
      <div class="p-1.5 rounded-lg bg-[var(--logo-bg)] border border-[var(--border-color)]">
      </div>
      <span class="text-lg font-bold tracking-wider text-primary-custom">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10">
      
      <!-- Login Card Container -->
      <div class="custom-card p-8 sm:p-10 rounded-2xl">
        <!-- Welcome Header -->
        <div class="mb-8">
          <h2 class="text-2xl font-extrabold text-primary-custom tracking-tight mb-2">Welcome back!</h2>
          <p class="text-secondary-custom text-sm">Please sign in to your affiliate account to continue.</p>
        </div>

        <!-- Error message alerts -->
        <?php 
        if($_SERVER['REQUEST_METHOD'] == 'POST' && ($errormsg!="")) {
          print $errormsg;
        }
        ?>

        <!-- Form Container -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post" class="space-y-6">
          
          <!-- Username Input -->
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="username">Username</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                <i class="fa-solid fa-user text-xs"></i>
              </span>
              <input type="text" id="username" name="username" placeholder="Enter your username" minlength="6" required
                     class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
            </div>
          </div>

          <!-- Password Input -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="password">Password</label>
              <a href="forgotpassword.php" class="text-xs font-semibold text-indigo-500 hover:text-indigo-600 dark:text-indigo-600 dark:hover:text-indigo-300 transition duration-150">Forgot password?</a>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                <i class="fa-solid fa-lock text-xs"></i>
              </span>
              <input type="password" id="password" name="password" placeholder="Enter your password" minlength="6" required
                     class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
            </div>
          </div>

          <!-- Sign In Button -->
          <button type="submit" 
                  class="w-full btn-primary-gradient text-white rounded-xl py-3 text-sm font-bold tracking-wide transition-all duration-300 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/25 transform active:scale-[0.98]">
            Sign In
          </button>

          <!-- Divider -->
          <div class="flex items-center my-6">
            <div class="flex-grow border-t border-[var(--border-color)]"></div>
            <span class="mx-3 text-[10px] text-muted-custom font-bold uppercase tracking-wider">New to portal?</span>
            <div class="flex-grow border-t border-[var(--border-color)]"></div>
          </div>

          <!-- Secondary Register CTA -->
          <a href="signup.php" 
             class="w-full flex items-center justify-center bg-[var(--logo-bg)] hover:bg-[var(--border-color)] border border-[var(--border-color)] text-primary-custom rounded-xl py-3 text-sm font-bold transition-all duration-200 text-center">
            Create an Account
          </a>

        </form>
      </div>
    </div>
  </div>
</main>



</body>
</html>
