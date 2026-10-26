<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Leaderboard';
$active_nav = 'leaderboard';
include("layout_header.php");
ensure_leaderboard_schema($pdo);

$username = $_SESSION['username'];

// Get current user info
$current_user = mlmp_pdo_fetch($pdo, "SELECT Id, fname, tamount, rank_title FROM affiliateuser WHERE username = ?", [$username]);
$current_user_id = $current_user['Id'] ?? 0;

// Filter: weekly, monthly, all
$filter = $_GET['filter'] ?? 'all';

// === TOP EARNERS ===
$top_earners = mlmp_pdo_fetch_all($pdo, "
    SELECT Id, fname, username, tamount, rank_title,
           (SELECT COUNT(*) FROM affiliateuser AS sub WHERE sub.referedby = affiliateuser.username AND sub.level = 2) AS referral_count
    FROM affiliateuser 
    WHERE level = 2 AND active = 1
    ORDER BY tamount DESC 
    LIMIT 10
");

// === TOP RECRUITERS ===
$top_recruiters = mlmp_pdo_fetch_all($pdo, "
    SELECT u.Id, u.fname, u.username, u.tamount, u.rank_title,
           COUNT(r.Id) AS referral_count
    FROM affiliateuser u
    LEFT JOIN affiliateuser r ON r.referedby = u.username AND r.level = 2
    WHERE u.level = 2 AND u.active = 1
    GROUP BY u.Id
    ORDER BY referral_count DESC
    LIMIT 10
");

// === RANK SYSTEM ===
$ranks = [
    ['title' => 'Starter',     'min_earnings' => 0,      'min_referrals' => 0,  'icon' => 'fa-seedling',       'color' => 'slate'],
    ['title' => 'Bronze',      'min_earnings' => 1000,    'min_referrals' => 3,  'icon' => 'fa-medal',          'color' => 'amber'],
    ['title' => 'Silver',      'min_earnings' => 5000,    'min_referrals' => 10, 'icon' => 'fa-award',          'color' => 'gray'],
    ['title' => 'Gold',        'min_earnings' => 15000,   'min_referrals' => 25, 'icon' => 'fa-trophy',         'color' => 'yellow'],
    ['title' => 'Platinum',    'min_earnings' => 50000,   'min_referrals' => 50, 'icon' => 'fa-gem',            'color' => 'blue'],
    ['title' => 'Diamond',     'min_earnings' => 100000,  'min_referrals' => 100,'icon' => 'fa-crown',          'color' => 'purple'],
    ['title' => 'Royal Crown', 'min_earnings' => 500000,  'min_referrals' => 250,'icon' => 'fa-star',           'color' => 'rose'],
];

// Update current user's rank
$my_earnings = (float)($current_user['tamount'] ?? 0);
$my_referrals = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ? AND level = 2", [$username]);
$my_rank = 'Starter';
foreach ($ranks as $r) {
    if ($my_earnings >= $r['min_earnings'] && $my_referrals >= $r['min_referrals']) {
        $my_rank = $r['title'];
    }
}
mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET rank_title = ? WHERE Id = ?", [$my_rank, $current_user_id]);

// Find current user's position in leaderboard
$my_position = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 1 AND tamount > ?", [$my_earnings]) + 1;

// Get rank details for display
function get_rank_info($rank_title, $ranks) {
    foreach ($ranks as $r) {
        if ($r['title'] === $rank_title) return $r;
    }
    return $ranks[0];
}

$my_rank_info = get_rank_info($my_rank, $ranks);

// Next rank
$next_rank = null;
$found_current = false;
foreach ($ranks as $r) {
    if ($found_current) { $next_rank = $r; break; }
    if ($r['title'] === $my_rank) { $found_current = true; }
}
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
@keyframes pulse-glow { 0%, 100% { box-shadow: 0 0 0 0 rgba(168,85,247,0.3); } 50% { box-shadow: 0 0 20px 5px rgba(168,85,247,0.15); } }
.fade-in { animation: fadeInUp 0.5s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; } .delay-5 { animation-delay: 0.5s; }
.rank-card { animation: pulse-glow 3s ease-in-out infinite; }
.gold-shimmer { background: linear-gradient(90deg, #fbbf24 0%, #fef3c7 50%, #fbbf24 100%); background-size: 200% 100%; animation: shimmer 3s linear infinite; }
.podium-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
.podium-2 { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); }
.podium-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
</style>

<!-- My Rank Card -->
<div class="fade-in delay-1 mb-6">
  <div class="rank-card bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-2xl p-6 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4"></div>
    <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>
    <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
          <i class="fa-solid <?php echo $my_rank_info['icon']; ?> text-2xl"></i>
        </div>
        <div>
          <div class="text-sm text-white/70 font-medium">Your Current Rank</div>
          <div class="text-2xl font-bold mt-0.5"><?php echo mlmp_escape($my_rank); ?></div>
          <div class="text-sm text-white/80 mt-1">
            <i class="fa-solid fa-chart-line mr-1"></i> Position #<?php echo $my_position; ?> overall
          </div>
        </div>
      </div>
      <div class="flex gap-4 sm:gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold">₹<?php echo number_format($my_earnings, 0); ?></div>
          <div class="text-xs text-white/60 mt-0.5">Total Earnings</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold"><?php echo $my_referrals; ?></div>
          <div class="text-xs text-white/60 mt-0.5">Referrals</div>
        </div>
      </div>
    </div>
    <?php if ($next_rank): ?>
    <div class="mt-5 pt-4 border-t border-white/15">
      <div class="flex items-center justify-between text-sm mb-2">
        <span class="text-white/70">Next: <strong class="text-white"><?php echo $next_rank['title']; ?></strong></span>
        <span class="text-white/70">
          ₹<?php echo number_format($next_rank['min_earnings'] - $my_earnings, 0); ?> more &amp; 
          <?php echo max(0, $next_rank['min_referrals'] - $my_referrals); ?> more referrals needed
        </span>
      </div>
      <?php 
        $earning_progress = min(100, ($my_earnings / max(1, $next_rank['min_earnings'])) * 100);
      ?>
      <div class="w-full h-2.5 bg-white/15 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-yellow-300 to-yellow-400 rounded-full transition-all duration-1000" style="width: <?php echo $earning_progress; ?>%"></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Top Earners & Top Recruiters -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
  
  <!-- TOP EARNERS -->
  <div class="fade-in delay-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center">
          <i class="fa-solid fa-coins text-amber-500"></i>
        </div>
        <div>
          <h3 class="text-[15px] font-bold text-slate-900">Top Earners</h3>
          <p class="text-[11px] text-slate-400">Highest earnings</p>
        </div>
      </div>
      <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg"><i class="fa-solid fa-trophy mr-1"></i> Top 10</span>
    </div>

    <!-- Podium for top 3 -->
    <?php if (count($top_earners) >= 3): ?>
    <div class="flex items-end justify-center gap-3 px-6 pt-6 pb-4">
      <!-- 2nd Place -->
      <div class="text-center flex-1 max-w-[110px]">
        <div class="w-12 h-12 mx-auto rounded-full bg-gradient-to-br from-slate-300 to-slate-400 flex items-center justify-center text-white font-bold text-lg shadow-lg mb-2">2</div>
        <div class="text-xs font-bold text-slate-800 truncate"><?php echo mlmp_escape($top_earners[1]['fname']); ?></div>
        <div class="text-[10px] text-slate-500">₹<?php echo number_format((float)$top_earners[1]['tamount'], 0); ?></div>
        <div class="podium-2 h-16 rounded-t-xl mt-2 flex items-center justify-center">
          <i class="fa-solid fa-medal text-white text-xl"></i>
        </div>
      </div>
      <!-- 1st Place -->
      <div class="text-center flex-1 max-w-[120px]">
        <div class="w-14 h-14 mx-auto rounded-full bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center text-white font-bold text-xl shadow-lg mb-2 ring-4 ring-yellow-200">
          <i class="fa-solid fa-crown"></i>
        </div>
        <div class="text-sm font-bold text-slate-900 truncate"><?php echo mlmp_escape($top_earners[0]['fname']); ?></div>
        <div class="text-xs text-amber-600 font-semibold">₹<?php echo number_format((float)$top_earners[0]['tamount'], 0); ?></div>
        <div class="podium-1 h-24 rounded-t-xl mt-2 flex items-center justify-center">
          <i class="fa-solid fa-trophy text-white text-2xl"></i>
        </div>
      </div>
      <!-- 3rd Place -->
      <div class="text-center flex-1 max-w-[110px]">
        <div class="w-12 h-12 mx-auto rounded-full bg-gradient-to-br from-amber-600 to-amber-700 flex items-center justify-center text-white font-bold text-lg shadow-lg mb-2">3</div>
        <div class="text-xs font-bold text-slate-800 truncate"><?php echo mlmp_escape($top_earners[2]['fname']); ?></div>
        <div class="text-[10px] text-slate-500">₹<?php echo number_format((float)$top_earners[2]['tamount'], 0); ?></div>
        <div class="podium-3 h-12 rounded-t-xl mt-2 flex items-center justify-center">
          <i class="fa-solid fa-award text-white text-lg"></i>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Full list -->
    <div class="px-4 pb-4">
      <?php foreach ($top_earners as $i => $earner): 
        $is_me = ($earner['username'] === $username);
        $pos = $i + 1;
      ?>
      <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo $is_me ? 'bg-purple-50 border border-purple-200' : 'hover:bg-slate-50'; ?> transition-colors mb-1">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0
          <?php echo $pos === 1 ? 'bg-yellow-100 text-yellow-700' : ($pos === 2 ? 'bg-slate-100 text-slate-600' : ($pos === 3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-50 text-slate-500')); ?>">
          <?php echo $pos; ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-[13px] font-semibold text-slate-800 truncate">
            <?php echo mlmp_escape($earner['fname']); ?>
            <?php if ($is_me): ?><span class="text-[10px] bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded-full ml-1">You</span><?php endif; ?>
          </div>
          <div class="text-[11px] text-slate-400"><?php echo mlmp_escape($earner['rank_title']); ?> · <?php echo (int)$earner['referral_count']; ?> referrals</div>
        </div>
        <div class="text-sm font-bold text-slate-800">₹<?php echo number_format((float)$earner['tamount'], 0); ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($top_earners)): ?>
      <div class="text-center py-8 text-slate-400">
        <i class="fa-solid fa-chart-simple text-3xl mb-2"></i>
        <p class="text-sm">No data yet. Be the first!</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TOP RECRUITERS -->
  <div class="fade-in delay-3 bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center">
          <i class="fa-solid fa-users text-emerald-500"></i>
        </div>
        <div>
          <h3 class="text-[15px] font-bold text-slate-900">Top Recruiters</h3>
          <p class="text-[11px] text-slate-400">Most direct referrals</p>
        </div>
      </div>
      <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg"><i class="fa-solid fa-user-plus mr-1"></i> Top 10</span>
    </div>

    <!-- Full list -->
    <div class="px-4 py-4">
      <?php foreach ($top_recruiters as $i => $recruiter): 
        $is_me = ($recruiter['username'] === $username);
        $pos = $i + 1;
        $max_refs = max(1, (int)($top_recruiters[0]['referral_count'] ?? 1));
        $bar_width = max(5, ((int)$recruiter['referral_count'] / $max_refs) * 100);
      ?>
      <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo $is_me ? 'bg-purple-50 border border-purple-200' : 'hover:bg-slate-50'; ?> transition-colors mb-1">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0
          <?php echo $pos === 1 ? 'bg-emerald-100 text-emerald-700' : ($pos === 2 ? 'bg-slate-100 text-slate-600' : ($pos === 3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-50 text-slate-500')); ?>">
          <?php echo $pos; ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-[13px] font-semibold text-slate-800 truncate">
            <?php echo mlmp_escape($recruiter['fname']); ?>
            <?php if ($is_me): ?><span class="text-[10px] bg-purple-100 text-purple-600 px-1.5 py-0.5 rounded-full ml-1">You</span><?php endif; ?>
          </div>
          <div class="mt-1 w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 rounded-full" style="width: <?php echo $bar_width; ?>%"></div>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="text-sm font-bold text-slate-800"><?php echo (int)$recruiter['referral_count']; ?></span>
          <span class="text-[10px] text-slate-400">refs</span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($top_recruiters)): ?>
      <div class="text-center py-8 text-slate-400">
        <i class="fa-solid fa-users text-3xl mb-2"></i>
        <p class="text-sm">No recruiters yet.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- RANK SYSTEM OVERVIEW -->
<div class="fade-in delay-4 bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
    <div class="w-9 h-9 bg-violet-50 rounded-xl flex items-center justify-center">
      <i class="fa-solid fa-ranking-star text-violet-500"></i>
    </div>
    <div>
      <h3 class="text-[15px] font-bold text-slate-900">Rank System</h3>
      <p class="text-[11px] text-slate-400">Climb ranks by earning more and recruiting</p>
    </div>
  </div>
  <div class="p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
      <?php foreach ($ranks as $rank): 
        $is_current = ($rank['title'] === $my_rank);
        $is_achieved = false;
        foreach ($ranks as $check) {
          if ($check['title'] === $rank['title']) break;
          if ($check['title'] === $my_rank) { $is_achieved = false; break; }
          $is_achieved = true;
        }
        $achieved = ($my_earnings >= $rank['min_earnings'] && $my_referrals >= $rank['min_referrals']);
      ?>
      <div class="relative p-4 rounded-xl border-2 transition-all <?php echo $is_current ? 'border-purple-400 bg-purple-50 shadow-lg shadow-purple-100' : ($achieved ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-100 bg-slate-50/50'); ?>">
        <?php if ($is_current): ?>
        <div class="absolute -top-2.5 left-1/2 -translate-x-1/2 bg-purple-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full">CURRENT</div>
        <?php elseif ($achieved): ?>
        <div class="absolute top-2 right-2"><i class="fa-solid fa-circle-check text-emerald-500"></i></div>
        <?php endif; ?>
        <div class="text-center">
          <div class="w-10 h-10 mx-auto rounded-xl flex items-center justify-center mb-2
            <?php echo $is_current ? 'bg-purple-100 text-purple-600' : ($achieved ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400'); ?>">
            <i class="fa-solid <?php echo $rank['icon']; ?> text-lg"></i>
          </div>
          <div class="text-sm font-bold <?php echo $is_current ? 'text-purple-700' : 'text-slate-800'; ?>"><?php echo $rank['title']; ?></div>
          <div class="text-[11px] text-slate-500 mt-1">
            ₹<?php echo number_format($rank['min_earnings'], 0); ?> earnings
          </div>
          <div class="text-[11px] text-slate-500">
            <?php echo $rank['min_referrals']; ?> referrals
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include("layout_footer.php"); ?>
