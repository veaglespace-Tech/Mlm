<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$page_title = "Payment History";
$active_nav = "payments";
include 'layout_header.php';
ensure_payout_schema($pdo);

// Fetch user ID
$uid_row = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$uid = $uid_row['Id'] ?? 0;

// --- Pagination ---
$per_page = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_payments = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE userid = ?", [$uid]);
$total_pages = max(1, (int)ceil($total_payments / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated payments
$payments = mlmp_pdo_fetch_all($pdo, "SELECT payment_amount, payment_status, createdtime FROM payments WHERE userid = ? ORDER BY createdtime DESC LIMIT $per_page OFFSET $offset", [$uid]);
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
            $is_rejected = ($p['payment_status'] == 2);
          ?>
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-5 py-3.5 text-slate-600 font-medium"><?php echo $i + 1; ?></td>
            <td class="px-5 py-3.5 text-slate-700"><?php echo mlmp_escape($p['createdtime']); ?></td>
            <td class="px-5 py-3.5 font-semibold text-emerald-600">INR <?php echo mlmp_escape($p['payment_amount']); ?></td>
            <td class="px-5 py-3.5">
              <?php if ($is_done): ?>
                <span class="text-emerald-600 font-semibold bg-emerald-500/10 px-2.5 py-1 rounded-md text-xs border border-emerald-500/20"><i class="fa-solid fa-check-circle mr-1"></i> Completed</span>
              <?php elseif ($is_rejected): ?>
                <span class="text-red-600 font-semibold bg-red-500/10 px-2.5 py-1 rounded-md text-xs border border-red-500/20"><i class="fa-solid fa-circle-xmark mr-1"></i> Rejected</span>
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

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-1">
    <div class="text-sm text-slate-500 font-medium">
        Showing page <strong class="text-slate-700"><?php echo $current_page; ?></strong> of <strong class="text-slate-700"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_payments; ?> total records
    </div>
    <div class="flex items-center gap-1.5">
        <?php
        $base_url = '?page=';
        if ($current_page > 1): ?>
            <a href="<?php echo $base_url . ($current_page - 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                <i class="fa-solid fa-chevron-left text-xs"></i> Prev
            </a>
        <?php endif;
        $range = 2;
        $start = max(1, $current_page - $range);
        $end   = min($total_pages, $current_page + $range);
        if ($start > 1): ?>
            <a href="<?php echo $base_url . 1; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">1</a>
            <?php if ($start > 2): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif;
        endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?php echo $base_url . $p; ?>" class="px-3 py-2 rounded-lg border text-sm font-semibold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700'; ?>"><?php echo $p; ?></a>
        <?php endfor;
        if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif; ?>
            <a href="<?php echo $base_url . $total_pages; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm"><?php echo $total_pages; ?></a>
        <?php endif;
        if ($current_page < $total_pages): ?>
            <a href="<?php echo $base_url . ($current_page + 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                Next <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'layout_footer.php'; ?>
