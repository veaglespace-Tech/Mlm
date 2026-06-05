<?php
include_once ("z_db.php");
		
$msg = "Under Maintenance";
$msg2 = "Our servers are currently undergoing a scheduled system upgrade. Our technical team is working hard to complete the operations as quickly as possible. Please check back shortly.";
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<title>System Under Maintenance - MLM Platform</title>
<meta name="description" content="MLM Platform is currently undergoing scheduled maintenance." />
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
.spin-slow {
    animation: spinSlow 8s linear infinite;
}
@keyframes spinSlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
</head>
<body class="text-slate-700 flex flex-col justify-between min-h-screen">

<main class="flex-grow flex flex-col lg:flex-row">
  
  <!-- Left Side: Premium Branding -->
  <div class="hidden lg:flex lg:w-7/12 relative overflow-hidden bg-gradient-to-tr from-[#0a0f1d] via-[#11162d] to-[#141a35] flex-col justify-between p-16 border-r border-slate-200">
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 right-20 w-96 h-96 bg-amber-500/5 rounded-full blur-3xl"></div>
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <span class="text-lg font-extrabold tracking-wider bg-gradient-to-r from-amber-400 to-amber-400 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Hero Content -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-amber-600 bg-amber-500/10 border border-amber-500/20 rounded-full inline-block mb-6 tracking-wide">SYSTEM UPGRADE</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-6 tracking-tight">
        Improving your affiliate experience.
      </h1>
      <p class="text-slate-600 text-base lg:text-lg leading-relaxed mb-8">
        We are executing infrastructure updates to boost database responsiveness, transaction processing speeds, and direct downline matrix updates.
      </p>

      <!-- Metrics -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-white text-lg font-extrabold">Active</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Upgrade Phase</div>
        </div>
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-amber-600 text-lg font-extrabold">99.9%</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Target Uptime</div>
        </div>
        <div class="bg-white/2 p-4 rounded-xl border border-slate-200 backdrop-blur-sm">
          <div class="text-white text-lg font-extrabold">Secured</div>
          <div class="text-[10px] text-slate-600 font-semibold tracking-wider uppercase mt-1">Data Storage</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-xs text-slate-700 relative z-10">
      &copy; 2026 MLM Platform Inc. All rights reserved. Maintenance Protocol.
    </div>
  </div>

  <!-- Right Side: Thank You Confirmation Card -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 py-12 bg-[#090d16] relative">
    <!-- Glowing background light for mobile -->
    <div class="lg:hidden absolute top-0 right-0 w-80 h-80 bg-amber-600/5 rounded-full blur-3xl pointer-events-none"></div>
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2 mb-10">
      <span class="text-md font-bold tracking-wider text-white">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10 text-center">
      
      <!-- Maintenance Cog Icon Panel -->
      <div class="w-20 h-20 bg-amber-500/10 border border-amber-500/20 text-amber-600 text-3xl rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-500/5 spin-slow">
        <i class="fa-solid fa-gear"></i>
      </div>

      <h2 class="text-2xl font-extrabold text-white tracking-tight mb-3"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'utf-8'); ?></h2>
      
      <div class="bg-white/2 border border-slate-200 rounded-2xl p-5 mb-8 text-left text-xs leading-relaxed text-slate-600">
        <span class="text-white font-bold block mb-1">Status Message</span>
        <?php echo htmlspecialchars($msg2, ENT_QUOTES, 'utf-8'); ?>
      </div>

      <p class="text-xs text-slate-600">
        Thank you for your patience and understanding.
      </p>

    </div>
  </div>
</main>

<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
