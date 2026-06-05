<?php
include_once ("z_db.php");
// Inialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
        print "
				<script language='javascript'>
					window.location = 'index.php';
				</script>
			";
        exit;
}

// Fetch admin user data safely using prepared statement
$admin_row = mlmp_pdo_fetch($pdo, "SELECT id, fname, country, tamount FROM affiliateuser WHERE username = ?", [$_SESSION['adminidusername']]);
$admin_name = $admin_row['fname'] ?? '';
$admin_country = $admin_row['country'] ?? '';
$admin_commission = (float)($admin_row['tamount'] ?? 0);

// Admin's Direct Referrals
$admin_direct_refs = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ?", [$_SESSION['adminidusername']]);

// Total Users Count (level 2)
$total_users = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2");

// Active Users Count (level 2)
$active_members_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 1");

// Inactive Users Count (level 2)
$inactive_members_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 0");

// Total Payments
$total_payments_row = mlmp_pdo_fetch($pdo, "
    SELECT SUM(p.price) as total_price
    FROM payu_payments p 
    JOIN affiliateuser u ON u.Id = p.orderid 
    WHERE u.level = 2 AND u.active = 1
");
$total_payments = $total_payments_row['total_price'] ?? 0;

// Fetch registration trends (last 15 days)
$registration_trends = mlmp_pdo_fetch_all($pdo, "
    SELECT DATE(doj) as reg_date, COUNT(*) as signup_count 
    FROM affiliateuser 
    WHERE level = 2 AND doj IS NOT NULL 
    GROUP BY DATE(doj) 
    ORDER BY reg_date ASC 
    LIMIT 15
");

// Fetch package distribution
$package_revenue_split = mlmp_pdo_fetch_all($pdo, "
    SELECT p.name, COUNT(u.Id) as member_count
    FROM affiliateuser u
    JOIN packages p ON u.pcktaken = p.id
    WHERE u.level = 2
    GROUP BY p.id, p.name
");

// Fetch recent online payment records (PayU/online)
$recent_payments = mlmp_pdo_fetch_all($pdo, "
    SELECT p.id, p.transacid, p.price, p.currency, p.date, u.username, pkg.name AS package_name
    FROM payu_payments p
    LEFT JOIN affiliateuser u ON u.Id = p.orderid
    LEFT JOIN packages pkg ON pkg.id = u.pcktaken
    WHERE p.gateway = 'PayU'
    ORDER BY p.id ASC
");

// Fetch Facebook/Twitter links safely
$settings_row = mlmp_pdo_fetch($pdo, "SELECT fblink, twitterlink FROM settings");
$fblink = $settings_row['fblink'] ?? '';
$twilink = $settings_row['twitterlink'] ?? '';

// Admin collection logic removed as requested by user
$page_title = 'Admin Dashboard';
$active_nav = 'dashboard';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include("layout_header.php");
?>
<?php if (!empty($admin_msg)) echo $admin_msg; ?>
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 text-indigo-700 text-sm">
    <p>Welcome back, <strong class="text-indigo-900"><?php echo mlmp_escape($admin_name); ?></strong>! Here is your real-time platform activity and system metrics overview.</p>
</div>

<!-- Personal Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
  <!-- Personal Commission -->
  <div class="bg-white border border-slate-200 border-l-4 border-l-purple-500 rounded-xl p-6 text-center shadow-sm relative overflow-hidden group hover:-translate-y-1 hover:shadow-md transition-all duration-300">
    <div class="absolute inset-0 bg-gradient-to-t from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-purple-600 mb-1 tracking-tight">INR <?php echo mlmp_escape(number_format($admin_commission, 2)); ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">My Commission Balance</div>
  </div>
  
  <!-- Personal Referrals -->
  <div class="bg-white border border-slate-200 border-l-4 border-l-blue-500 rounded-xl p-6 text-center shadow-sm relative overflow-hidden group hover:-translate-y-1 hover:shadow-md transition-all duration-300">
    <div class="absolute inset-0 bg-gradient-to-t from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-blue-600 mb-1 tracking-tight"><?php echo mlmp_escape($admin_direct_refs); ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">My Direct Referrals</div>
  </div>
</div>

<h4 class="text-lg font-bold text-slate-900 mb-4 tracking-tight">System Overview</h4>

<!-- Statistics Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <!-- Stat Card 1 -->
  <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:-translate-y-1 hover:border-indigo-300 transition-all duration-300">
    <div class="text-2xl font-extrabold text-indigo-600 mb-1"><?php echo mlmp_escape($total_users); ?></div>
    <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider">Total Members</div>
  </div>
  
  <!-- Stat Card 2 -->
  <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:-translate-y-1 hover:border-emerald-300 transition-all duration-300">
    <div class="text-2xl font-extrabold text-emerald-600 mb-1">INR <?php echo mlmp_escape(number_format((float)$total_payments, 2)); ?></div>
    <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider">Total Payments</div>
  </div>

  <!-- Stat Card 3 -->
  <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:-translate-y-1 hover:border-cyan-300 transition-all duration-300">
    <div class="text-2xl font-extrabold text-cyan-600 mb-1"><?php echo mlmp_escape($active_members_count); ?></div>
    <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider">Active Members</div>
  </div>

  <!-- Stat Card 4 -->
  <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-sm hover:-translate-y-1 hover:border-amber-300 transition-all duration-300">
    <div class="text-2xl font-extrabold text-amber-600 mb-1"><?php echo mlmp_escape($inactive_members_count); ?></div>
    <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider">Inactive Members</div>
  </div>
</div>

<!-- Visual Analytics Row 1 -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
  <!-- Line Chart: New User Trends -->
  <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 text-sm font-bold text-slate-900 flex items-center gap-2">
      <i class="fa-solid fa-chart-area text-indigo-500"></i> New User Registration Trends
    </div>
    <div class="p-4 flex-1 relative min-h-[300px]">
      <canvas id="regTrendsChart"></canvas>
    </div>
  </div>

  <!-- Pie Chart: Active/Inactive Split -->
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 text-sm font-bold text-slate-900 flex items-center gap-2">
      <i class="fa-solid fa-chart-pie text-emerald-500"></i> Member Distribution Status
    </div>
    <div class="p-4 flex-1 relative min-h-[300px]">
      <canvas id="activeStatusChart"></canvas>
    </div>
  </div>
</div>

<!-- Visual Analytics Row 2 -->
<div class="grid grid-cols-1 gap-5 mb-6">
  <!-- Doughnut Chart: Package Split -->
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 text-sm font-bold text-slate-900 flex items-center gap-2">
      <i class="fa-solid fa-chart-donut text-purple-500"></i> Member Distribution by Package
    </div>
    <div class="p-4 relative min-h-[320px]">
      <canvas id="packageSplitChart"></canvas>
    </div>
  </div>
</div>

<!-- Social Links Row -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex hover:-translate-y-1 transition-transform">
    <div class="w-24 bg-[#1877F2]/10 flex items-center justify-center shrink-0 border-r border-slate-100">
      <a href="<?php print mlmp_escape($fblink); ?>" target="_blank" class="w-full h-full flex items-center justify-center hover:scale-110 transition-transform"> 
        <i class="fa-brands fa-facebook fa-2x text-[#1877F2]"></i> 
      </a>
    </div>
    <div class="flex-1 flex p-0">
      <div class="flex-1 border-r border-slate-100 p-4 text-center flex flex-col justify-center">
        <div class="text-lg font-bold text-slate-900">Like</div>
        <div class="text-[11px] text-slate-600 uppercase tracking-wider">us on facebook</div> 
      </div>
      <div class="flex-1 p-4 text-center flex flex-col justify-center">
        <div class="text-lg font-bold text-slate-900">Right</div>
        <div class="text-[11px] text-slate-600 uppercase tracking-wider">now</div>
      </div>
    </div>
  </div>
  
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex hover:-translate-y-1 transition-transform">
    <div class="w-24 bg-[#1DA1F2]/10 flex items-center justify-center shrink-0 border-r border-slate-100">
      <a href="<?php print mlmp_escape($twilink); ?>" target="_blank" class="w-full h-full flex items-center justify-center hover:scale-110 transition-transform"> 
        <i class="fa-brands fa-twitter fa-2x text-[#1DA1F2]"></i> 
      </a>
    </div>
    <div class="flex-1 flex p-0">
      <div class="flex-1 border-r border-slate-100 p-4 text-center flex flex-col justify-center">
        <div class="text-lg font-bold text-slate-900">Follow</div>
        <div class="text-[11px] text-slate-600 uppercase tracking-wider">us on twitter</div> 
      </div>
      <div class="flex-1 p-4 text-center flex flex-col justify-center">
        <div class="text-lg font-bold text-slate-900">Right</div>
        <div class="text-[11px] text-slate-600 uppercase tracking-wider">now</div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Payment Records -->
<div class="mb-8">
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 text-sm font-bold text-slate-900 flex items-center gap-2">
      <i class="fa-solid fa-money-check-dollar text-green-500"></i> Recent Online Payment Records
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-600 border-b border-slate-200">
            <th class="px-5 py-3 font-semibold">Payment ID</th>
            <th class="px-5 py-3 font-semibold">Transaction ID</th>
            <th class="px-5 py-3 font-semibold">Member</th>
            <th class="px-5 py-3 font-semibold">Package</th>
            <th class="px-5 py-3 font-semibold text-right">Amount</th>
            <th class="px-5 py-3 font-semibold text-right">Date</th>
          </tr>
        </thead>
        <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
          <?php if (count($recent_payments) === 0): ?>
            <tr>
              <td colspan="6" class="px-5 py-8 text-center text-slate-600 text-sm">No online payments found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recent_payments as $rp): ?>
              <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-5 py-3 font-medium text-slate-900">#<?php echo mlmp_escape($rp['id'] ?? ''); ?></td>
                <td class="px-5 py-3"><code class="bg-slate-100 px-2 py-0.5 rounded text-xs text-indigo-600 font-mono"><?php echo mlmp_escape($rp['transacid'] ?? ''); ?></code></td>
                <td class="px-5 py-3 text-emerald-600 font-medium">@<?php echo mlmp_escape($rp['username'] ?? 'Unknown'); ?></td>
                <td class="px-5 py-3"><?php echo mlmp_escape($rp['package_name'] ?? 'N/A'); ?></td>
                <td class="px-5 py-3 text-right font-semibold text-slate-900"><?php echo mlmp_escape($rp['currency'] ?? ''); ?> <?php echo number_format((float)($rp['price'] ?? 0), 2); ?></td>
                <td class="px-5 py-3 text-right text-xs text-slate-400"><?php echo !empty($rp['date']) ? date('M d, Y H:i', strtotime($rp['date'])) : '-'; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
                
              
<!-- Render Dynamic Charts using Chart.js -->
<?php
// Prepare registration trends data for JavaScript safely
$reg_labels = [];
$reg_data = [];
foreach ($registration_trends as $trend) {
    $reg_labels[] = $trend['reg_date'];
    $reg_data[] = (int)$trend['signup_count'];
}
// Default if empty to prevent chart error
if (empty($reg_labels)) {
    $reg_labels = [date('Y-m-d')];
    $reg_data = [0];
}

// Prepare package split data
$pck_labels = [];
$pck_data = [];
foreach ($package_revenue_split as $split) {
    $pck_labels[] = $split['name'];
    $pck_data[] = (int)$split['member_count'];
}
if (empty($pck_labels)) {
    $pck_labels = ['No Active Packages'];
    $pck_data = [0];
}
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Global Chart.js Settings for Premium Look
    Chart.defaults.font.family = "'Inter', 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.scale.grid.color = 'rgba(0, 0, 0, 0.05)';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
    Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
    Chart.defaults.plugins.tooltip.bodyColor = '#e2e8f0';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 6;
    
    // Smooth Animations
    const animationConfig = {
        tension: {
            duration: 1000,
            easing: 'linear',
            from: 0.8,
            to: 0.4,
            loop: false
        }
    };

    // Chart 1: Registration Trends
    const ctxReg = document.getElementById('regTrendsChart').getContext('2d');
    const gradientReg = ctxReg.createLinearGradient(0, 0, 0, 300);
    gradientReg.addColorStop(0, 'rgba(79, 70, 229, 0.4)'); // Indigo 600
    gradientReg.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

    new Chart(ctxReg, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($reg_labels); ?>,
            datasets: [{
                label: 'Daily Signups',
                data: <?php echo json_encode($reg_data); ?>,
                borderColor: '#4f46e5', // Indigo 600
                borderWidth: 3,
                backgroundColor: gradientReg,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#4f46e5',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: animationConfig,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.04)',
                        drawTicks: false,
                    },
                    ticks: {
                        stepSize: 1,
                        padding: 10
                    }
                },
                x: {
                    border: { display: false },
                    grid: {
                        display: false
                    },
                    ticks: {
                        padding: 10
                    }
                }
            }
        }
    });

    // Chart 2: Active vs Inactive Pie Split
    const ctxActive = document.getElementById('activeStatusChart').getContext('2d');
    new Chart(ctxActive, {
        type: 'doughnut',
        data: {
            labels: ['Active Members', 'Inactive Members'],
            datasets: [{
                data: [
                    <?php echo (int)$active_members_count; ?>,
                    <?php echo (int)$inactive_members_count; ?>
                ],
                backgroundColor: [
                    '#10b981', // Emerald 500
                    '#f43f5e'  // Rose 500
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12, weight: '500' }
                    }
                }
            }
        }
    });

    // Chart 3: Package Distribution Doughnut
    const ctxPck = document.getElementById('packageSplitChart').getContext('2d');
    new Chart(ctxPck, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pck_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($pck_data); ?>,
                backgroundColor: [
                    '#6366f1', // Indigo 500
                    '#8b5cf6', // Violet 500
                    '#ec4899', // Pink 500
                    '#14b8a6', // Teal 500
                    '#f59e0b', // Amber 500
                    '#3b82f6'  // Blue 500
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12, weight: '500' }
                    }
                }
            },
            layout: {
                padding: {
                    left: 20,
                    right: 20
                }
            }
        }
    });
});
</script>
<?php include("layout_footer.php"); ?>


