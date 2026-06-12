<?php
if(!isset($_SESSION)){
    session_start();
}

$username = $_GET['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Registration Rejected</title>
<meta name="description" content="Your registration request was rejected by the admin." />
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
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 right-20 w-96 h-96 bg-red-500/5 rounded-full blur-3xl"></div>
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <span class="text-lg font-extrabold tracking-wider bg-gradient-to-r from-red-400 to-orange-400 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Hero Content -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-red-400 bg-red-500/10 border border-red-500/20 rounded-full inline-block mb-6 tracking-wide">REGISTRATION REJECTED</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-6 tracking-tight">
        Application Declined.
      </h1>
      <p class="text-slate-600 text-base lg:text-lg leading-relaxed mb-8">
        Your request to join the network has been reviewed and rejected by the administrator. 
      </p>

    </div>

    <!-- Footer -->
    <div class="text-xs text-slate-700 relative z-10">
      &copy; <?php echo date('Y'); ?> MLM Platform Inc. All rights reserved. Onboarding System.
    </div>
  </div>

  <!-- Right Side: Failure Confirmation Card -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 py-12 bg-[#090d16] relative">
    <!-- Glowing background light for mobile -->
    <div class="lg:hidden absolute top-0 right-0 w-80 h-80 bg-red-600/5 rounded-full blur-3xl pointer-events-none"></div>
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2 mb-10">
      <span class="text-md font-bold tracking-wider text-white">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10 text-center">
      
      <!-- Error Icon Panel -->
      <div class="w-20 h-20 bg-red-500/10 border border-red-500/20 text-red-400 text-3xl rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-500/5 pop-error">
        <i class="fa-solid fa-user-xmark"></i>
      </div>

      <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3">Request Rejected</h2>
      <p class="text-slate-600 text-sm leading-relaxed mb-8 max-w-sm mx-auto">
        <?php if($username): ?>Hello <strong class="text-red-400">@<?php echo htmlspecialchars($username, ENT_QUOTES, 'utf-8'); ?></strong>. <?php endif; ?>
        Your request has been rejected by the admin. Please join with another sponsor's ID or retry later.
      </p>

      <div class="pt-6 flex flex-col gap-4">
        <a href="signup.php" 
           class="w-full bg-gradient-to-r from-red-500 to-orange-600 hover:from-orange-600 hover:to-red-700 text-white rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-red-500/10 hover:shadow-red-500/20 transform active:scale-[0.98] inline-flex items-center justify-center gap-2">
          Start Over with New Sponsor <i class="fa-solid fa-user-plus text-xs"></i>
        </a>
        <a href="index.php" 
           class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer transform active:scale-[0.98] inline-flex items-center justify-center gap-2">
          Return to Sign In
        </a>
      </div>

    </div>
  </div>
</main>

<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
