<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}
.animate-float-slow { animation: float 12s ease-in-out infinite; }
.animate-float-fast { animation: float 8s ease-in-out infinite; }
body { font-family: 'Inter', sans-serif; }
</style>
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden bg-gradient-to-b from-[#8fc8ea] via-[#c9e7f9] to-[#eef7fd] text-[#0d1b2a]">

  <!-- Global Glowing Background Elements (Floating freely across the whole screen!) -->
  <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] animate-float-fast bg-[#7baed2]/30 pointer-events-none z-0"></div>
  <div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] animate-float-slow bg-[#add4ee]/30 pointer-events-none z-0"></div>
  <div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] animate-float-fast bg-[#7baed2]/30 pointer-events-none z-0 opacity-40"></div>

  <div class="fixed top-6 left-6 z-10 flex items-center gap-2.5 font-extrabold text-2xl text-[#0d1b2a]">
    <div class="w-8 h-8 rounded-xl bg-white/75 border border-[#79a3c1]/30 flex items-center justify-center text-sm">
        <i class="fa-solid fa-gear"></i>
    </div>
    MLM Platform
  </div>

  <main class="min-h-screen flex items-center justify-center p-5 pt-24 relative z-10 w-full">
    <section class="w-full max-w-md rounded-3xl bg-white/70 border border-[#79a3c1]/30 shadow-[0_24px_52px_rgba(72,114,142,0.20)] backdrop-blur-md p-8 pb-6">
      <div class="w-14 h-14 rounded-2xl bg-white/75 border border-[#79a3c1]/30 mx-auto mb-4 flex items-center justify-center text-[22px] text-[#0d1b2a] shadow-[0_10px_20px_rgba(102,120,138,0.15)]">
        <i class="fa-solid fa-right-to-bracket"></i>
      </div>
      <h1 class="text-center m-0 text-[26px] font-extrabold text-[#0d1b2a] leading-tight tracking-tight">Sign in with email</h1>
      <p class="text-center text-[#425f75] mt-2 mb-5 mx-auto max-w-[500px] text-[13.5px] leading-relaxed">
        Manage members, payouts and package controls from your MLM admin workspace.
      </p>

      <form action="loginproc.php" method="post" class="flex flex-col gap-3.5">
        <div class="relative">
            <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-[#748da1]"></i>
            <input type="text" name="username" placeholder="Email / Username" minlength="6" required 
                class="w-full h-11 border border-[#79a3c1]/40 bg-[#eef5fb] rounded-xl px-11 text-sm outline-none text-[#0d1b2a] transition-all focus:bg-[#e6f1fa] focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
        </div>

        <div class="relative">
            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#748da1]"></i>
            <i class="fa-regular fa-eye-slash absolute right-3.5 top-1/2 -translate-y-1/2 text-[#748da1] cursor-pointer" id="togglePassword"></i>
            <input type="password" name="password" id="password" placeholder="Password" minlength="8" required
                class="w-full h-11 border border-[#79a3c1]/40 bg-[#eef5fb] rounded-xl px-11 text-sm outline-none text-[#0d1b2a] transition-all focus:bg-[#e6f1fa] focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15">
        </div>

        <div class="text-right -mt-1 mb-2 px-1">
            <a href="forgotpassword.php" class="text-indigo-500 text-[12.5px] font-semibold hover:text-indigo-600 transition-colors">Forgot password?</a>
        </div>
        <button class="w-full h-11 rounded-xl border-none bg-gradient-to-b from-[#1a1f2d] to-[#0c1019] text-white text-[15px] font-bold cursor-pointer shadow-[0_8px_24px_rgba(12,17,28,0.15)] transition-all hover:from-[#2a3142] hover:to-[#111827]" type="submit">Get Started</button>
      </form>

    </section>
  </main>

  <script>
  document.addEventListener("DOMContentLoaded", function() {
      var togglePassword = document.getElementById("togglePassword");
      var passwordInput = document.getElementById("password");

      if (togglePassword && passwordInput) {
          togglePassword.addEventListener("click", function() {
              var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
              passwordInput.setAttribute("type", type);
              
              if (type === "password") {
                  togglePassword.classList.remove("fa-eye");
                  togglePassword.classList.add("fa-eye-slash");
              } else {
                  togglePassword.classList.remove("fa-eye-slash");
                  togglePassword.classList.add("fa-eye");
              }
          });
      }
  });
  </script>
</body>
</html>

