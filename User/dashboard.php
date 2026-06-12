<?php
include_once ("z_db.php");
// Initialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['username'])) {
        print "
                        <script language='javascript'>
                                window.location = 'index.php';
                        </script>
                ";
        exit;
}
$payto = $_SESSION['username'];

// The withdrawal processing logic has been moved securely to withdraw.php

// Fetch chart data
$ref_chart_data = [];
try {
    $ref_chart_data = mlmp_pdo_fetch_all($pdo, "
        SELECT doj, COUNT(*) as signup_count
        FROM affiliateuser
        WHERE referedby = ? AND doj IS NOT NULL AND doj != '0000-00-00'
        GROUP BY doj ORDER BY doj ASC LIMIT 15
    ", [$payto]);
} catch (\PDOException $e) {}

$chart_labels   = [];
$chart_signups  = [];
$chart_earnings = [];
$cumulative_earnings = 0.0;

$pkg_comm_row = mlmp_pdo_fetch($pdo, "SELECT level1 FROM packages ORDER BY id ASC LIMIT 1");
$level1_comm = (float)($pkg_comm_row['level1'] ?? 200.0);

if (!empty($ref_chart_data)) {
    foreach ($ref_chart_data as $data) {
        $chart_labels[]  = date('d M', strtotime($data['doj']));
        $count           = (int)$data['signup_count'];
        $chart_signups[] = $count;
        $cumulative_earnings += $count * $level1_comm;
        $chart_earnings[] = $cumulative_earnings;
    }
} else {
    $chart_labels   = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
    $chart_signups  = [0, 0, 0, 0, 0];
    $chart_earnings = [0, 0, 0, 0, 0];
}

// Fetch user info
$profile_row = mlmp_pdo_fetch($pdo, "SELECT id, fname, email, doj, active, username, address, pcktaken, tamount, country, launch_time, is_binary_qualified FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$aid     = $profile_row['id']       ?? 0;
$name    = $profile_row['fname']    ?? '';
$email   = $profile_row['email']    ?? '';
$regdate = $profile_row['doj']      ?? '';
$acti    = $profile_row['active']   ?? '';
$pck     = $profile_row['pcktaken'] ?? 0;
$ear     = $profile_row['tamount']  ?? 0;
$coun    = $profile_row['country']  ?? '';
$launch_time = $profile_row['launch_time'] ?? null;
$is_binary_qualified = (int)($profile_row['is_binary_qualified'] ?? 0);

$pname   = ''; $pdetails = ''; $pprice = 0; $pcur = ''; $ptax = 0; $mpay = 0;
if ($pck) {
    $pkg_row = mlmp_pdo_fetch($pdo, "SELECT name, details, price, currency, tax, mpay FROM packages WHERE id = ?", [$pck]);
    if ($pkg_row) {
        $pname   = $pkg_row['name'];
        $pdetails= $pkg_row['details'];
        $pprice  = $pkg_row['price'];
        $pcur    = $pkg_row['currency'];
        $ptax    = $pkg_row['tax'];
        $mpay    = $pkg_row['mpay'];
    }
}

$ear_val  = (float)($ear ?? 0.0);
$mpay_val = (float)($mpay ?? 0.0);
$left     = $mpay_val - $ear_val;
$pro      = ($mpay_val > 0) ? min(100, ($ear_val / $mpay_val) * 100) : (($ear_val > 0) ? 100 : 0);

// Last referral
$referusername = "-"; $refcountry = "-"; $refdate = "-"; $refpckname = "-";
$last_ref = mlmp_pdo_fetch($pdo, "SELECT username, country, doj, pcktaken FROM affiliateuser WHERE referedby = ? ORDER BY Id DESC LIMIT 1", [$_SESSION['username']]);
if ($last_ref) {
    $referusername = $last_ref['username'] ?? "-";
    $refcountry    = $last_ref['country']  ?? "-";
    $refdate       = $last_ref['doj']      ?? "-";
    $refpckid      = (int)($last_ref['pcktaken'] ?? 0);
    if ($refpckid > 0) {
        $ref_pkg    = mlmp_pdo_fetch($pdo, "SELECT name FROM packages WHERE id = ? LIMIT 1", [$refpckid]);
        $refpckname = $ref_pkg['name'] ?? "-";
    }
}

// Total referrals
$numrows = mlmp_pdo_count($pdo, "SELECT count(*) FROM affiliateuser WHERE referedby = ?", [$_SESSION['username']]);

// Settings
$settings_row  = mlmp_pdo_fetch($pdo, "SELECT header, wlink, fblink, twitterlink FROM settings WHERE sno = 0 LIMIT 1");
$header_title  = $settings_row['header']      ?? 'MLM Platform';
$wlink         = $settings_row['wlink']        ?? '';
$fblink        = $settings_row['fblink']       ?? '#';
$twilink       = $settings_row['twitterlink']  ?? '#';
$invite_url    = mlmp_escape(mlmp_build_referral_url($wlink, $_SESSION['username']));

// Pass variables and libraries to shared layout_header.php
$page_title = "Dashboard";
$active_nav = "dashboard";
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

include_once("layout_header.php");
?>

<?php if (isset($errormsg)) echo $errormsg; ?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 rounded-[20px] p-7 md:p-8 flex flex-col md:flex-row items-start md:items-center justify-between mb-7 relative overflow-hidden border border-indigo-500/30 shadow-2xl shadow-black/40">
  <div class="absolute -top-[50%] -right-[10%] w-[300px] h-[300px] bg-[radial-gradient(circle,_rgba(124,58,237,0.25)_0%,_transparent_70%)] rounded-full pointer-events-none"></div>
  <div class="relative z-10 mb-4 md:mb-0">
    <h2 class="text-[22px] font-bold text-slate-100 mb-1.5">Welcome back, <?php echo mlmp_escape($name); ?>!</h2>
    <p class="text-[13.5px] text-indigo-300 font-medium">
      <?php if ($coun): ?>
        <i class="fa-solid fa-location-dot text-purple-400 mr-1"></i>
        <?php echo mlmp_escape($coun); ?> &nbsp;|&nbsp;
      <?php endif; ?>
      Member since <?php echo $regdate ? date('d M Y', strtotime($regdate)) : '-'; ?>
    </p>
  </div>
  <div class="relative z-10 bg-white/10 border border-white/15 rounded-xl px-5 py-2.5 text-center backdrop-blur-md">
    <div class="text-[11px] text-indigo-300 font-medium">Account Status</div>
    <div class="text-xl font-extrabold mt-0.5 <?php echo ($acti == 1 || strtolower($acti) == 'yes') ? 'text-green-500' : 'text-amber-500'; ?>">
      <?php echo ($acti == 1 || strtolower($acti) == 'yes') ? 'Active' : 'Pending'; ?>
    </div>
  </div>
</div>

<!-- Fast Start Binary Qualification Alert -->
<?php 
if ($acti != 1 || empty($launch_time)) {
    // Not launched yet
    echo '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-hourglass-start"></i></div>
            <div>
                <h4 class="font-bold text-slate-800 text-[15px] mb-1">Fast Start Timer Pending</h4>
                <p class="text-sm text-slate-600 mb-0">Your 24-hour countdown to qualify for Pair Commissions will begin exactly when your account is activated.</p>
            </div>
          </div>';
} else {
    if ($is_binary_qualified === 1) {
        // Qualified
        echo '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-emerald-100 to-transparent pointer-events-none"></div>
                <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-emerald-500/30"><i class="fa-solid fa-medal"></i></div>
                <div class="relative z-10">
                    <h4 class="font-bold text-emerald-900 text-[15px] mb-1">Binary Qualified! <i class="fa-solid fa-circle-check text-emerald-600 ml-1"></i></h4>
                    <p class="text-sm text-emerald-700 mb-0">Congratulations! You completed your first pair within 24 hours. Pair Commissions are unlocked for life.</p>
                </div>
              </div>';
    } else {
        $db_now_row = mlmp_pdo_fetch($pdo, "SELECT NOW() as db_now");
        $now = strtotime($db_now_row['db_now']);
        $lt = strtotime($launch_time);
        $hours_left = 24 - (($now - $lt) / 3600);
        
        if ($hours_left > 0) {
            // Still have time
            $hrs = floor($hours_left);
            $mins = floor(($hours_left - $hrs) * 60);
            echo '<div class="bg-amber-50 border border-amber-300 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm relative overflow-hidden">
                    <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-amber-100 to-transparent pointer-events-none"></div>
                    <div class="w-12 h-12 rounded-full bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-amber-500/30 animate-pulse"><i class="fa-solid fa-stopwatch"></i></div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-amber-900 text-[15px] mb-1">Urgent: Fast Start Qualification!</h4>
                        <p class="text-sm text-amber-800 mb-0">You must complete your first Pair (1 Left, 1 Right) within <b>24 hours</b> to unlock Pair Commissions.</p>
                        <div class="mt-2 inline-flex items-center gap-2 bg-amber-100 text-amber-900 px-3 py-1.5 rounded-lg text-xs font-bold border border-amber-200">
                            <i class="fa-regular fa-clock"></i> Time Remaining: '.$hrs.' hours, '.$mins.' minutes
                        </div>
                    </div>
                  </div>';
        } else {
            // Failed
            echo '<div class="bg-red-50 border border-red-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div>
                        <h4 class="font-bold text-red-900 text-[15px] mb-1">Fast Start Window Closed</h4>
                        <p class="text-sm text-red-700 mb-0">The 24-hour window to unlock Pair Commissions has expired.</p>
                    </div>
                  </div>';
        }
    }
}
?>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 mb-7">
  <!-- Card 1 -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-purple-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-purple-500 to-indigo-400"></div>
    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-indian-rupee-sign"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Total Earnings</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur) . ' ' . number_format((float)$ear, 2); ?></div>
    <div class="text-xs text-slate-600 font-medium"><?php echo $pname ? mlmp_escape($pname) . ' package' : 'No package'; ?></div>
  </div>
  <!-- Card 2 -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-emerald-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-emerald-500 to-emerald-400"></div>
    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-users"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Direct Referrals</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($numrows); ?></div>
    <div class="text-xs text-slate-600 font-medium">Total team members</div>
  </div>
  <!-- Card 3 -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-blue-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-blue-500 to-blue-400"></div>
    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-wallet"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Min. Payout Target</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur) . ' ' . number_format($mpay_val, 0); ?></div>
    <div class="text-xs text-slate-600 font-medium"><?php echo number_format($pro, 0); ?>% achieved</div>
  </div>
  <!-- Card 4 -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-amber-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-amber-500 to-amber-400"></div>
    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-user-plus"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Last Referral</div>
    <div class="text-[18px] font-extrabold text-slate-900 leading-tight truncate"><?php echo $referusername !== '-' ? mlmp_escape($referusername) : '-'; ?></div>
    <div class="text-xs text-slate-600 font-medium"><?php echo $refdate !== '-' ? date('d M Y', strtotime($refdate)) : 'No referrals yet'; ?></div>
  </div>
</div>

<!-- Chart + Progress Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

  <!-- Chart Panel -->
  <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
        <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-chart-line"></i></span>
        Downline Growth
      </div>
      <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">Live</span>
    </div>
    <div class="p-5 flex-1 relative min-h-[300px]">
      <canvas id="userAnalyticsChart"></canvas>
    </div>
  </div>

  <!-- Progress Panel -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100">
      <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
        <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-trophy"></i></span>
        Payout Progress
      </div>
    </div>
    <div class="p-5 flex flex-col flex-1">

      <!-- Earnings + Referrals -->
      <div class="flex gap-2.5 mb-5">
        <div class="flex-1 bg-purple-50 border border-purple-100 rounded-xl p-4 text-center">
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Earnings</div>
          <div class="text-[22px] font-extrabold text-purple-600 mt-1"><?php echo mlmp_escape($pcur) . ' ' . number_format($ear_val, 0); ?></div>
        </div>
        <div class="flex-1 bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-center">
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Referrals</div>
          <div class="text-[22px] font-extrabold text-emerald-600 mt-1"><?php echo mlmp_escape($numrows); ?></div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="text-xs font-semibold text-slate-600 uppercase tracking-[0.8px] mb-2.5">Progress to Payout</div>
      <div class="h-3 bg-slate-100 rounded-full overflow-hidden mb-2">
        <div class="h-full bg-gradient-to-r from-purple-500 to-blue-400 rounded-full relative transition-all duration-1000" style="width:<?php echo number_format($pro, 1); ?>%">
          <div class="absolute right-0 top-0 bottom-0 w-1.5 bg-white/60 rounded-full shadow-[0_0_8px_rgba(255,255,255,0.6)]"></div>
        </div>
      </div>
      <div class="flex justify-between text-[11px] text-slate-600 mt-1">
        <span><?php echo mlmp_escape($pcur) . ' ' . number_format($ear_val, 0); ?> earned</span>
        <span><?php echo number_format($pro, 0); ?>%</span>
        <span><?php echo mlmp_escape($pcur) . ' ' . number_format($mpay_val, 0); ?> target</span>
      </div>

      <?php if ($ear_val > 0 && $left <= 0): ?>
        <div class="text-[13px] font-semibold text-green-600 mt-3">
          Congratulations! You have reached minimum payout.
        </div>
        <div class="mt-2.5">
          <a href="withdraw.php" class="inline-flex items-center gap-2 bg-gradient-to-br from-green-500 to-emerald-400 text-white border-none rounded-lg px-6 py-3 text-sm font-bold cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-md">
            <i class="fa-solid fa-paper-plane"></i>
            Go to Withdrawals
          </a>
        </div>
      <?php elseif ($ear_val == 0 && $mpay_val == 0): ?>
        <div class="text-[13px] font-semibold text-slate-600 mt-3">
          Please purchase a package to unlock payouts, or earn commissions first.
        </div>
      <?php else: ?>
        <div class="text-[13px] font-semibold text-slate-600 mt-3">
          Need <strong class="text-purple-600"><?php echo mlmp_escape($pcur) . ' ' . mlmp_escape(number_format(max(0, $left), 2)); ?></strong> more to unlock payout.
        </div>
      <?php endif; ?>

      <!-- Referral URL -->
      <div class="mt-5">
        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-[0.8px] mb-2">Your Referral Link</label>
        <div class="flex">
          <input type="text" id="refUrl" value="<?php echo $invite_url; ?>" readonly class="flex-1 bg-white border border-slate-200 border-r-0 rounded-l-lg text-slate-800 px-3.5 py-2.5 text-[12.5px] font-['Inter'] outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100 transition-all">
          <button class="bg-gradient-to-br from-purple-500 to-indigo-600 text-white border-none rounded-r-lg px-4.5 py-2.5 text-xs font-semibold cursor-pointer hover:opacity-90 transition-opacity" onclick="copyRefUrl()">
            <i class="fa-solid fa-copy"></i> Copy
          </button>
        </div>
        <div class="text-[11px] text-green-600 mt-1.5 hidden" id="copySuccess">Copied to clipboard!</div>
      </div>

    </div>
  </div>

</div><!-- /row -->

<!-- Last Referral Details + Social -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Referral info grid -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
      <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-user-group"></i></span>
      <div class="text-sm font-bold text-slate-900">Last Referral Details</div>
    </div>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-px bg-slate-100 rounded-b-2xl overflow-hidden border border-slate-100">
      <div class="bg-white p-5 flex items-center gap-3.5 hover:bg-slate-50 transition-colors">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 bg-green-50 text-green-600"><i class="fa-solid fa-user"></i></div>
        <div>
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Username</div>
          <div class="text-[17px] font-bold text-slate-900 mt-0.5"><?php echo mlmp_escape($referusername); ?></div>
        </div>
      </div>
      <div class="bg-white p-5 flex items-center gap-3.5 hover:bg-slate-50 transition-colors">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 bg-red-50 text-red-600"><i class="fa-solid fa-box"></i></div>
        <div>
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Package</div>
          <div class="text-[17px] font-bold text-slate-900 mt-0.5"><?php echo mlmp_escape($refpckname); ?></div>
        </div>
      </div>
      <div class="bg-white p-5 flex items-center gap-3.5 hover:bg-slate-50 transition-colors">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 bg-blue-50 text-blue-600"><i class="fa-solid fa-location-dot"></i></div>
        <div>
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Location</div>
          <div class="text-[17px] font-bold text-slate-900 mt-0.5"><?php echo mlmp_escape($refcountry); ?></div>
        </div>
      </div>
      <div class="bg-white p-5 flex items-center gap-3.5 hover:bg-slate-50 transition-colors">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 bg-amber-50 text-amber-600"><i class="fa-solid fa-calendar-day"></i></div>
        <div>
          <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-[0.7px]">Joined Date</div>
          <div class="text-[17px] font-bold text-slate-900 mt-0.5"><?php echo ($refdate !== '-') ? date('d M Y', strtotime($refdate)) : '-'; ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Social links -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
      <span class="w-7 h-7 rounded-lg bg-pink-100 text-pink-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-share-nodes"></i></span>
      <div class="text-sm font-bold text-slate-900">Follow &amp; Connect</div>
    </div>
    <div class="p-5 flex-1 flex flex-col">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 flex-1">
        <a href="<?php echo mlmp_escape($fblink); ?>" target="_blank" class="rounded-xl p-5 flex items-center gap-3.5 text-white bg-gradient-to-br from-[#1877f2] to-[#3b5998] hover:-translate-y-1 hover:shadow-md transition-all shadow-sm">
          <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-[22px] shrink-0 transition-transform hover:scale-105 hover:bg-white/30"><i class="fab fa-facebook-f"></i></span>
          <div>
            <div class="text-sm font-bold">Like Us</div>
            <div class="text-[11px] text-white/90 mt-0.5">on Facebook</div>
          </div>
        </a>
        <a href="<?php echo mlmp_escape($twilink); ?>" target="_blank" class="rounded-xl p-5 flex items-center gap-3.5 text-white bg-gradient-to-br from-[#1da1f2] to-[#0d95e8] hover:-translate-y-1 hover:shadow-md transition-all shadow-sm">
          <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-[22px] shrink-0 transition-transform hover:scale-105 hover:bg-white/30"><i class="fab fa-twitter"></i></span>
          <div>
            <div class="text-sm font-bold">Follow Us</div>
            <div class="text-[11px] text-white/90 mt-0.5">on Twitter</div>
          </div>
        </a>
      </div>
      <div class="mt-5 p-4 bg-slate-50 rounded-xl border border-slate-100">
        <div class="text-[12px] text-slate-600 font-semibold uppercase tracking-[0.7px] mb-2.5">Quick Links</div>
        <div class="flex flex-wrap gap-2">
          <a href="downline.php" class="text-[12px] font-medium text-purple-600 px-3 py-1.5 bg-purple-100 rounded-md border border-purple-200 hover:bg-purple-200 hover:text-purple-700 transition-colors">Downline</a>
          <a href="paymentshistory.php" class="text-[12px] font-medium text-blue-600 px-3 py-1.5 bg-blue-100 rounded-md border border-blue-200 hover:bg-blue-200 hover:text-blue-700 transition-colors">Payments</a>
          <a href="profile.php" class="text-[12px] font-medium text-emerald-600 px-3 py-1.5 bg-emerald-100 rounded-md border border-emerald-200 hover:bg-emerald-200 hover:text-emerald-700 transition-colors">Profile</a>
          <a href="contact.php" class="text-[12px] font-medium text-amber-600 px-3 py-1.5 bg-amber-100 rounded-md border border-amber-200 hover:bg-amber-200 hover:text-amber-700 transition-colors">Help</a>
        </div>
      </div>
    </div>
  </div>

</div><!-- /row -->

<!-- Chart.js and helper Scripts -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    var ctx = document.getElementById('userAnalyticsChart').getContext('2d');
    var labels       = <?php echo json_encode($chart_labels); ?>;
    var signupData   = <?php echo json_encode($chart_signups); ?>;
    var earningsData = <?php echo json_encode($chart_earnings); ?>;
    var currency     = <?php echo json_encode($pcur ?: 'INR'); ?>;

    var signupGradient = ctx.createLinearGradient(0, 0, 0, 280);
    signupGradient.addColorStop(0, 'rgba(96,165,250,0.5)');
    signupGradient.addColorStop(1, 'rgba(96,165,250,0.02)');

    var earningsGradient = ctx.createLinearGradient(0, 0, 0, 280);
    earningsGradient.addColorStop(0, 'rgba(167,139,250,0.5)');
    earningsGradient.addColorStop(1, 'rgba(167,139,250,0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Direct Referrals',
                    data: signupData,
                    borderColor: '#60a5fa',
                    backgroundColor: signupGradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.45,
                    pointBackgroundColor: '#60a5fa',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 12, weight: '600' }, padding: 18, usePointStyle: true }
                },
                tooltip: {
                    backgroundColor: 'rgba(22,24,31,0.95)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function (c) {
                            var l = c.dataset.label + ': ';
                            l += c.datasetIndex === 1 ? currency + ' ' + c.parsed.y.toLocaleString() : c.parsed.y;
                            return l;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: '#475569', font: { family: 'Inter', size: 11 } }
                },
                y: {
                    type: 'linear', position: 'left',
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: '#475569', font: { family: 'Inter', size: 11 }, stepSize: 1 },
                    title: { display: true, text: 'New Referrals', color: '#60a5fa', font: { family: 'Inter', weight: 'bold', size: 11 } }
                }
            }
        }
    });
});

// Copy referral URL
function copyRefUrl() {
    var inp = document.getElementById('refUrl');
    inp.select(); inp.setSelectionRange(0, 99999);
    navigator.clipboard ? navigator.clipboard.writeText(inp.value) : document.execCommand('copy');
    var suc = document.getElementById('copySuccess');
    suc.style.display = 'block';
    setTimeout(function(){ suc.style.display = 'none'; }, 2500);
}
</script>

<?php
include_once("layout_footer.php");
?>

