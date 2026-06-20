<?php
if(!isset($_SESSION)){
    session_start();
}
include_once("z_db.php");
include_once("password_helper.php");
include_once("smtp_helper.php");

// Use PDO for maintenance check
$settings = mlmp_pdo_fetch($pdo, "SELECT maintain FROM settings WHERE sno = 0");
if ($settings && ($settings['maintain'] == 2 || $settings['maintain'] == 3)) {
    header("Location: maintain.php");
    exit;
}

// Clear session data if this is a fresh page load (not a validation error POST and not clicking 'Back' from the product page)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['back'])) {
    unset($_SESSION['signup_data']);
}

$errormsg = "";
$name = $_POST['fname'] ?? $_SESSION['signup_data']['fname'] ?? '';
$username = $_POST['username'] ?? $_SESSION['signup_data']['username'] ?? '';
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';
$email = $_POST['email'] ?? $_SESSION['signup_data']['email'] ?? '';
$mobile = $_POST['mobile'] ?? $_SESSION['signup_data']['mobile'] ?? '';
$ref = $_POST['referral'] ?? $_SESSION['signup_data']['referedby'] ?? $_GET['aff'] ?? '';
$address = $_POST['address'] ?? $_SESSION['signup_data']['address'] ?? '';
$country = $_POST['country'] ?? $_SESSION['signup_data']['country'] ?? '';
$country = $_POST['country'] ?? $_SESSION['signup_data']['country'] ?? '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']))
{
    $status = "OK";
    $msg = "";

    // Validation
    if(strlen($username) < 6){
        $msg .= "User Id Should Contain Minimum 6 CHARACTERS.<BR>";
        $status = "NOTOK";
    }

    if(!ctype_alnum($username)){
        $msg .= "User Id Should Contain Alphanumeric Chars Only.<BR>";
        $status = "NOTOK";
    }

    // Clean up abandoned registrations (active = 0) that match the provided username, email, or mobile.
    // This allows users to retry registration if their previous payment failed or they abandoned checkout.
    try {
        $stmt_cleanup = $pdo->prepare("DELETE FROM affiliateuser WHERE active = 0 AND (username = ? OR email = ? OR mobile = ?)");
        $stmt_cleanup->execute([$username, $email, $mobile]);
    } catch (Exception $e) {}

    // Also clean up previously rejected records from pending_registrations so they can try again
    try {
        $stmt_pending_cleanup = $pdo->prepare("DELETE FROM pending_registrations WHERE admin_approval_status = 'Rejected' AND (username = ? OR email = ? OR mobile = ?)");
        $stmt_pending_cleanup->execute([$username, $email, $mobile]);
    } catch (Exception $e) {}

    if(mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE username = ?", [$username]) > 0){
        $msg .= "Userid Already Exists. Please Try Another One.<BR>";
        $status = "NOTOK";
    }

    if(mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE mobile = ?", [$mobile]) > 0){
        $msg .= "Mobile Number Already Registered.<BR>";
        $status = "NOTOK";
    }

    if(mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE email = ?", [$email]) > 0){
        $msg .= "E-Mail Id Already Registered.<BR>";
        $status = "NOTOK";
    }

    if(mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE username = ?", [$ref]) == 0){
        $msg .= "Sponsor/Referral Username Not Found.<BR>";
        $status = "NOTOK";
    }



    if(strlen($password) < 8){
        $msg .= "Password Must Be More Than 8 Char Length.<BR>";
        $status = "NOTOK";
    }

    // Layer 1: Basic format check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg .= "Email Id Not Valid, Please Enter The Correct Email Id.<BR>";
        $status = "NOTOK";
    } else {
        // Layer 2: Strict format - local part min 3 chars, domain must NOT start with digit
        // Valid:   abhijeetambhore4@gmail.com  | user.name@company.co.in
        // Invalid: te@jgmail.com (too short)   | tejas@5gmail.com (digit domain)
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._%+\-]{2,}@[a-zA-Z][a-zA-Z0-9\-]*(\.[a-zA-Z0-9\-]+)*\.[a-zA-Z]{2,}$/', $email)) {
            $msg .= "Email Id Not Valid, Please Enter The Correct Email Id.<BR>";
            $status = "NOTOK";
        }
    }

    if($password !== $password2){
        $msg .= "Both Passwords Are Not Matching.<BR>";
        $status = "NOTOK";
    }

    if(empty($country)){
        $msg .= "Please Enter Your Country Name.<BR>";
        $status = "NOTOK";
    }

    // IP Handling
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    $ip_long = ip2long($ip) ?: 0;

    if ($status == "OK") {
        $cur_date = date("Y-m-d");
        $expiry = '2199-12-31';
        $scode = rand(1111111111, 9999999999);
        $hashedPassword = mlmp_hash_password($password);

        // Store registration details in session without inserting to database yet
        $_SESSION['signup_data'] = [
            'username' => $username,
            'password' => $hashedPassword,
            'fname' => $name,
            'address' => $address,
            'email' => $email,
            'referedby' => $ref,
            'ipaddress' => $ip_long,
            'mobile' => $mobile,
            'country' => $country,
            'expiry' => $expiry,
            'signupcode' => $scode,
            'doj' => $cur_date
        ];

        $_SESSION['reg_username'] = $username;
        $_SESSION['signup_success'] = "Registration details captured! Please select your complimentary product.";
        header("Location: select_product.php");
        exit;
    } else {
        $errormsg = "
        <div class='mb-6 p-4 rounded-xl border border-red-300 bg-red-50 text-red-800 text-sm flex items-start gap-3 relative transition-all duration-200 shadow-sm' id='errorAlert'>
            <div class='flex-grow'>
                <div class='font-bold flex items-center gap-1.5 mb-1 text-red-700 text-base'>
                    <i class='fa-solid fa-circle-exclamation'></i> Please Fix Below Errors:
                </div>
                <div class='space-y-1 list-disc font-medium text-sm text-red-700/90 mt-2'>" . $msg . "</div>
            </div>
            <button type='button' class='text-red-500 hover:text-red-800 transition cursor-pointer text-lg leading-none absolute top-3.5 right-3.5' onclick=\"document.getElementById('errorAlert').style.display='none';\">
                <i class='fa-solid fa-xmark text-sm'></i>
            </button>
        </div>";
    }
}

$ref_id = $_GET['aff'] ?? $ref;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<title>Register - MLM Platform</title>
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
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden">

<!-- Global Glowing Background Elements (Floating freely across the whole screen!) -->
<div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] floating-glow-1 pointer-events-none z-0"></div>
<div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] floating-glow-2 pointer-events-none z-0"></div>
<div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] floating-glow-1 pointer-events-none z-0 opacity-40"></div>
<main class="flex-grow flex flex-col lg:flex-row relative z-10">
  
  <!-- Left Side: Dynamic Premium Branding Visuals (Desktop Only) -->
  <div class="hidden lg:flex lg:w-4/12 relative bg-transparent flex-col justify-between p-12 transition-all duration-500 border-r border-[var(--border-color)]">
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <div class="p-1.5 rounded-lg bg-[var(--logo-bg)] border border-[var(--border-color)] transition-colors">
      </div>
      <span class="text-xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Main Hero Text -->
    <div class="my-auto relative z-10 max-w-sm">
      <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-500/10 border border-indigo-500/20 rounded-full inline-block mb-6 tracking-wide">JOIN TODAY</span>
      <h1 class="text-3xl lg:text-4xl font-extrabold text-primary-custom leading-tight mb-6 tracking-tight">
        Start your journey with a modern MLM workspace.
      </h1>
      <p class="text-secondary-custom text-sm leading-relaxed mb-8">
        Choose a premium plan, enter your details, join with your sponsor and take instant control of your network tracking.
      </p>

      <!-- Metric Badges -->
      <div class="space-y-3">
        <div class="custom-card p-3.5 rounded-xl flex items-center gap-3 hover:scale-[1.03] transform transition duration-300">
          <div class="w-8 h-8 rounded-lg bg-indigo-500/15 flex items-center justify-center text-indigo-600 text-sm"><i class="fa-solid fa-cube"></i></div>
          <div>
            <div class="text-primary-custom text-sm font-semibold">INR 1,000 Starter</div>
            <div class="text-[10px] text-muted-custom font-bold uppercase tracking-wider">Premium Entrance Tier</div>
          </div>
        </div>
        <div class="custom-card p-3.5 rounded-xl flex items-center gap-3 hover:scale-[1.03] transform transition duration-300">
          <div class="w-8 h-8 rounded-lg bg-emerald-500/15 flex items-center justify-center text-emerald-600 text-sm"><i class="fa-solid fa-bolt"></i></div>
          <div>
            <div class="text-primary-custom text-sm font-semibold">One-Time Fee</div>
            <div class="text-[10px] text-muted-custom font-bold uppercase tracking-wider">No hidden monthly charges</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Branding Footer -->
    <div class="text-xs text-muted-custom relative z-10">
      &copy; <?php echo date("Y"); ?> MLM Platform Inc. Secure SSL Encrypted.
    </div>
  </div>

  <!-- Right Side: Centered Modern Glassmorphism Signup Card -->
  <div class="w-full lg:w-8/12 flex flex-col justify-center px-4 sm:px-10 lg:px-16 py-8 sm:py-12 relative bg-transparent transition-colors duration-400">
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2.5 mb-8 relative z-10">
      <div class="p-1.5 rounded-lg bg-[var(--logo-bg)] border border-[var(--border-color)]">
      </div>
      <span class="text-lg font-bold tracking-wider text-primary-custom">MLM Platform</span>
    </div>

    <div class="max-w-2xl w-full mx-auto relative z-10">
      
      <!-- Signup Card Container -->
      <div class="custom-card p-6 sm:p-10 rounded-2xl">
        <!-- Welcome Header -->
        <div class="mb-6">
          <h2 class="text-2xl font-extrabold text-primary-custom tracking-tight mb-2">Create your account</h2>
          <p class="text-secondary-custom text-sm">Fill in the fields below to start your professional affiliate registration.</p>
        </div>

        <!-- Error message alerts -->
        <?php if($errormsg) echo $errormsg; ?>

        <!-- Form Container -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post" id="signupForm" class="space-y-5">
          
          <!-- Grid layout for input fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Full Name Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="fname">Full Name</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-user text-xs"></i>
                </span>
                <input type="text" id="fname" name="fname" placeholder="Enter your full name" minlength="2" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'utf-8'); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- User ID Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="username">User ID (Username)</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-circle-user text-xs"></i>
                </span>
                <input type="text" id="username" name="username" placeholder="Choose username" minlength="6" pattern="^[a-zA-Z0-9]+$" title="User ID must be alphanumeric and contain at least 6 characters" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- Email Address Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="email">Email Address</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-envelope text-xs"></i>
                </span>
                <input type="email" id="email" name="email" placeholder="example@domain.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'utf-8'); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- Mobile Number Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="mobile">Mobile Number</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-phone text-xs"></i>
                </span>
                <input type="text" id="mobile" name="mobile" placeholder="10-digit number" pattern="[0-9]{10}" maxlength="10" title="Mobile number must be exactly 10 digits" value="<?php echo htmlspecialchars($mobile, ENT_QUOTES, 'utf-8'); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- Password Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="password">Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-lock text-xs"></i>
                </span>
                <input type="password" id="password" name="password" placeholder="Min 8 characters" minlength="8" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-10 text-sm outline-none">
                <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-muted-custom hover:text-indigo-500 transition-colors duration-200 focus:outline-none cursor-pointer">
                  <i class="fa-regular fa-eye text-sm"></i>
                </button>
              </div>
            </div>

            <!-- Confirm Password Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="password2">Confirm Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-lock text-xs"></i>
                </span>
                <input type="password" id="password2" name="password2" placeholder="Repeat password" minlength="8" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-10 text-sm outline-none">
                <button type="button" onclick="togglePasswordVisibility('password2', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-muted-custom hover:text-indigo-500 transition-colors duration-200 focus:outline-none cursor-pointer">
                  <i class="fa-regular fa-eye text-sm"></i>
                </button>
              </div>
            </div>

            <!-- Referral ID Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="referral">Referral ID (Sponsor)</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-user-group text-xs"></i>
                </span>
                <input type="text" id="referral" name="referral" placeholder="Enter sponsor username" value="<?php echo htmlspecialchars($ref, ENT_QUOTES, "utf-8"); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- Address Input -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="address">Full Address</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-location-dot text-xs"></i>
                </span>
                <input type="text" id="address" name="address" placeholder="State/City/Street" minlength="5" value="<?php echo htmlspecialchars($address, ENT_QUOTES, 'utf-8'); ?>" required
                       class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
              </div>
            </div>

            <!-- Country Select -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-custom" for="country">Country</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-muted-custom pointer-events-none transition-colors duration-200">
                  <i class="fa-solid fa-globe text-xs"></i>
                </span>
                <select id="country" name="country" required
                        class="custom-input w-full rounded-xl py-3 pl-10 pr-4 text-sm outline-none">
                  <option value="">Select Country</option>
                  <?php 
                  $countries = ["India","United States of America","United Kingdom","Afghanistan","Albania","Algeria","Andorra","Angola","Argentina","Australia","Austria","Bangladesh","Belgium","Brazil","Canada","China","Denmark","Egypt","France","Germany","Greece","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Japan","Kenya","Malaysia","Mexico","Nepal","Netherlands","New Zealand","Nigeria","Norway","Pakistan","Philippines","Poland","Portugal","Russia","Saudi Arabia","Singapore","South Africa","Spain","Sri Lanka","Sweden","Switzerland","Thailand","Turkey","United Arab Emirates","Vietnam"];
                  foreach($countries as $c): ?>
                    <option value="<?php echo $c; ?>" <?php if($country === $c) echo "selected"; ?>><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>



          </div>

          <input type="hidden" name="todo" value="post">
          
          <!-- Submit Button -->
          <button type="submit" 
                  class="w-full btn-primary-gradient text-white rounded-xl py-3 text-sm font-bold tracking-wide transition-all duration-300 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/25 transform active:scale-[0.98]">
            Register Now
          </button>

          <!-- Dynamic password matching logic -->
          <script>
          document.getElementById('signupForm').addEventListener('submit', function(e) {
              var p1 = document.getElementById('password').value;
              var p2 = document.getElementById('password2').value;
              if (p1 !== p2) {
                  e.preventDefault();
                  alert('Both passwords do not match. Please enter matching passwords.');
              }
          });
          </script>

          <!-- Redirect back to login -->
          <div class="text-center pt-2">
            <a href="index.php" class="text-xs font-bold text-muted-custom hover:text-indigo-500 dark:hover:text-indigo-600 transition duration-150">
              Already have an account? <span class="text-indigo-500 dark:text-indigo-600">Sign In</span>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<script>
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>