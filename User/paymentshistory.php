<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$page_title = "Payment History";
$active_nav = "payments";
include 'layout_header.php';

// Fetch user ID
$uid_row = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$uid = $uid_row['Id'] ?? 0;

// Fetch payments
$payments = mlmp_pdo_fetch_all($pdo, "SELECT payment_amount, payment_status, createdtime FROM payments WHERE userid = ? ORDER BY createdtime DESC", [$uid]);
?>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col mb-6">
  <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-4">
    <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left"></i></span>
      Payment History
    </div>
    <div class="flex items-center gap-4 text-xs font-semibold">
      <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_currentColor]"></span> Completed</span>
      <span class="flex items-center gap-1.5 text-amber-500"><span class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_currentColor]"></span> Pending</span>
    </div>
  </div>
  <div class="p-0 overflow-x-auto rounded-b-2xl">
    <?php if (empty($payments)): ?>
      <div class="text-center p-10">
        <i class="fa-solid fa-receipt text-5xl text-slate-700 mb-4 block"></i>
        <p class="text-slate-600 text-sm font-medium">No payment records found.</p>
      </div>
    <?php else: ?>
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-600 border-b border-slate-200">
            <th class="px-5 py-3 font-semibold">#</th>
            <th class="px-5 py-3 font-semibold">Date &amp; Time</th>
            <th class="px-5 py-3 font-semibold">Amount</th>
            <th class="px-5 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody class="text-sm text-slate-700 divide-y divide-white/5">
          <?php foreach ($payments as $i => $p):
            $is_done = ($p['payment_status'] == 1);
          ?>
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-5 py-3.5 text-slate-600 font-medium"><?php echo $i + 1; ?></td>
            <td class="px-5 py-3.5 text-slate-700"><?php echo mlmp_escape($p['createdtime']); ?></td>
            <td class="px-5 py-3.5 font-semibold text-emerald-600">INR <?php echo mlmp_escape($p['payment_amount']); ?></td>
            <td class="px-5 py-3.5">
              <?php if ($is_done): ?>
                <span class="text-emerald-600 font-semibold bg-emerald-500/10 px-2.5 py-1 rounded-md text-xs border border-emerald-500/20"><i class="fa-solid fa-check-circle mr-1"></i> Completed</span>
              <?php else: ?>
                <span class="text-amber-500 font-semibold bg-amber-500/10 px-2.5 py-1 rounded-md text-xs border border-amber-500/20"><i class="fa-solid fa-clock mr-1"></i> Pending</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include 'layout_footer.php'; ?>
