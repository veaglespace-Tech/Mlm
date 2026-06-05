<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Admin Reset Password</title>
<meta name="description" content="Recover your administrative account credentials securely." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}
.animate-float-slow { animation: float 12s ease-in-out infinite; }
.animate-float-fast { animation: float 8s ease-in-out infinite; }
body { font-family: 'Inter', sans-serif; }
</style>
<div id="google_translate_element" class="absolute top-4 right-4 z-50"></div>
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, multilanguagePage: true}, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden bg-gradient-to-b from-[#8fc8ea] via-[#c9e7f9] to-[#eef7fd] text-[#425f75] transition-colors duration-400">

<!-- Global Glowing Background Elements (Floating freely across the whole screen!) -->
<div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] animate-float-fast bg-[#7baed2]/30 pointer-events-none z-0"></div>
<div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] animate-float-slow bg-[#add4ee]/30 pointer-events-none z-0"></div>
<div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] animate-float-fast bg-[#7baed2]/30 pointer-events-none z-0 opacity-40"></div>

<main class="flex-grow flex flex-col lg:flex-row relative z-10">
  
  <!-- Left Side: Dynamic Premium Branding Visuals (Desktop Only) -->
  <div class="hidden lg:flex lg:w-7/12 relative bg-transparent flex-col justify-between p-16 transition-all duration-500">
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <div class="p-1.5 rounded-lg bg-white/75 border border-[#79a3c1]/30 transition-colors">
      </div>
      <span class="text-xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Main Hero Text -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-500/10 border border-indigo-500/20 rounded-full inline-block mb-6 tracking-wide">SECURE RECOVERY</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold text-[#0d1b2a] leading-tight mb-6 tracking-tight transition-colors duration-400">
        Recover administrative credentials.
      </h1>
      <p class="text-[#425f75] text-lg leading-relaxed mb-8 transition-colors duration-400">
        Enter your registered administrative e-mail address. We will verify your identity and securely transmit your access credentials.
      </p>

      <!-- Metric Badges -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="bg-white/70 border border-[#79a3c1]/30 shadow-[0_24px_52px_rgba(72,114,142,0.20)] backdrop-blur-md p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-[#0d1b2a] text-xl font-extrabold transition-colors duration-400">Verified</div>
          <div class="text-[10px] text-[#748da1] font-bold tracking-wider uppercase mt-1 transition-colors duration-400">E-Mail Identity</div>
        </div>
        <div class="bg-white/70 border border-[#79a3c1]/30 shadow-[0_24px_52px_rgba(72,114,142,0.20)] backdrop-blur-md p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-emerald-500 text-xl font-extrabold">Secure</div>
          <div class="text-[10px] text-[#748da1] font-bold tracking-wider uppercase mt-1 transition-colors duration-400">SSL Encrypted</div>
        </div>
        <div class="bg-white/70 border border-[#79a3c1]/30 shadow-[0_24px_52px_rgba(72,114,142,0.20)] backdrop-blur-md p-4 rounded-xl hover:scale-[1.03] transform transition duration-300">
          <div class="text-[#0d1b2a] text-xl font-extrabold transition-colors duration-400">Instant</div>
          <div class="text-[10px] text-[#748da1] font-bold tracking-wider uppercase mt-1 transition-colors duration-400">Processing</div>
        </div>
      </div>
    </div>

    <!-- Branding Footer -->
    <div class="text-xs text-[#748da1] relative z-10 transition-colors duration-400">
      &copy; 2026 MLM Platform Inc. All rights reserved. Secure Administrative Interface.
    </div>
  </div>

  <!-- Right Side: Centered Modern Glassmorphism Recovery Card -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 pt-24 pb-12 sm:pt-28 lg:py-12 relative bg-transparent transition-colors duration-400">
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2.5 mb-10 relative z-10">
      <div class="p-1.5 rounded-lg bg-white/75 border border-[#79a3c1]/30">
      </div>
      <span class="text-lg font-bold tracking-wider text-[#0d1b2a] transition-colors duration-400">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10">
      
      <!-- Welcome Card Container -->
      <div class="bg-white/70 border border-[#79a3c1]/30 shadow-[0_24px_52px_rgba(72,114,142,0.20)] backdrop-blur-md p-8 sm:p-10 rounded-3xl transition-all duration-400">
        <!-- Recovery Header -->
        <div class="mb-8">
          <h2 class="text-2xl font-extrabold text-[#0d1b2a] tracking-tight mb-2 transition-colors duration-400">Reset Password</h2>
          <p class="text-[#425f75] text-sm transition-colors duration-400">Please enter your registered administrative e-mail address below.</p>
        </div>

        <!-- Form Container -->
        <form action="forgotpasswordck.php" method="post" class="space-y-6">
          
          <!-- E-Mail Input -->
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-wider text-[#748da1] transition-colors duration-400" for="femail">Registered E-Mail</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#748da1] pointer-events-none transition-colors duration-200">
                <i class="fa-solid fa-envelope text-xs"></i>
              </span>
              <input type="email" id="femail" name="femail" placeholder="enter_admin_email@domain.com" required
                     class="w-full bg-[#eef5fb] border border-[#79a3c1]/40 text-[#0d1b2a] rounded-xl py-3 pl-10 pr-4 text-sm outline-none transition-all duration-200 focus:bg-[#e6f1fa] focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 placeholder:text-[#748da1]">
            </div>
          </div>

          <!-- Send Password Button -->
          <button type="submit" 
                  class="w-full bg-gradient-to-b from-[#1a1f2d] to-[#0c1019] hover:from-[#2a3142] hover:to-[#111827] text-white rounded-xl py-3 text-sm font-bold tracking-wide transition-all duration-300 cursor-pointer shadow-[0_8px_24px_rgba(12,17,28,0.15)] transform active:scale-[0.98]">
            Send Me Password
          </button>

          <!-- Go back link -->
          <div class="text-center pt-2">
            <a href="index.php" class="text-xs font-bold text-[#748da1] hover:text-indigo-500 transition-colors duration-150">
              <i class="fa-solid fa-arrow-left mr-1.5"></i> Got the password? Sign In
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

</body>
</html>




