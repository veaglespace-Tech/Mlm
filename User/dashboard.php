<?php
include_once ("z_db.php");
// Initialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
$payto = $_SESSION['username'];

// Handle Leg Placement Preference Update
$leg_update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_leg_pref') {
    $new_leg = strtoupper(trim($_POST['default_leg'] ?? 'AUTO'));
    if (in_array($new_leg, ['AUTO', 'L', 'R'])) {
        mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET default_leg = ? WHERE username = ?", [$new_leg, $payto]);
        $leg_update_msg = "Placement preference successfully saved!";
    }
}

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
$profile_row = mlmp_pdo_fetch($pdo, "SELECT id, fname, email, doj, active, username, address, pcktaken, tamount, country, launch_time, is_binary_qualified, default_leg FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
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
$user_leg = $profile_row['default_leg'] ?? 'AUTO';

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

// Pair commission breakdown
$pair_comm_row = mlmp_pdo_fetch($pdo, "SELECT SUM(net_amount) as pair_total FROM pairing_transactions WHERE user_id = ?", [$aid]);
$pair_commission = (float)($pair_comm_row['pair_total'] ?? 0);
// Direct bonus = Total earnings - Pair commissions
$direct_bonus = max(0.0, $ear_val - $pair_commission);

// Daily / Weekly / Monthly Earnings from pairing_transactions
$daily_pair  = (float)(mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(net_amount),0) as v FROM pairing_transactions WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$aid])['v'] ?? 0);
$weekly_pair = (float)(mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(net_amount),0) as v FROM pairing_transactions WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [$aid])['v'] ?? 0);
$monthly_pair= (float)(mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(net_amount),0) as v FROM pairing_transactions WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$aid])['v'] ?? 0);

// Daily / Weekly / Monthly total (pair + referral bonuses)
// For referral bonuses we track them as the difference between tamount growth (approximate via pairing_transactions)
// We show pair commission breakdown per period, and total is from tamount
$daily_total  = $daily_pair;   // pair is the trackable part; referral bonuses are credited immediately
$weekly_total = $weekly_pair;
$monthly_total= $monthly_pair;

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

<!-- Pair Commission Qualification & Countdown Alert -->
<?php 
if ($acti != 1) {
    // Not active yet
    echo '<div class="bg-slate-50 border border-slate-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-hourglass-start"></i></div>
            <div>
                <h4 class="font-bold text-slate-800 text-[15px] mb-1">Pair Timer Pending</h4>
                <p class="text-sm text-slate-600 mb-0">Your per-pair 24-hour countdowns to qualify for commissions will begin when your account is activated and referrals start joining.</p>
            </div>
          </div>';
} else {
    // Check if there is an active countdown for a pending pair
    $pending_pair = mlmp_pdo_fetch($pdo, "SELECT pair_no, first_member_joined_at FROM pair_countdowns WHERE user_id = ? AND completed_at IS NULL ORDER BY pair_no ASC LIMIT 1", [$aid]);
    if ($pending_pair) {
        $db_now_row = mlmp_pdo_fetch($pdo, "SELECT NOW() as db_now");
        $now = strtotime($db_now_row['db_now']);
        $first_joined = strtotime($pending_pair['first_member_joined_at']);
        $seconds_left = max(0, 86400 - ($now - $first_joined));
        $percent_left = round(($seconds_left / 86400) * 100);
        
        if ($seconds_left > 0) {
            // Countdown is active
            $hrs = floor($seconds_left / 3600);
            $mins = floor(($seconds_left % 3600) / 60);
            $secs = $seconds_left % 60;
            echo '<div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-2xl p-5 md:p-6 mb-7 shadow-sm relative overflow-hidden">
                    <div class="absolute right-0 top-0 bottom-0 w-48 bg-gradient-to-l from-amber-100/50 to-transparent pointer-events-none"></div>
                    <div class="flex items-start md:items-center gap-4 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-amber-500/30 animate-pulse"><i class="fa-solid fa-stopwatch"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-extrabold text-amber-950 text-base md:text-lg mb-0">Active 24-Hour Timer: Pair #' . htmlspecialchars($pending_pair['pair_no']) . '</h4>
                                <span class="bg-amber-500/20 text-amber-900 border border-amber-500/30 text-[11px] font-bold px-2.5 py-0.5 rounded-full">Commission Window</span>
                            </div>
                            <p class="text-sm text-amber-900/90 mt-1 mb-3">One member joined your team for this pair. Place a member on your matching leg within <b>24 hours</b> to unlock your <b>₹300.00</b> Pair Matching Bonus!</p>
                            
                            <!-- Live Progress Bar -->
                            <div class="w-full bg-amber-200/70 h-2.5 rounded-full overflow-hidden mb-3 max-w-xl">
                                <div id="livePairProgress" class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full transition-all duration-1000" style="width: ' . $percent_left . '%;"></div>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="inline-flex items-center gap-2 bg-white text-amber-950 px-3.5 py-1.5 rounded-xl text-xs font-black border border-amber-300 shadow-sm">
                                    <i class="fa-regular fa-clock text-amber-600"></i> Time Left: <span id="livePairTimer" data-seconds="' . $seconds_left . '">' . sprintf('%02d:%02d:%02d', $hrs, $mins, $secs) . '</span>
                                </div>
                                <span class="text-xs font-semibold text-amber-800"><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Pair Payout: <strong>₹300.00</strong></span>
                            </div>
                        </div>
                    </div>
                  </div>';
        } else {
            // Expired
            echo '<div class="bg-red-50 border border-red-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div>
                        <h4 class="font-bold text-red-900 text-[15px] mb-1">Countdown Expired: Pair #' . htmlspecialchars($pending_pair['pair_no']) . '</h4>
                        <p class="text-sm text-red-700 mb-0">The 24-hour window to complete this pair has expired. Commission for this pair is forfeited.</p>
                    </div>
                  </div>';
        }
    } else {
        // No pending pair
        echo '<div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 md:p-5 mb-7 flex items-center gap-4 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-emerald-100 to-transparent pointer-events-none"></div>
                <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-emerald-500/30"><i class="fa-solid fa-medal"></i></div>
                <div class="relative z-10">
                    <h4 class="font-bold text-emerald-900 text-[15px] mb-1">Pairs Status: Normal <i class="fa-solid fa-circle-check text-emerald-600 ml-1"></i></h4>
                    <p class="text-sm text-emerald-700 mb-0">All your active pairs are complete. Once a member joins on one leg of a new pair, the 24-hour countdown for that pair will begin.</p>
                </div>
              </div>';
    }
}
?>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 mb-7">
  <!-- Card 1: Total Earnings -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-purple-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-purple-500 to-indigo-400"></div>
    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-indian-rupee-sign"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Total Earnings</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur) . ' ' . number_format($ear_val, 2); ?></div>
    <div class="text-xs text-slate-600 font-medium"><?php echo $pname ? mlmp_escape($pname) . ' package' : 'No package'; ?></div>
  </div>
  <!-- Card 2: Pair Commission -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-cyan-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-cyan-500 to-teal-400"></div>
    <div class="w-11 h-11 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-arrows-left-right"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Pair Commission</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur ?: '₹') . ' ' . number_format($pair_commission, 2); ?></div>
    <div class="text-xs text-cyan-600 font-medium">Binary pair earnings (after TDS)</div>
  </div>
  <!-- Card 3: Direct Referral Bonus -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-emerald-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-emerald-500 to-emerald-400"></div>
    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Direct Referral Bonus</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur ?: '₹') . ' ' . number_format($direct_bonus, 2); ?></div>
    <div class="text-xs text-emerald-600 font-medium"><?php echo mlmp_escape($numrows); ?> direct referral(s)</div>
  </div>
  <!-- Card 4: Payout Progress -->
  <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-[22px] flex flex-col gap-2.5 relative overflow-hidden shadow-sm hover:-translate-y-1 hover:shadow-md hover:border-amber-300 transition-all duration-300">
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-amber-500 to-amber-400"></div>
    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-wallet"></i></div>
    <div class="text-[11.5px] text-slate-600 font-semibold uppercase tracking-[0.8px] mt-1">Min. Payout Target</div>
    <div class="text-[26px] font-extrabold text-slate-900 leading-none"><?php echo mlmp_escape($pcur) . ' ' . number_format($mpay_val, 0); ?></div>
    <div class="text-xs text-amber-600 font-medium"><?php echo number_format($pro, 0); ?>% achieved so far</div>
  </div>
</div>

<!-- Daily / Weekly / Monthly Earnings Section -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-7 overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
    <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-chart-column"></i></span>
    <div class="text-sm font-bold text-slate-900">Pair Commission Breakdown</div>
    <span class="ml-auto text-[11px] font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 border border-blue-200">Live Stats</span>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">

    <!-- Today -->
    <div class="p-5 sm:p-7 flex items-center gap-4 group hover:bg-slate-50 transition-colors">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl shrink-0 shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
        <i class="fa-solid fa-sun"></i>
      </div>
      <div>
        <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-[0.8px] mb-0.5">Today's Earnings</div>
        <div class="text-[22px] font-extrabold text-slate-900 leading-tight">
          <?php echo htmlspecialchars($pcur ?: '₹', ENT_QUOTES, 'UTF-8') . ' ' . number_format($daily_pair, 2); ?>
        </div>
        <div class="text-[11px] text-blue-600 font-semibold mt-0.5">Pair commission (after TDS)</div>
      </div>
    </div>

    <!-- This Week -->
    <div class="p-5 sm:p-7 flex items-center gap-4 group hover:bg-slate-50 transition-colors">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white text-xl shrink-0 shadow-md shadow-violet-500/20 group-hover:scale-105 transition-transform">
        <i class="fa-solid fa-calendar-week"></i>
      </div>
      <div>
        <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-[0.8px] mb-0.5">This Week (7 Days)</div>
        <div class="text-[22px] font-extrabold text-slate-900 leading-tight">
          <?php echo htmlspecialchars($pcur ?: '₹', ENT_QUOTES, 'UTF-8') . ' ' . number_format($weekly_pair, 2); ?>
        </div>
        <div class="text-[11px] text-violet-600 font-semibold mt-0.5">Pair commission (after TDS)</div>
      </div>
    </div>

    <!-- This Month -->
    <div class="p-5 sm:p-7 flex items-center gap-4 group hover:bg-slate-50 transition-colors">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl shrink-0 shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
        <i class="fa-solid fa-calendar-days"></i>
      </div>
      <div>
        <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-[0.8px] mb-0.5">This Month</div>
        <div class="text-[22px] font-extrabold text-slate-900 leading-tight">
          <?php echo htmlspecialchars($pcur ?: '₹', ENT_QUOTES, 'UTF-8') . ' ' . number_format($monthly_pair, 2); ?>
        </div>
        <div class="text-[11px] text-emerald-600 font-semibold mt-0.5">Pair commission (after TDS)</div>
      </div>
    </div>

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

      <!-- Binary Placement Preference Selector -->
      <div class="mt-5 pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between mb-2">
          <label class="block text-xs font-semibold text-slate-700 uppercase tracking-[0.8px]">Binary Placement Preference</label>
          <span class="text-[11px] text-indigo-700 font-bold bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-200">
            Active: <?php echo ($user_leg === 'L' ? 'Left Leg' : ($user_leg === 'R' ? 'Right Leg' : 'Auto Balance')); ?>
          </span>
        </div>
        <form method="POST" class="grid grid-cols-3 gap-2">
          <input type="hidden" name="action" value="update_leg_pref">
          <label class="cursor-pointer border rounded-xl p-2.5 text-center flex flex-col items-center justify-center transition-all <?php echo $user_leg === 'AUTO' ? 'bg-indigo-50 border-indigo-400 text-indigo-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
            <input type="radio" name="default_leg" value="AUTO" class="sr-only" onchange="this.form.submit()" <?php if ($user_leg === 'AUTO') echo 'checked'; ?>>
            <i class="fa-solid fa-scale-balanced mb-1 text-sm text-indigo-500"></i>
            <span class="text-[11px] font-bold">Auto</span>
            <span class="text-[9px] text-slate-500">Weaker Leg</span>
          </label>

          <label class="cursor-pointer border rounded-xl p-2.5 text-center flex flex-col items-center justify-center transition-all <?php echo $user_leg === 'L' ? 'bg-indigo-50 border-indigo-400 text-indigo-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
            <input type="radio" name="default_leg" value="L" class="sr-only" onchange="this.form.submit()" <?php if ($user_leg === 'L') echo 'checked'; ?>>
            <i class="fa-solid fa-arrow-left mb-1 text-sm text-indigo-500"></i>
            <span class="text-[11px] font-bold">Left</span>
            <span class="text-[9px] text-slate-500">Force Left</span>
          </label>

          <label class="cursor-pointer border rounded-xl p-2.5 text-center flex flex-col items-center justify-center transition-all <?php echo $user_leg === 'R' ? 'bg-indigo-50 border-indigo-400 text-indigo-900 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
            <input type="radio" name="default_leg" value="R" class="sr-only" onchange="this.form.submit()" <?php if ($user_leg === 'R') echo 'checked'; ?>>
            <i class="fa-solid fa-arrow-right mb-1 text-sm text-indigo-500"></i>
            <span class="text-[11px] font-bold">Right</span>
            <span class="text-[9px] text-slate-500">Force Right</span>
          </label>
        </form>
        <?php if (!empty($leg_update_msg)): ?>
          <div class="text-[11px] text-emerald-600 font-semibold mt-2 flex items-center gap-1">
            <i class="fa-solid fa-circle-check"></i> <?php echo mlmp_escape($leg_update_msg); ?>
          </div>
        <?php endif; ?>
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
          <a href="my_referrals.php" class="text-[12px] font-medium text-indigo-600 px-3 py-1.5 bg-indigo-100 rounded-md border border-indigo-200 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">My Referrals</a>
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
    var currency     = <?php echo json_encode($pcur ?: '₹'); ?>;

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

// Live 24-hr Countdown Ticker
(function() {
    var timerEl = document.getElementById('livePairTimer');
    var progressEl = document.getElementById('livePairProgress');
    if (!timerEl) return;

    var secLeft = parseInt(timerEl.getAttribute('data-seconds'), 10);
    if (isNaN(secLeft) || secLeft <= 0) return;

    var timerInterval = setInterval(function() {
        secLeft--;
        if (secLeft <= 0) {
            clearInterval(timerInterval);
            timerEl.innerText = "00:00:00 (Expired)";
            if (progressEl) progressEl.style.width = "0%";
            return;
        }

        var h = Math.floor(secLeft / 3600);
        var m = Math.floor((secLeft % 3600) / 60);
        var s = secLeft % 60;
        timerEl.innerText = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

        if (progressEl) {
            var pct = Math.max(0, Math.min(100, (secLeft / 86400) * 100));
            progressEl.style.width = pct + '%';
        }
    }, 1000);
})();
</script>

<?php
include_once("layout_footer.php");
?>

