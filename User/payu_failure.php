<?php
if(!isset($_SESSION)){
    session_start();
}
include_once("z_db.php");

// Safety check (recovers username from PayU POST, GET if simulation, or session)
$username = $_POST['udf1'] ?? $_GET['username'] ?? $_SESSION['reg_username'] ?? '';

// We do NOT delete from affiliateuser here because the pending user is in pending_registrations.
// But we can clean up any legacy failed records just in case.
if (!empty($username)) {
    $stmt_cleanup = $pdo->prepare("DELETE FROM affiliateuser WHERE username = ? AND active = 0");
    $stmt_cleanup->execute([$username]);
}

if (empty($username)) {
    header("Location: signup.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Payment Failed</title>
<meta name="description" content="Your payment transaction could not be completed." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style type="text/css">
html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background-color: #090d16;
    overflow-y: auto;
}
.pop-error {
    animation: popError 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes popError {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
</head>
<body class="text-slate-700 flex flex-col justify-between min-h-screen">

<main class="flex-grow flex flex-col lg:flex-row">
  
  <!-- Left Side: Premium Branding -->
  <div class="hidden lg:flex lg:w-7/12 relative overflow-hidden bg-gradient-to-tr from-[#0a0f1d] via-[#11162d] to-[#141a35] flex-col justify-between p-16 border-r border-slate-200">
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-rose-600/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 right-20 w-96 h-96 bg-rose-500/5 rounded-full blur-3xl"></div>
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <span class="text-lg font-extrabold tracking-wider bg-gradient-to-r from-rose-400 to-indigo-400 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Hero Content -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-full inline-block mb-6 tracking-wide">TRANSACTION NOT COMPLETED</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-6 tracking-tight">
        Payment could not be processed.
      </h1>
      <p class="text-slate-600 text-base lg:text-lg leading-relaxed mb-8">
        Your registration details are securely stored. You can complete the onboarding checkout now or try another payment method.
      </p>

      <!-- Metrics -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-rose-400 text-lg font-extrabold">Failed</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Transaction Status</div>
        </div>
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-white text-lg font-extrabold">INR 1,000</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Plan Price</div>
        </div>
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-white text-lg font-extrabold">24/7</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Support Available</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-xs text-slate-700 relative z-10">
      &copy; 2026 MLM Platform Inc. All rights reserved. Onboarding System.
    </div>
  </div>

  <!-- Right Side: Failure Confirmation Card -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 py-12 bg-[#090d16] relative">
    <!-- Glowing background light for mobile -->
    <div class="lg:hidden absolute top-0 right-0 w-80 h-80 bg-rose-600/5 rounded-full blur-3xl pointer-events-none"></div>
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2 mb-10">
      <span class="text-md font-bold tracking-wider text-white">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10 text-center">
      
      <!-- Error Icon Panel -->
      <div class="w-20 h-20 bg-rose-500/10 border border-rose-500/20 text-rose-400 text-3xl rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-rose-500/5 pop-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>

      <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3">Payment Failed</h2>
      <p class="text-slate-600 text-sm leading-relaxed mb-8 max-w-sm mx-auto">
        Hello <strong class="text-rose-400">@<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?></strong>. Your PayU transaction could not be processed. Don't worry, your registration details are safe, and you can retry to activate your account.
      </p>

      <div class="pt-6 flex flex-col gap-4">
        <a href="payu_payment.php?retry_username=<?php echo urlencode($username); ?>" 
           class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transform active:scale-[0.98] inline-flex items-center justify-center gap-2">
          Retry Payment <i class="fa-solid fa-arrow-rotate-right text-xs"></i>
        </a>
        <a href="signup.php" 
           class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer transform active:scale-[0.98] inline-flex items-center justify-center gap-2">
          Start Over
        </a>
      </div>

    </div>
  </div>
</main>

<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
