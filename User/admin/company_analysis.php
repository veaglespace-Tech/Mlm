<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Company Analysis & Business Intelligence';
$active_nav = 'company_analysis';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include("layout_header.php");

// -------------------------------------------------------------
// Time Range Filter
// -------------------------------------------------------------
$range = $_GET['range'] ?? 'all';
$date_sql_payu    = "1=1";
$date_sql_pair    = "1=1";
$date_sql_user    = "1=1";
$date_sql_payout  = "1=1";

switch ($range) {
    case 'today':
        $date_sql_payu   = "DATE(p.date) = CURDATE()";
        $date_sql_pair   = "DATE(created_at) = CURDATE()";
        $date_sql_user   = "DATE(doj) = CURDATE()";
        $date_sql_payout = "DATE(createdtime) = CURDATE()";
        $range_label     = "Today";
        break;
    case 'last_7_days':
        $date_sql_payu   = "p.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $date_sql_pair   = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $date_sql_user   = "doj >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $date_sql_payout = "createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $range_label     = "Last 7 Days";
        break;
    case 'this_month':
        $date_sql_payu   = "YEAR(p.date) = YEAR(CURDATE()) AND MONTH(p.date) = MONTH(CURDATE())";
        $date_sql_pair   = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $date_sql_user   = "YEAR(doj) = YEAR(CURDATE()) AND MONTH(doj) = MONTH(CURDATE())";
        $date_sql_payout = "YEAR(createdtime) = YEAR(CURDATE()) AND MONTH(createdtime) = MONTH(CURDATE())";
        $range_label     = "This Month (" . date('M Y') . ")";
        break;
    case 'this_year':
        $date_sql_payu   = "YEAR(p.date) = YEAR(CURDATE())";
        $date_sql_pair   = "YEAR(created_at) = YEAR(CURDATE())";
        $date_sql_user   = "YEAR(doj) = YEAR(CURDATE())";
        $date_sql_payout = "YEAR(createdtime) = YEAR(CURDATE())";
        $range_label     = "This Year (" . date('Y') . ")";
        break;
    default:
        $range = 'all';
        $range_label = "All Time (Lifetime)";
        break;
}

// -------------------------------------------------------------
// 1. FINANCIAL HEALTH & CASHFLOW METRICS
// -------------------------------------------------------------
// Gross Turnover: From PayU Payments
$payu_inflow_row = mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(p.price), 0) AS total_inflow FROM payu_payments p WHERE $date_sql_payu");
$gross_turnover = (float)($payu_inflow_row['total_inflow'] ?? 0);

// Fallback: If PayU payments table has less records than active users * package price in demo
$active_package_turnover_row = mlmp_pdo_fetch($pdo, "
    SELECT COALESCE(SUM(pkg.price), 0) AS package_total
    FROM affiliateuser u
    JOIN packages pkg ON u.pcktaken = pkg.id
    WHERE u.level = 2 AND u.active = 1 AND $date_sql_user
");
$package_turnover = (float)($active_package_turnover_row['package_total'] ?? 0);
if ($gross_turnover < $package_turnover && $package_turnover > 0) {
    $gross_turnover = $package_turnover;
}

// Total Commissions Generated (Gross Pair Commissions)
$pair_comm_row = mlmp_pdo_fetch($pdo, "
    SELECT 
        COALESCE(SUM(gross_amount), 0) AS total_gross_pair,
        COALESCE(SUM(tds_amount), 0) AS total_tds,
        COALESCE(SUM(net_amount), 0) AS total_net_pair,
        COALESCE(SUM(pairs_count), 0) AS total_pairs
    FROM pairing_transactions
    WHERE $date_sql_pair
");
$total_gross_pair = (float)($pair_comm_row['total_gross_pair'] ?? 0);
$total_tds        = (float)($pair_comm_row['total_tds'] ?? 0);
$total_net_pair   = (float)($pair_comm_row['total_net_pair'] ?? 0);
$total_pairs_matched = (int)($pair_comm_row['total_pairs'] ?? 0);

// Direct Referral Bonuses Paid
$referral_bonus_row = mlmp_pdo_fetch($pdo, "
    SELECT COALESCE(SUM(tamount), 0) AS total_user_earnings 
    FROM affiliateuser 
    WHERE level = 2 AND $date_sql_user
");
$total_user_earnings = (float)($referral_bonus_row['total_user_earnings'] ?? 0);
// Direct commission is earnings minus pair commission
$total_direct_bonus = max(0.0, $total_user_earnings - $total_net_pair);

// Total Outflow / Commissions Distributed
$total_commissions_distributed = $total_gross_pair + $total_direct_bonus;

// Net Company Retained Profit
$net_company_profit = $gross_turnover - $total_commissions_distributed;
$profit_margin = ($gross_turnover > 0) ? ($net_company_profit / $gross_turnover) * 100 : 0.0;

// Payout Ratio / Sustainability Index
$payout_ratio = ($gross_turnover > 0) ? ($total_commissions_distributed / $gross_turnover) * 100 : 0.0;

// Pending Withdrawal Liabilities
$pending_withdrawals_row = mlmp_pdo_fetch($pdo, "
    SELECT COUNT(*) AS count, COALESCE(SUM(payment_amount), 0) AS total 
    FROM payments 
    WHERE payment_status = 0
");
$pending_withdraw_count = (int)($pending_withdrawals_row['count'] ?? 0);
$pending_withdraw_amount = (float)($pending_withdrawals_row['total'] ?? 0);

// Completed Payouts
$completed_withdrawals_row = mlmp_pdo_fetch($pdo, "
    SELECT COUNT(*) AS count, COALESCE(SUM(payment_amount), 0) AS total 
    FROM payments 
    WHERE payment_status = 1 AND $date_sql_payout
");
$completed_withdraw_count = (int)($completed_withdrawals_row['count'] ?? 0);
$completed_withdraw_amount = (float)($completed_withdrawals_row['total'] ?? 0);

// -------------------------------------------------------------
// 2. NETWORK & MEMBERSHIP METRICS
// -------------------------------------------------------------
$total_members   = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2");
$active_members  = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 1");
$inactive_members= mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 0");
$pending_approval_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM pending_registrations WHERE admin_approval_status = 'Pending'");
$conversion_rate = ($total_members > 0) ? ($active_members / $total_members) * 100 : 0.0;

// Binary Left vs Right Network Balance
$binary_tree_row = mlmp_pdo_fetch($pdo, "
    SELECT 
        COALESCE(SUM(left_count), 0) AS total_left,
        COALESCE(SUM(right_count), 0) AS total_right,
        COUNT(CASE WHEN is_binary_qualified = 1 THEN 1 END) AS qualified_count
    FROM affiliateuser 
    WHERE level = 2
");
$total_left_nodes  = (int)($binary_tree_row['total_left'] ?? 0);
$total_right_nodes = (int)($binary_tree_row['total_right'] ?? 0);
$qualified_count   = (int)($binary_tree_row['qualified_count'] ?? 0);
$total_nodes_placed = $total_left_nodes + $total_right_nodes;

$left_percent  = ($total_nodes_placed > 0) ? round(($total_left_nodes / $total_nodes_placed) * 100, 1) : 50;
$right_percent = ($total_nodes_placed > 0) ? round(($total_right_nodes / $total_nodes_placed) * 100, 1) : 50;

// -------------------------------------------------------------
// 3. PACKAGE REVENUE & DISTRIBUTION BREAKDOWN
// -------------------------------------------------------------
$package_stats = mlmp_pdo_fetch_all($pdo, "
    SELECT 
        pkg.id,
        pkg.name,
        pkg.price,
        pkg.binary_percent,
        pkg.sponsor_percent,
        COUNT(u.Id) AS member_count,
        COALESCE(SUM(pkg.price), 0) AS total_revenue
    FROM packages pkg
    LEFT JOIN affiliateuser u ON u.pcktaken = pkg.id AND u.level = 2 AND u.active = 1
    GROUP BY pkg.id, pkg.name, pkg.price, pkg.binary_percent, pkg.sponsor_percent
    ORDER BY total_revenue DESC, member_count DESC
");

// -------------------------------------------------------------
// 4. TOP EARNERS (PAYOUT CONCENTRATION RISK)
// -------------------------------------------------------------
$top_earners = mlmp_pdo_fetch_all($pdo, "
    SELECT 
        u.Id,
        u.username,
        u.fname,
        u.tamount AS total_earned,
        u.paid_pairs,
        pkg.name AS package_name
    FROM affiliateuser u
    LEFT JOIN packages pkg ON u.pcktaken = pkg.id
    WHERE u.level = 2 AND u.tamount > 0
    ORDER BY u.tamount DESC
    LIMIT 5
");

$top_5_earnings_sum = 0.0;
foreach ($top_earners as $te) {
    $top_5_earnings_sum += (float)$te['total_earned'];
}
$top_earners_share = ($total_user_earnings > 0) ? ($top_5_earnings_sum / $total_user_earnings) * 100 : 0.0;

// -------------------------------------------------------------
// 5. MONTHLY FINANCIAL TRENDS (LAST 6 MONTHS)
// -------------------------------------------------------------
$monthly_trends = mlmp_pdo_fetch_all($pdo, "
    SELECT 
        DATE_FORMAT(doj, '%b %Y') AS month_label,
        DATE_FORMAT(doj, '%Y-%m') AS ym,
        COUNT(*) AS signups
    FROM affiliateuser
    WHERE level = 2 AND doj >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym, month_label
    ORDER BY ym ASC
");

// Fill trend data
$trend_labels = [];
$trend_signups = [];
$trend_turnover = [];
$trend_payouts = [];

foreach ($monthly_trends as $mt) {
    $ym = $mt['ym'];
    $trend_labels[] = $mt['month_label'];
    $trend_signups[] = (int)$mt['signups'];

    // Inflow for that month
    $m_inflow = mlmp_pdo_fetch($pdo, "
        SELECT COALESCE(SUM(pkg.price), 0) AS total 
        FROM affiliateuser u 
        JOIN packages pkg ON u.pcktaken = pkg.id 
        WHERE u.level = 2 AND u.active = 1 AND DATE_FORMAT(u.doj, '%Y-%m') = ?
    ", [$ym]);
    $trend_turnover[] = (float)($m_inflow['total'] ?? 0);

    // Outflow for that month
    $m_outflow = mlmp_pdo_fetch($pdo, "
        SELECT COALESCE(SUM(gross_amount), 0) AS total 
        FROM pairing_transactions 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    ", [$ym]);
    $trend_payouts[] = (float)($m_outflow['total'] ?? 0);
}

// Format packages for Donut chart
$chart_pkg_labels = [];
$chart_pkg_counts = [];
$chart_pkg_revenues = [];
foreach ($package_stats as $ps) {
    $chart_pkg_labels[] = $ps['name'];
    $chart_pkg_counts[] = (int)$ps['member_count'];
    $chart_pkg_revenues[] = (float)$ps['total_revenue'];
}
?>

<style>
@media print {
  aside, nav, .no-print, header { display: none !important; }
  main, .dash-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
  .shadow-sm, .shadow-md, .shadow-xl { box-shadow: none !important; }
}
</style>

<div class="dash-content space-y-6">

  <!-- Header Banner with Filter & Print Actions -->
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-3xl shadow-xl border border-slate-800">
    <div>
      <div class="flex items-center gap-2.5 text-xs font-bold text-indigo-400 uppercase tracking-widest mb-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        Executive Business Intelligence
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Company Analysis & P&L</h1>
      <p class="text-sm text-slate-300 mt-1">Real-time financial solvency, MLM payout sustainability, and network growth analytics.</p>
    </div>

    <!-- Filter Buttons + Export -->
    <div class="flex flex-wrap items-center gap-2 no-print">
      <div class="bg-white/10 backdrop-blur-md rounded-2xl p-1 flex items-center gap-1 border border-white/10 text-xs font-semibold">
        <a href="?range=all" class="px-3 py-1.5 rounded-xl transition-all <?php echo $range === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/5'; ?>">
          All Time
        </a>
        <a href="?range=this_month" class="px-3 py-1.5 rounded-xl transition-all <?php echo $range === 'this_month' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/5'; ?>">
          This Month
        </a>
        <a href="?range=last_7_days" class="px-3 py-1.5 rounded-xl transition-all <?php echo $range === 'last_7_days' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/5'; ?>">
          7 Days
        </a>
        <a href="?range=today" class="px-3 py-1.5 rounded-xl transition-all <?php echo $range === 'today' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-white/5'; ?>">
          Today
        </a>
      </div>

      <button onclick="window.print()" class="px-3.5 py-2 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all border border-white/10 flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-print"></i> Print Report
      </button>
    </div>
  </div>

  <!-- Row 1: Key Executive P&L Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    
    <!-- Gross Inflow -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Turnover (Inflow)</span>
        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
          <i class="fa-solid fa-arrow-down-left"></i>
        </div>
      </div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
        ₹<?php echo number_format($gross_turnover, 2); ?>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-2 font-medium">
        <span class="text-emerald-600 font-bold"><i class="fa-solid fa-layer-group"></i> Packages Sold</span>
        <span>• <?php echo $range_label; ?></span>
      </div>
      <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-500 absolute bottom-0 left-0"></div>
    </div>

    <!-- Total Outflow -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Commissions Outflow</span>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
          <i class="fa-solid fa-arrow-up-right"></i>
        </div>
      </div>
      <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight">
        ₹<?php echo number_format($total_commissions_distributed, 2); ?>
      </div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mt-2 font-medium">
        <span>Pairs: ₹<?php echo number_format($total_gross_pair, 0); ?></span>
        <span>• Direct: ₹<?php echo number_format($total_direct_bonus, 0); ?></span>
      </div>
      <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500 absolute bottom-0 left-0"></div>
    </div>

    <!-- Net Company Profit -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Net Company Profit</span>
        <div class="w-10 h-10 rounded-2xl <?php echo $net_company_profit >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'; ?> flex items-center justify-center text-base">
          <i class="fa-solid fa-vault"></i>
        </div>
      </div>
      <div class="text-2xl sm:text-3xl font-extrabold <?php echo $net_company_profit >= 0 ? 'text-emerald-600' : 'text-red-600'; ?> tracking-tight">
        ₹<?php echo number_format($net_company_profit, 2); ?>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-2 font-medium">
        <span class="<?php echo $profit_margin >= 0 ? 'text-emerald-600' : 'text-red-600'; ?> font-bold">
          <?php echo number_format($profit_margin, 1); ?>% Margin
        </span>
        <span>after all payouts</span>
      </div>
      <div class="h-1 w-full <?php echo $net_company_profit >= 0 ? 'bg-gradient-to-r from-emerald-400 to-teal-500' : 'bg-gradient-to-r from-red-500 to-rose-600'; ?> absolute bottom-0 left-0"></div>
    </div>

    <!-- TDS Collected for Compliance -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tax Deducted (TDS)</span>
        <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-base">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
      </div>
      <div class="text-2xl sm:text-3xl font-extrabold text-cyan-700 tracking-tight">
        ₹<?php echo number_format($total_tds, 2); ?>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-2 font-medium">
        <span class="text-cyan-600 font-bold">5% Govt TDS</span>
        <span>Retained for filing</span>
      </div>
      <div class="h-1 w-full bg-gradient-to-r from-cyan-400 to-blue-500 absolute bottom-0 left-0"></div>
    </div>
  </div>

  <!-- Row 2: Solvency Gauge & Binary Network Balance -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    
    <!-- Payout Sustainability Meter -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-scale-balanced text-indigo-600"></i> Solvency & Payout Ratio
          </h3>
          <?php if ($payout_ratio <= 60): ?>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
              Low Risk (Safe)
            </span>
          <?php elseif ($payout_ratio <= 75): ?>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
              Moderate Risk
            </span>
          <?php else: ?>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-800">
              High Payout Alert
            </span>
          <?php endif; ?>
        </div>
        <p class="text-xs text-slate-500">Percentage of company revenue paid out as member commissions.</p>
      </div>

      <div class="my-6">
        <div class="flex items-baseline justify-between mb-2">
          <span class="text-3xl font-extrabold text-slate-800"><?php echo number_format($payout_ratio, 1); ?>%</span>
          <span class="text-xs font-semibold text-slate-500">Benchmark: <span class="text-emerald-600 font-bold">40% - 65%</span></span>
        </div>
        <!-- Progress Bar -->
        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden flex">
          <div class="h-full rounded-full transition-all duration-700 <?php echo $payout_ratio <= 60 ? 'bg-emerald-500' : ($payout_ratio <= 75 ? 'bg-amber-500' : 'bg-red-500'); ?>" 
               style="width: <?php echo min(100, max(5, $payout_ratio)); ?>%;"></div>
        </div>
      </div>

      <!-- Quick Summary List -->
      <div class="space-y-2 text-xs border-t border-slate-100 pt-4">
        <div class="flex justify-between text-slate-600">
          <span>Unpaid Withdrawal Liability:</span>
          <strong class="text-amber-600">₹<?php echo number_format($pending_withdraw_amount, 2); ?> (<?php echo $pending_withdraw_count; ?> requests)</strong>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Paid Withdrawals Completed:</span>
          <strong class="text-emerald-600">₹<?php echo number_format($completed_withdraw_amount, 2); ?> (<?php echo $completed_withdraw_count; ?> disbursed)</strong>
        </div>
      </div>
    </div>

    <!-- Binary Network Placement Symmetry -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-sitemap text-indigo-600"></i> Binary Tree Symmetry
          </h3>
          <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full">
            <?php echo $total_nodes_placed; ?> Total Nodes
          </span>
        </div>
        <p class="text-xs text-slate-500">Distribution between Left Leg & Right Leg across entire network.</p>
      </div>

      <!-- Left vs Right Visual Gauge -->
      <div class="my-6 space-y-4">
        <div class="grid grid-cols-2 gap-3 text-center">
          <div class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100">
            <div class="text-[11px] font-bold text-indigo-600 uppercase">Left Leg</div>
            <div class="text-xl font-extrabold text-indigo-900 mt-0.5"><?php echo number_format($total_left_nodes); ?></div>
            <div class="text-xs text-indigo-600 font-medium"><?php echo $left_percent; ?>%</div>
          </div>
          <div class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100">
            <div class="text-[11px] font-bold text-purple-600 uppercase">Right Leg</div>
            <div class="text-xl font-extrabold text-purple-900 mt-0.5"><?php echo number_format($total_right_nodes); ?></div>
            <div class="text-xs text-purple-600 font-medium"><?php echo $right_percent; ?>%</div>
          </div>
        </div>

        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden flex">
          <div class="bg-indigo-600 h-full transition-all" style="width: <?php echo $left_percent; ?>%;"></div>
          <div class="bg-purple-500 h-full transition-all" style="width: <?php echo $right_percent; ?>%;"></div>
        </div>
      </div>

      <div class="space-y-2 text-xs border-t border-slate-100 pt-4">
        <div class="flex justify-between text-slate-600">
          <span>Binary Qualified Members:</span>
          <strong class="text-indigo-600"><?php echo $qualified_count; ?> of <?php echo $total_members; ?> users</strong>
        </div>
        <div class="flex justify-between text-slate-600">
          <span>Total Pairs Completed:</span>
          <strong class="text-slate-800"><?php echo number_format($total_pairs_matched); ?> pairs</strong>
        </div>
      </div>
    </div>

    <!-- Membership Conversion Funnel -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-users-gear text-indigo-600"></i> Conversion Funnel
          </h3>
          <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full">
            <?php echo number_format($conversion_rate, 1); ?>% Conversion
          </span>
        </div>
        <p class="text-xs text-slate-500">From registration to paid active package subscribers.</p>
      </div>

      <!-- Funnel Progressions -->
      <div class="my-4 space-y-3">
        <div>
          <div class="flex justify-between text-xs font-semibold mb-1">
            <span class="text-slate-600">Active Paid Members</span>
            <span class="text-emerald-600 font-bold"><?php echo $active_members; ?> (<?php echo number_format($conversion_rate, 1); ?>%)</span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2">
            <div class="bg-emerald-500 h-2 rounded-full" style="width: <?php echo $conversion_rate; ?>%;"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs font-semibold mb-1">
            <span class="text-slate-600">Free / Inactive Members</span>
            <span class="text-slate-500"><?php echo $inactive_members; ?></span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2">
            <div class="bg-slate-300 h-2 rounded-full" style="width: <?php echo $total_members > 0 ? (100 - $conversion_rate) : 0; ?>%;"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs font-semibold mb-1">
            <span class="text-slate-600">Pending Approvals</span>
            <span class="text-amber-600 font-bold"><?php echo $pending_approval_count; ?></span>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-2">
            <div class="bg-amber-400 h-2 rounded-full" style="width: <?php echo min(100, $pending_approval_count * 10); ?>%;"></div>
          </div>
        </div>
      </div>

      <div class="border-t border-slate-100 pt-3 text-xs flex justify-between text-slate-500">
        <span>Total Registered Members:</span>
        <strong class="text-slate-800"><?php echo number_format($total_members); ?></strong>
      </div>
    </div>
  </div>

  <!-- Row 3: Interactive Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    
    <!-- Main Chart: Financial Flow Trend (Turnover vs Payouts) -->
    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-chart-line text-indigo-600"></i> Cash Inflow vs Payouts Trend
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">Monthly trajectory of revenue collected versus commissions distributed.</p>
        </div>
      </div>

      <div class="h-72 w-full">
        <canvas id="financialFlowChart"></canvas>
      </div>
    </div>

    <!-- Package Revenue Share (Donut) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
      <div>
        <h3 class="font-bold text-slate-800 text-base flex items-center gap-2 mb-1">
          <i class="fa-solid fa-chart-pie text-indigo-600"></i> Package Share
        </h3>
        <p class="text-xs text-slate-500">Revenue contribution per membership package.</p>
      </div>

      <div class="h-60 w-full relative flex items-center justify-center my-2">
        <canvas id="packageShareChart"></canvas>
      </div>

      <div class="text-center text-xs text-slate-400">
        Active Package Subscribers Distribution
      </div>
    </div>
  </div>

  <!-- Row 4: Package Performance Table & Top Leaders Concentration -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    
    <!-- Package Breakdown Table -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-cubes text-indigo-600"></i> Package Performance Matrix
          </h3>
          <p class="text-xs text-slate-500">Detailed sales, subscribers, and commission margins by package.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-semibold text-xs uppercase tracking-wider">
              <th class="py-3 px-4">Package</th>
              <th class="py-3 px-4">Price</th>
              <th class="py-3 px-4">Subscribers</th>
              <th class="py-3 px-4">Binary Matching</th>
              <th class="py-3 px-4">Sponsor Bonus</th>
              <th class="py-3 px-4 text-right">Total Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <?php if (empty($package_stats)): ?>
              <tr><td colspan="6" class="p-6 text-center text-slate-400">No packages configured.</td></tr>
            <?php else: ?>
              <?php foreach ($package_stats as $pkg): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-3.5 px-4 font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <?php echo htmlspecialchars($pkg['name']); ?>
                  </td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">₹<?php echo number_format($pkg['price'], 2); ?></td>
                  <td class="py-3.5 px-4">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 font-bold text-slate-700">
                      <?php echo number_format($pkg['member_count']); ?> members
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-indigo-600 font-semibold"><?php echo (float)$pkg['binary_percent']; ?>%</td>
                  <td class="py-3.5 px-4 text-purple-600 font-semibold"><?php echo (float)$pkg['sponsor_percent']; ?>%</td>
                  <td class="py-3.5 px-4 text-right font-extrabold text-slate-900">₹<?php echo number_format($pkg['total_revenue'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payout Concentration: Top 5 Earners -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
      <div class="p-5 border-b border-slate-100">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
            <i class="fa-solid fa-trophy text-amber-500"></i> Top 5 Earners
          </h3>
          <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full">
            <?php echo number_format($top_earners_share, 1); ?>% of Payout
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Earner concentration monitoring to avoid single-leader dependency.</p>
      </div>

      <div class="p-5 space-y-3 flex-1">
        <?php if (empty($top_earners)): ?>
          <p class="text-xs text-slate-400 text-center py-6">No payout data available yet.</p>
        <?php else: ?>
          <?php foreach ($top_earners as $idx => $te): ?>
            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50/70 border border-slate-100">
              <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-extrabold flex items-center justify-center">
                  #<?php echo $idx + 1; ?>
                </div>
                <div>
                  <div class="font-bold text-xs text-slate-800"><?php echo htmlspecialchars($te['fname']); ?></div>
                  <div class="text-[11px] text-slate-400">@<?php echo htmlspecialchars($te['username']); ?> • <?php echo htmlspecialchars($te['package_name'] ?? 'Plan'); ?></div>
                </div>
              </div>
              <div class="text-right">
                <div class="font-extrabold text-xs text-indigo-600">₹<?php echo number_format($te['total_earned'], 2); ?></div>
                <div class="text-[10px] text-slate-400"><?php echo $te['paid_pairs']; ?> pairs</div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="p-4 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-500 flex justify-between">
        <span>Combined Top 5 Total:</span>
        <strong class="text-slate-800">₹<?php echo number_format($top_5_earnings_sum, 2); ?></strong>
      </div>
    </div>
  </div>

</div>

<!-- Initialize Chart.js Scripts -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Chart 1: Financial Flow
  const ctxFlow = document.getElementById('financialFlowChart').getContext('2d');
  new Chart(ctxFlow, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($trend_labels); ?>,
      datasets: [
        {
          label: 'Gross Inflow (Turnover)',
          data: <?php echo json_encode($trend_turnover); ?>,
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.5,
          pointBackgroundColor: '#4f46e5',
          pointRadius: 4
        },
        {
          label: 'Commissions Outflow',
          data: <?php echo json_encode($trend_payouts); ?>,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.08)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.5,
          pointBackgroundColor: '#f59e0b',
          pointRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { font: { size: 11, family: 'Inter, sans-serif' }, boxWidth: 12 }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ₹' + Number(context.raw).toLocaleString();
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) { return '₹' + value.toLocaleString(); },
            font: { size: 10 }
          },
          grid: { color: 'rgba(226, 232, 240, 0.6)' }
        },
        x: {
          ticks: { font: { size: 10 } },
          grid: { display: false }
        }
      }
    }
  });

  // Chart 2: Package Share Donut
  const ctxPkg = document.getElementById('packageShareChart').getContext('2d');
  new Chart(ctxPkg, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode($chart_pkg_labels); ?>,
      datasets: [{
        data: <?php echo json_encode($chart_pkg_revenues); ?>,
        backgroundColor: [
          '#4f46e5', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ec4899'
        ],
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { font: { size: 10, family: 'Inter, sans-serif' }, boxWidth: 10 }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.label + ': ₹' + Number(context.raw).toLocaleString();
            }
          }
        }
      }
    }
  });
});
</script>

<?php include("layout_footer.php"); ?>
