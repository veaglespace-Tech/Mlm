<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$my_username = $_SESSION['username'];

// --- Pagination, Search & Sorting ---
$per_page     = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');
$filter       = $_GET['filter'] ?? 'all'; // all | active | inactive
$sort         = $_GET['sort'] ?? 'Id';
$order        = strtolower($_GET['order'] ?? 'desc');
if (!in_array($order, ['asc', 'desc'])) $order = 'desc';

$sort_columns = [
    'Id'       => 'u.Id',
    'fname'    => 'u.fname',
    'username' => 'u.username',
    'email'    => 'u.email',
    'pkg_name' => 'pkg_name',
    'doj'      => 'u.doj',
    'active'   => 'u.active',
];
$sort_sql = $sort_columns[$sort] ?? 'u.Id';

$where_parts = ["referedby = ?"];
$params      = [$my_username];

if ($search !== '') {
    $where_parts[] = "(u.username LIKE ? OR u.fname LIKE ? OR u.email LIKE ? OR u.country LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filter === 'active') {
    $where_parts[] = "u.active = 1";
} elseif ($filter === 'inactive') {
    $where_parts[] = "u.active = 0";
}

$where_sql = implode(' AND ', $where_parts);

$total_refs   = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser u WHERE $where_sql", $params);
$total_pages  = max(1, (int)ceil($total_refs / $per_page));
$current_page = min($current_page, $total_pages);
$offset       = ($current_page - 1) * $per_page;

$referrals = mlmp_pdo_fetch_all($pdo,
    "SELECT u.username, u.fname, u.email, u.country, u.doj, u.active, u.tamount, u.left_count, u.right_count, p.name AS pkg_name
     FROM affiliateuser u
     LEFT JOIN packages p ON p.id = u.pcktaken
     WHERE $where_sql
     ORDER BY $sort_sql $order
     LIMIT $per_page OFFSET $offset",
    $params
);

// Summary stats (no search filter)
$total_all      = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ?", [$my_username]);
$total_active   = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ? AND active = 1", [$my_username]);
$total_inactive = $total_all - $total_active;

// My currency
$pkg_row = mlmp_pdo_fetch($pdo, "SELECT pcktaken FROM affiliateuser WHERE username = ?", [$my_username]);
$pckcur = '';
if (!empty($pkg_row['pcktaken'])) {
    $pkg = mlmp_pdo_fetch($pdo, "SELECT currency FROM packages WHERE id = ?", [(int)$pkg_row['pcktaken']]);
    $pckcur = $pkg['currency'] ?? '';
}
$pckcur = $pckcur ?: '₹';

function sort_th_user_refs($key, $label, $cur_sort, $cur_order, $align = 'left') {
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

function build_user_refs_query($override = []) {
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
    if ($merged['sort'] === 'Id') unset($merged['sort']);
    if ($merged['order'] === 'desc') unset($merged['order']);
    return http_build_query($merged);
}

$page_title = "My Direct Referrals";
$active_nav = "referrals";
include_once("layout_header.php");
?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-slate-200 border-l-4 border-l-indigo-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-indigo-600 mb-1"><?php echo $total_all; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Total Direct Referrals</div>
    <div class="text-xs text-slate-400 mt-1">People who joined using your link</div>
  </div>
  <div class="bg-white border border-slate-200 border-l-4 border-l-emerald-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-emerald-600 mb-1"><?php echo $total_active; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Paid &amp; Active</div>
    <div class="text-xs text-slate-400 mt-1">Completed their payment</div>
  </div>
  <div class="bg-white border border-slate-200 border-l-4 border-l-amber-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-amber-600 mb-1"><?php echo $total_inactive; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Pending / Inactive</div>
    <div class="text-xs text-slate-400 mt-1">Yet to complete payment</div>
  </div>
</div>

<!-- Info Banner -->
<?php if ($total_all === 0 && $search === ''): ?>
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5 mb-5 flex items-center gap-4">
  <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl shrink-0">
    <i class="fa-solid fa-link"></i>
  </div>
  <div>
    <div class="font-bold text-indigo-900 text-sm mb-1">No referrals yet!</div>
    <div class="text-sm text-indigo-700">Share your referral link from your dashboard to earn direct referral bonuses when someone joins.</div>
  </div>
</div>
<?php endif; ?>

<!-- Search & Filter Bar -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm px-5 py-4 mb-5 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
  <form id="userRefsForm" method="GET" action="my_referrals.php" class="flex gap-2 flex-1 flex-wrap items-center">
    <input type="hidden" name="page" value="1">
    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
    <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">
    
    <div class="relative flex-1 min-w-[200px]">
      <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
      <input type="text" id="userRefsSearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>"
        placeholder="Search by name, username, email, country..."
        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-8 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all">
      <?php if ($search !== ''): ?>
        <a href="?<?php echo build_user_refs_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1" title="Clear Search">
          <i class="fa-solid fa-circle-xmark"></i>
        </a>
      <?php endif; ?>
    </div>

    <select name="filter" onchange="document.getElementById('userRefsForm').submit()" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-100 transition-all cursor-pointer">
      <option value="all"      <?php echo $filter === 'all'      ? 'selected' : ''; ?>>All Status</option>
      <option value="active"   <?php echo $filter === 'active'   ? 'selected' : ''; ?>>Active Only</option>
      <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
    </select>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap">
      <i class="fa-solid fa-search text-xs"></i> Search
    </button>
    <?php if ($search !== '' || $filter !== 'all' || $sort !== 'Id'): ?>
      <a href="my_referrals.php" class="text-sm text-slate-500 hover:text-slate-800 px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors whitespace-nowrap">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>
  <div class="text-xs text-slate-500 font-semibold whitespace-nowrap self-end sm:self-center">
    <?php echo $total_refs; ?> result<?php echo $total_refs !== 1 ? 's' : ''; ?>
  </div>
</div>

<!-- Referrals Table -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50">
    <div class="flex items-center gap-2">
      <i class="fa-solid fa-user-group text-indigo-500"></i>
      <span class="text-sm font-bold text-slate-900">People Who Joined Through My Referral Link</span>
    </div>
    <span class="text-xs text-slate-400">Click column header to sort</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <th class="px-5 py-3 font-semibold">#</th>
          <?php echo sort_th_user_refs('fname', 'Member', $sort, $order); ?>
          <?php echo sort_th_user_refs('email', 'Email', $sort, $order); ?>
          <th class="px-5 py-3 font-semibold">Country</th>
          <?php echo sort_th_user_refs('pkg_name', 'Package', $sort, $order); ?>
          <?php echo sort_th_user_refs('doj', 'Joined On', $sort, $order); ?>
          <th class="px-5 py-3 font-semibold text-center">Their Team (L/R)</th>
          <?php echo sort_th_user_refs('active', 'Status', $sort, $order, 'center'); ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
        <?php if (empty($referrals)): ?>
          <tr>
            <td colspan="8" class="px-5 py-10 text-center text-slate-500">
              <i class="fa-solid fa-users-slash text-3xl text-slate-300 block mb-3"></i>
              <?php echo $search !== '' ? 'No results match your search.' : 'No direct referrals yet.'; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php $row_num = $offset + 1; foreach ($referrals as $ref): ?>
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3 text-slate-400 font-medium text-xs"><?php echo $row_num++; ?></td>
              <td class="px-5 py-3">
                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($ref['fname']); ?></div>
                <div class="text-xs text-indigo-600 font-mono mt-0.5">@<?php echo htmlspecialchars($ref['username']); ?></div>
              </td>
              <td class="px-5 py-3 text-slate-500 text-xs"><?php echo htmlspecialchars($ref['email']); ?></td>
              <td class="px-5 py-3">
                <?php if (!empty($ref['country'])): ?>
                  <span class="text-xs bg-slate-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($ref['country']); ?></span>
                <?php else: ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3">
                <?php if (!empty($ref['pkg_name'])): ?>
                  <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded"><?php echo htmlspecialchars($ref['pkg_name']); ?></span>
                <?php else: ?>
                  <span class="text-slate-400 text-xs">No Package</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap"><?php echo !empty($ref['doj']) ? date('d M Y', strtotime($ref['doj'])) : '—'; ?></td>
              <td class="px-5 py-3 text-center whitespace-nowrap">
                <span class="text-xs font-bold text-blue-600">L<?php echo (int)$ref['left_count']; ?></span>
                <span class="text-slate-300 mx-1">/</span>
                <span class="text-xs font-bold text-purple-600">R<?php echo (int)$ref['right_count']; ?></span>
              </td>
              <td class="px-5 py-3 text-center whitespace-nowrap">
                <?php if ($ref['active'] == 1): ?>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-circle-check text-[9px]"></i> Active
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                    <i class="fa-solid fa-clock text-[9px]"></i> Pending
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
  <!-- Pagination -->
  <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50">
    <div class="text-xs text-slate-500">
      Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> &mdash; <?php echo $total_refs; ?> total records
    </div>
    <div class="flex gap-1 flex-wrap justify-center">
      <?php if ($current_page > 1): ?>
        <a href="?<?php echo build_user_refs_query(['page' => $current_page - 1]); ?>"
           class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all">&larr; Prev</a>
      <?php endif;
      $range = 2;
      for ($p = 1; $p <= $total_pages; $p++):
        if ($p == 1 || $p == $total_pages || abs($p - $current_page) <= $range):
          if ($p == $current_page): ?>
            <span class="px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white border border-indigo-700"><?php echo $p; ?></span>
          <?php else: ?>
            <a href="?<?php echo build_user_refs_query(['page' => $p]); ?>"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all"><?php echo $p; ?></a>
          <?php endif;
        elseif (abs($p - $current_page) == $range + 1): ?>
          <span class="px-2 py-1.5 text-xs text-slate-400">&hellip;</span>
        <?php endif;
      endfor;
      if ($current_page < $total_pages): ?>
        <a href="?<?php echo build_user_refs_query(['page' => $current_page + 1]); ?>"
           class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all">Next &rarr;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    initTableSearchDebounce('userRefsSearchInput', 'userRefsForm', 450);
});
</script>

<?php include_once("layout_footer.php"); ?>
