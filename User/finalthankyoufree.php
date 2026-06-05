<?php
include_once ("z_db.php");
if(!isset($_SESSION)){
    session_start();
}
session_destroy();

$msg = "Thank you! Your registration / checkout request has been successfully filed.";
$msg2 = "Your account is pending administrator verification. Once verified, your status will update to active, and your sponsorship matrices and signup bonuses will be fully processed.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Registration Received</title>
<meta name="description" content="Thank you for your registration." />
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
    --border-color: rgba(121, 163, 193, 0.34);
    --text-primary: #0d1b2a;
    --text-secondary: #425f75;
    --text-muted: #748da1;
    --glow-color-1: rgba(123, 174, 210, 0.28);
    --glow-color-2: rgba(173, 212, 238, 0.28);
    --card-shadow: 0 24px 52px rgba(72, 114, 142, 0.20);
    --btn-primary: linear-gradient(180deg, #1a1f2d 0%, #0c1019 100%);
    --btn-primary-hover: linear-gradient(180deg, #2a3142 0%, #111827 100%);
    --glass-blur: blur(12px);
}

html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background: var(--bg-main);
    background-attachment: fixed;
    color: var(--text-secondary);
    overflow-y: auto;
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

.pop-check {
    animation: popCheck 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes popCheck {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.custom-card {
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
}

.btn-primary-gradient {
    background: var(--btn-primary);
    transition: background 0.4s ease;
}
.btn-primary-gradient:hover {
    background: var(--btn-primary-hover);
}
</style>
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden">

  <!-- Global Glowing Background Elements (Floating freely across the whole screen!) -->
  <div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] floating-glow-1 pointer-events-none z-0"></div>
  <div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] floating-glow-2 pointer-events-none z-0"></div>
  <div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] floating-glow-1 pointer-events-none z-0 opacity-40"></div>

  <main class="flex-grow flex flex-col lg:flex-row relative z-10">
    
    <!-- Left Side: Premium Branding -->
    <div class="hidden lg:flex lg:w-7/12 relative bg-transparent flex-col justify-between p-16 border-r border-[var(--border-color)]">
      
      <!-- Branding Header -->
      <div class="flex items-center gap-3 relative z-10">
        <span class="text-lg font-extrabold tracking-wider bg-gradient-to-r from-indigo-500 to-indigo-600 bg-clip-text text-transparent">MLM Platform</span>
      </div>

      <!-- Hero Content -->
      <div class="my-auto relative z-10 max-w-lg">
        <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-500/10 border border-indigo-500/20 rounded-full inline-block mb-6 tracking-wide">REQUEST FILED</span>
        <h1 class="text-4xl lg:text-5xl font-extrabold text-[var(--text-primary)] leading-tight mb-6 tracking-tight">
          Registration is successfully filed.
        </h1>
        <p class="text-[var(--text-secondary)] text-base lg:text-lg leading-relaxed mb-8">
          Your registration details have been received and stored. Once the administrator approves the billing verification, your network benefits will begin.
        </p>

        <!-- Metrics -->
        <div class="grid grid-cols-3 gap-4 pt-4">
          <div class="custom-card p-4 rounded-xl border border-[var(--border-color)]">
            <div class="text-[var(--text-primary)] text-lg font-extrabold">Pending</div>
            <div class="text-[10px] text-[var(--text-muted)] font-semibold tracking-wider uppercase mt-1">Approval Status</div>
          </div>
          <div class="custom-card p-4 rounded-xl border border-[var(--border-color)]">
            <div class="text-indigo-600 text-lg font-extrabold">Online</div>
            <div class="text-[10px] text-[var(--text-muted)] font-semibold tracking-wider uppercase mt-1">Payment Method</div>
          </div>
          <div class="custom-card p-4 rounded-xl border border-[var(--border-color)]">
            <div class="text-[var(--text-primary)] text-lg font-extrabold">MLM</div>
            <div class="text-[10px] text-[var(--text-muted)] font-semibold tracking-wider uppercase mt-1">Affiliate Hub</div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-xs text-[var(--text-muted)] relative z-10">
        &copy; 2026 MLM Platform Inc. All rights reserved. Onboarding System.
      </div>
    </div>

    <!-- Right Side: Thank You Confirmation Card -->
    <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 py-12 bg-transparent relative">
      
      <div class="max-w-md w-full mx-auto relative z-10 text-center">
        
        <!-- Welcome Card Container -->
        <div class="custom-card p-8 sm:p-10 rounded-2xl">
          <!-- Checkmark Icon Panel -->
          <div class="w-20 h-20 bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 text-3xl rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-500/5 pop-check">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>

          <h2 class="text-2xl font-extrabold text-[var(--text-primary)] tracking-tight mb-3">Order Received</h2>
          <p class="text-[var(--text-secondary)] text-sm leading-relaxed mb-6">
            <?php echo htmlspecialchars($msg, ENT_QUOTES, 'utf-8'); ?>
          </p>

          <div class="bg-[#eef5fb] border border-[#c9dbea] rounded-2xl p-5 mb-8 text-left text-xs leading-relaxed text-[var(--text-secondary)]">
            <span class="text-[var(--text-primary)] font-bold block mb-1">Verification Processing</span>
            <?php echo htmlspecialchars($msg2, ENT_QUOTES, 'utf-8'); ?>
          </div>

          <div class="pt-4 border-t border-[var(--border-color)]">
            <a href="index.php" 
               class="w-full btn-primary-gradient text-white rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transform active:scale-[0.98] inline-flex items-center justify-center gap-2">
              Back to Login <i class="fa-solid fa-right-to-bracket text-xs"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="js/app.v1.js"></script>
  <script src="js/app.plugin.js"></script>
</body>
</html>
