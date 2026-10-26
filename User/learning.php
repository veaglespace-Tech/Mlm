<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Learning Portal • NP Academy';
$active_nav = 'learning';
include("layout_header.php");

$username = $_SESSION['username'];
$user = mlmp_pdo_fetch($pdo, "SELECT Id, fname, active, pcktaken FROM affiliateuser WHERE username = ?", [$username]);
$is_active = ((int)($user['active'] ?? 0) === 1);
$fname = $user['fname'] ?? 'Member';

// Target credentials
$target_url = "https://learn.navinparmar.in/login.html";
$target_email = "veaglespace@gmail.com";
$target_pass  = "Vihan@301196";

// Bookmarklet javascript code
$bookmarklet_code = "javascript:(function(){try{const e=document.getElementById('email');const p=document.getElementById('password');const c=document.getElementById('tncCheck');const b=document.getElementById('loginBtn');if(e&&p){e.value='" . $target_email . "';p.value='" . $target_pass . "';if(c)c.checked=true;if(typeof toggleLoginBtn==='function')toggleLoginBtn();if(b){b.classList.remove('locked');b.click();}}else{alert('Please open learn.navinparmar.in/login.html first!');}}catch(err){console.error(err);}})();";
?>

<div class="dash-content space-y-6">

  <!-- Hero Header Banner -->
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 p-6 sm:p-8 text-white shadow-xl border border-indigo-500/20">
    <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-purple-500/10 blur-3xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold mb-3">
          <i class="fa-solid fa-graduation-cap text-indigo-400"></i> Official Learning Hub
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">NP Academy • Learning Gateway</h1>
        <p class="text-sm text-slate-300 mt-1 max-w-2xl">
          Access world-class network marketing training, leadership courses, and digital business masterclasses.
        </p>
      </div>

      <!-- Access Status Badge -->
      <div>
        <?php if ($is_active): ?>
          <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 font-bold text-sm shadow-sm backdrop-blur-md">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            VIP All-Access Unlocked
          </div>
        <?php else: ?>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-amber-500/15 border border-amber-400/30 text-amber-300 font-bold text-sm">
            <i class="fa-solid fa-lock text-amber-400"></i> Membership Required
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!$is_active): ?>
    <!-- Locked State for Free / Inactive Members -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-12 text-center shadow-sm max-w-2xl mx-auto">
      <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-sm">
        <i class="fa-solid fa-lock"></i>
      </div>
      <h2 class="text-xl font-bold text-slate-800">Premium Courses Locked</h2>
      <p class="text-sm text-slate-500 mt-2 leading-relaxed">
        Hello <strong><?php echo htmlspecialchars($fname); ?></strong>, NP Academy courses (worth ₹15,000+) are exclusively unlocked for Active Members.
        Please activate your membership package to get lifetime access to all learning materials.
      </p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <a href="dashboard.php" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition-all shadow-md shadow-indigo-600/20">
          Go to Dashboard
        </a>
      </div>
    </div>

  <?php else: ?>
    <!-- Unlocked State: Active Members Gateway -->

    <!-- Main 1-Click Launch Card -->
    <div class="bg-gradient-to-br from-white via-indigo-50/20 to-purple-50/30 rounded-3xl border border-indigo-200/60 p-6 sm:p-8 shadow-sm">
      <div class="max-w-3xl mx-auto text-center space-y-6">
        
        <div>
          <span class="inline-block px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold uppercase tracking-wider mb-2">
            One-Click Seamless Launch
          </span>
          <h2 class="text-2xl font-extrabold text-slate-900">Direct Portal Login</h2>
          <p class="text-sm text-slate-500 mt-1">
            Click the launch button below. Your credentials will be auto-copied, and NP Academy will open in a new tab ready for you to paste and login.
          </p>
        </div>

        <!-- Credentials Quick-View Box -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl mx-auto text-left">
          <!-- Email Box -->
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="overflow-hidden mr-2">
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Login Email</div>
              <div id="displayEmail" class="font-mono text-sm font-bold text-slate-800 truncate"><?php echo $target_email; ?></div>
            </div>
            <button onclick="copyText('<?php echo $target_email; ?>', 'Email copied!')" 
                    class="shrink-0 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs transition-colors flex items-center gap-1">
              <i class="fa-solid fa-copy"></i> Copy
            </button>
          </div>

          <!-- Password Box -->
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="overflow-hidden mr-2">
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Login Password</div>
              <div class="flex items-center gap-2">
                <input type="password" id="displayPass" value="<?php echo $target_pass; ?>" readonly 
                       class="font-mono text-sm font-bold text-slate-800 bg-transparent border-0 p-0 focus:outline-none w-28">
                <button type="button" onclick="togglePassVisibility()" class="text-slate-400 hover:text-slate-600 text-xs">
                  <i id="passEyeIcon" class="fa-solid fa-eye"></i>
                </button>
              </div>
            </div>
            <button onclick="copyText('<?php echo $target_pass; ?>', 'Password copied!')" 
                    class="shrink-0 px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs transition-colors flex items-center gap-1">
              <i class="fa-solid fa-copy"></i> Copy
            </button>
          </div>
        </div>

        <!-- Big Launch CTA -->
        <div class="pt-2">
          <button onclick="launchAcademy()" 
                  class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-base shadow-xl shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
            <i class="fa-solid fa-rocket text-lg"></i>
            <span>Launch & Open NP Academy</span>
            <i class="fa-solid fa-arrow-up-right-from-square text-xs opacity-75"></i>
          </button>
          <div class="text-xs text-slate-400 mt-2">
            <i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Auto-copies password to clipboard & opens login page
          </div>
        </div>

      </div>
    </div>

    <!-- 3 Simple Steps Walkthrough -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-start gap-4">
        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base font-extrabold shrink-0">
          1
        </div>
        <div>
          <h3 class="font-bold text-sm text-slate-800">Click "Launch Academy"</h3>
          <p class="text-xs text-slate-500 mt-1">The button above copies your login credentials to your clipboard and opens the academy website.</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-start gap-4">
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-base font-extrabold shrink-0">
          2
        </div>
        <div>
          <h3 class="font-bold text-sm text-slate-800">Paste Credentials</h3>
          <p class="text-xs text-slate-500 mt-1">Paste the email and password on the login form, check "Accept Terms", and hit Login.</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-start gap-4">
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-extrabold shrink-0">
          3
        </div>
        <div>
          <h3 class="font-bold text-sm text-slate-800">Learn & Grow</h3>
          <p class="text-xs text-slate-500 mt-1">Enjoy unlimited access to all training modules, strategy courses, and leadership sessions.</p>
        </div>
      </div>
    </div>

    <!-- Power User: Magic 1-Click Auto-Fill Bookmarklet (Desktop PC/Laptop) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-800 text-sm sm:text-base">PC/Laptop Power Tool: 1-Click Auto-Fill Bookmarklet</h3>
            <p class="text-xs text-slate-500">Drag this button to your Bookmarks Bar for instant 0.1-second auto-fill & login.</p>
          </div>
        </div>
        
        <!-- Draggable Bookmarklet Button -->
        <div>
          <a href="<?php echo htmlspecialchars($bookmarklet_code); ?>" 
             onclick="alert('Drag this button to your Browser Bookmarks bar (Ctrl + Shift + B). Then click it on the NP Academy login page to auto-login!'); return false;"
             class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/20 cursor-grab active:cursor-grabbing transition-all select-none">
            <i class="fa-solid fa-bookmark"></i>
            <span>⚡ Auto-Login NP Academy</span>
          </a>
        </div>
      </div>

      <div class="mt-4 text-xs text-slate-500 space-y-1">
        <p><strong class="text-slate-700">How to use on Chrome/Edge:</strong> Press <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-700 font-mono text-[11px]">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-700 font-mono text-[11px]">Shift</kbd> + <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-700 font-mono text-[11px]">B</kbd> to show bookmarks bar, drag the orange button up into the bar, then on `login.html`, click it once to auto-login!</p>
      </div>
    </div>

  <?php endif; ?>

</div>

<!-- Toast Container -->
<div id="toastPopup" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
  <div class="bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700 text-sm font-semibold">
    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
    <span id="toastText">Copied!</span>
  </div>
</div>

<script>
function showToastMsg(msg) {
  const toast = document.getElementById('toastPopup');
  const text = document.getElementById('toastText');
  text.textContent = msg;
  toast.classList.remove('translate-y-20', 'opacity-0');
  toast.classList.add('translate-y-0', 'opacity-100');
  setTimeout(() => {
    toast.classList.remove('translate-y-0', 'opacity-100');
    toast.classList.add('translate-y-20', 'opacity-0');
  }, 3200);
}

function copyText(val, successMsg) {
  navigator.clipboard.writeText(val).then(() => {
    showToastMsg(successMsg || 'Copied to clipboard!');
  }).catch(() => {
    showToastMsg('Copied!');
  });
}

function togglePassVisibility() {
  const passInput = document.getElementById('displayPass');
  const icon = document.getElementById('passEyeIcon');
  if (passInput.type === 'password') {
    passInput.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    passInput.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

function launchAcademy() {
  // 1. Copy password to clipboard
  const password = "<?php echo $target_pass; ?>";
  copyText(password, '✅ Password copied! Opening NP Academy in new tab...');

  // 2. Open login page in new tab
  setTimeout(() => {
    window.open("<?php echo $target_url; ?>", "_blank");
  }, 400);
}
</script>

<?php include("layout_footer.php"); ?>
