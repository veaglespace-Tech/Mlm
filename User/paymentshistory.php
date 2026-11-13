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

// Search, Filter & Sorting inputs
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all | completed | pending | rejected
$sort   = $_GET['sort'] ?? 'createdtime';
$order  = strtolower($_GET['order'] ?? 'desc');
if (!in_array($order, ['asc', 'desc'])) $order = 'desc';

$sort_columns = [
    'id'             => 'id',
    'createdtime'    => 'createdtime',
    'payment_amount' => 'payment_amount',
    'payment_status' => 'payment_status',
];
$sort_sql = $sort_columns[$sort] ?? 'createdtime';

$where_clauses = ["userid = ?"];
$params = [$uid];

if ($search !== '') {
    $where_clauses[] = "(payment_amount LIKE ? OR createdtime LIKE ? OR id = ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = is_numeric($search) ? (int)$search : 0;
}

if ($filter === 'completed') {
    $where_clauses[] = "payment_status = 1";
} elseif ($filter === 'pending') {
    $where_clauses[] = "payment_status = 0";
} elseif ($filter === 'rejected') {
    $where_clauses[] = "payment_status = 2";
}

$where_sql = implode(' AND ', $where_clauses);

// --- Pagination ---
$per_page = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_payments = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE $where_sql", $params);
$total_pages = max(1, (int)ceil($total_payments / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated payments
$payments = mlmp_pdo_fetch_all($pdo, "
    SELECT id, payment_amount, payment_status, createdtime 
    FROM payments 
    WHERE $where_sql 
    ORDER BY $sort_sql $order 
    LIMIT $per_page OFFSET $offset
", $params);

function sort_th_payhist($key, $label, $cur_sort, $cur_order, $align = 'left') {
    $is_active = ($cur_sort === $key);
    $next_order = ($is_active && $cur_order === 'asc') ? 'desc' : 'asc';
    $icon = '<i class="fa-solid fa-sort text-slate-300 ml-1 text-[11px]"></i>';
    if ($is_active) {
        $icon = ($cur_order === 'asc') 
            ? '<i class="fa-solid fa-sort-up text-indigo-600 ml-1 text-[11px]"></i>' 
            : '<i class="fa-solid fa-sort-down text-indigo-600 ml-1 text-[11px]"></i>';
    }
    $align_cls = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
    return "<th class=\"px-5 py-3 font-semibold {$align_cls} cursor-pointer select-none hover:text-indigo-600 transition-colors\" onclick=\"applyTableSort('{$key}', '{$next_order}')\">
        <span>{$label}</span> {$icon}
    </th>";
}

function build_payhist_query($override = []) {
    global $search, $filter, $sort, $order, $current_page;
    $p = [
        'search' => $search,
        'filter' => $filter,
        'sort'   => $sort,
        'order'  => $order,
        'page'   => $current_page
    ];
    $merged = array_merge($p, $override);
    if (empty($merged['search'])) unset($merged['search']);
    if ($merged['filter'] === 'all') unset($merged['filter']);
    if ($merged['sort'] === 'createdtime') unset($merged['sort']);
    if ($merged['order'] === 'desc') unset($merged['order']);
    return http_build_query($merged);
}
?>

<!-- Search, Filter & Toolbar -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
    <form id="payHistorySearchForm" method="GET" action="paymentshistory.php" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">

        <!-- Debounced Search Box -->
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="payHistorySearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search by amount, date or ID..." 
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-8 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            <?php if (!empty($search)): ?>
                <a href="?<?php echo build_payhist_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1" title="Clear Search">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter Dropdown -->
        <div class="flex items-center gap-2">
            <select name="filter" onchange="document.getElementById('payHistorySearchForm').submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs md:text-sm text-slate-700 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white transition-all cursor-pointer">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed Only</option>
                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected Only</option>
            </select>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs md:text-sm font-semibold px-4 py-2 rounded-xl transition-all shadow-sm shadow-indigo-600/20 whitespace-nowrap">
                <i class="fa-solid fa-search mr-1"></i> Search
            </button>

            <?php if ($search !== '' || $filter !== 'all' || $sort !== 'createdtime'): ?>
                <a href="paymentshistory.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs md:text-sm font-semibold px-3 py-2 rounded-xl transition-all whitespace-nowrap" title="Reset all filters">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="text-xs text-slate-500 font-semibold whitespace-nowrap flex items-center gap-1.5 self-end md:self-center">
        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
        <?php echo $total_payments; ?> record<?php echo $total_payments !== 1 ? 's' : ''; ?>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col mb-6">
  <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-4 bg-slate-50">
    <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left"></i></span>
      Payment History Logs
    </div>
    <div class="flex items-center gap-4 text-xs font-semibold">
      <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_currentColor]"></span> Completed</span>
      <span class="flex items-center gap-1.5 text-amber-500"><span class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_currentColor]"></span> Pending</span>
      <span class="flex items-center gap-1.5 text-red-500"><span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_currentColor]"></span> Rejected</span>
    </div>
  </div>
  <div class="p-0 overflow-x-auto rounded-b-2xl">
    <?php if (empty($payments)): ?>
      <div class="text-center p-10">
        <i class="fa-solid fa-receipt text-5xl text-slate-300 mb-4 block"></i>
        <p class="text-slate-600 text-sm font-medium"><?php echo $search !== '' ? 'No payment records match your search criteria.' : 'No payment records found.'; ?></p>
      </div>
    <?php else: ?>
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-600 border-b border-slate-200">
            <th class="px-5 py-3 font-semibold">#</th>
            <?php echo sort_th_payhist('createdtime', 'Date & Time', $sort, $order); ?>
            <?php echo sort_th_payhist('payment_amount', 'Amount', $sort, $order); ?>
            <?php echo sort_th_payhist('payment_status', 'Status', $sort, $order); ?>
          </tr>
        </thead>
        <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
          <?php foreach ($payments as $i => $p):
            $is_done = ($p['payment_status'] == 1);
            $is_rejected = ($p['payment_status'] == 2);
          ?>
          <tr class="hover:bg-slate-50 transition-colors">
            <td class="px-5 py-3.5 text-slate-400 font-medium"><?php echo $offset + $i + 1; ?></td>
            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap"><?php echo mlmp_escape($p['createdtime']); ?></td>
            <td class="px-5 py-3.5 font-semibold text-emerald-600 whitespace-nowrap">₹ <?php echo number_format((float)$p['payment_amount'], 2); ?></td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <?php if ($is_done): ?>
                <span class="text-emerald-600 font-semibold bg-emerald-50 px-2.5 py-1 rounded-md text-xs border border-emerald-200"><i class="fa-solid fa-check-circle mr-1"></i> Completed</span>
              <?php elseif ($is_rejected): ?>
                <span class="text-red-600 font-semibold bg-red-50 px-2.5 py-1 rounded-md text-xs border border-red-200"><i class="fa-solid fa-circle-xmark mr-1"></i> Rejected</span>
              <?php else: ?>
                <span class="text-amber-600 font-semibold bg-amber-50 px-2.5 py-1 rounded-md text-xs border border-amber-200"><i class="fa-solid fa-clock mr-1"></i> Pending</span>
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
    <div class="flex items-center gap-1.5 flex-wrap justify-center">
        <?php if ($current_page > 1): ?>
            <a href="?<?php echo build_payhist_query(['page' => $current_page - 1]); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                <i class="fa-solid fa-chevron-left text-xs"></i> Prev
            </a>
        <?php endif;
        $range = 2;
        $start = max(1, $current_page - $range);
        $end   = min($total_pages, $current_page + $range);
        if ($start > 1): ?>
            <a href="?<?php echo build_payhist_query(['page' => 1]); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">1</a>
            <?php if ($start > 2): ?><span class="px-1 text-slate-400 text-sm">&hellip;</span><?php endif;
        endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="?<?php echo build_payhist_query(['page' => $p]); ?>" class="px-3 py-2 rounded-lg border text-sm font-semibold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700'; ?>"><?php echo $p; ?></a>
        <?php endfor;
        if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400 text-sm">&hellip;</span><?php endif; ?>
            <a href="?<?php echo build_payhist_query(['page' => $total_pages]); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm"><?php echo $total_pages; ?></a>
        <?php endif;
        if ($current_page < $total_pages): ?>
            <a href="?<?php echo build_payhist_query(['page' => $current_page + 1]); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                Next <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    initTableSearchDebounce('payHistorySearchInput', 'payHistorySearchForm', 450);
});
</script>

<?php include 'layout_footer.php'; ?>
