<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$admin_username = $_SESSION['adminidusername'];

// --- Pagination & Search ---
$per_page    = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$search      = trim($_GET['search'] ?? '');
$filter      = $_GET['filter'] ?? 'all'; // all | active | inactive

$where_parts = ["referedby = ?"];
$params      = [$admin_username];

if ($search !== '') {
    $where_parts[] = "(username LIKE ? OR fname LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filter === 'active') {
    $where_parts[] = "active = 1";
} elseif ($filter === 'inactive') {
    $where_parts[] = "active = 0";
}

$where_sql = implode(' AND ', $where_parts);

$total_refs   = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE $where_sql", $params);
$total_pages  = max(1, (int)ceil($total_refs / $per_page));
$current_page = min($current_page, $total_pages);
$offset       = ($current_page - 1) * $per_page;

$referrals = mlmp_pdo_fetch_all($pdo,
    "SELECT u.username, u.fname, u.email, u.country, u.doj, u.active, u.tamount, u.left_count, u.right_count, p.name AS pkg_name
     FROM affiliateuser u
     LEFT JOIN packages p ON p.id = u.pcktaken
     WHERE $where_sql
     ORDER BY u.Id DESC
     LIMIT $per_page OFFSET $offset",
    $params
);

// Summary stats (no search filter for totals)
$total_all     = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ?", [$admin_username]);
$total_active  = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE referedby = ? AND active = 1", [$admin_username]);
$total_inactive= $total_all - $total_active;
$total_earned  = mlmp_pdo_fetch($pdo, "SELECT SUM(tamount) as tot FROM affiliateuser WHERE referedby = ?", [$admin_username]);
$total_earned_val = (float)($total_earned['tot'] ?? 0);

$page_title = 'My Direct Referrals';
$active_nav = 'my_referrals';
include("layout_header.php");
?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-slate-200 border-l-4 border-l-indigo-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-indigo-600 mb-1"><?php echo $total_all; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Total Direct Referrals</div>
  </div>
  <div class="bg-white border border-slate-200 border-l-4 border-l-emerald-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-emerald-600 mb-1"><?php echo $total_active; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Active Members</div>
  </div>
  <div class="bg-white border border-slate-200 border-l-4 border-l-amber-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-amber-600 mb-1"><?php echo $total_inactive; ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Pending / Inactive</div>
  </div>
  <div class="bg-white border border-slate-200 border-l-4 border-l-purple-500 rounded-xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-t from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <div class="text-3xl font-extrabold text-purple-600 mb-1">INR <?php echo number_format($total_earned_val, 2); ?></div>
    <div class="text-xs text-slate-600 font-semibold uppercase tracking-wider">Total Earnings of My Refs</div>
  </div>
</div>

<!-- Search & Filter Bar -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm px-5 py-4 mb-5 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
  <form method="GET" action="my_referrals.php" class="flex gap-2 flex-1 flex-wrap items-center">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search by name, username, email..."
      class="flex-1 min-w-[200px] bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all">
    <select name="filter" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-100 transition-all">
      <option value="all"      <?php echo $filter === 'all'      ? 'selected' : ''; ?>>All</option>
      <option value="active"   <?php echo $filter === 'active'   ? 'selected' : ''; ?>>Active Only</option>
      <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
    </select>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
      <i class="fa-solid fa-search text-xs"></i> Search
    </button>
    <?php if ($search !== '' || $filter !== 'all'): ?>
      <a href="my_referrals.php" class="text-sm text-slate-500 hover:text-slate-800 px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>
  <div class="text-xs text-slate-500 font-medium whitespace-nowrap">
    Showing <?php echo $total_refs; ?> result<?php echo $total_refs !== 1 ? 's' : ''; ?>
  </div>
</div>

<!-- Table -->
<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2 bg-slate-50">
    <i class="fa-solid fa-user-group text-indigo-500"></i>
    <span class="text-sm font-bold text-slate-900">People Who Joined Through Me (Admin)</span>
    <?php if ($search !== '' || $filter !== 'all'): ?>
      <span class="ml-auto text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full font-semibold">Filtered</span>
    <?php endif; ?>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
          <th class="px-5 py-3 font-semibold">#</th>
          <th class="px-5 py-3 font-semibold">Member</th>
          <th class="px-5 py-3 font-semibold">Email</th>
          <th class="px-5 py-3 font-semibold">Country</th>
          <th class="px-5 py-3 font-semibold">Package</th>
          <th class="px-5 py-3 font-semibold">Joined On</th>
          <th class="px-5 py-3 font-semibold text-center">Binary (L/R)</th>
          <th class="px-5 py-3 font-semibold text-right">Earnings</th>
          <th class="px-5 py-3 font-semibold text-center">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
        <?php if (empty($referrals)): ?>
          <tr>
            <td colspan="9" class="px-5 py-10 text-center text-slate-500">
              <i class="fa-solid fa-users-slash text-3xl text-slate-300 block mb-3"></i>
              <?php echo $search !== '' ? 'No results found for your search.' : 'No direct referrals yet.'; ?>
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
              <td class="px-5 py-3 text-slate-600 text-xs"><?php echo htmlspecialchars($ref['email']); ?></td>
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
              <td class="px-5 py-3 text-xs text-slate-500"><?php echo !empty($ref['doj']) ? date('d M Y', strtotime($ref['doj'])) : '—'; ?></td>
              <td class="px-5 py-3 text-center">
                <span class="text-xs font-bold text-blue-600">L<?php echo (int)$ref['left_count']; ?></span>
                <span class="text-slate-300 mx-1">/</span>
                <span class="text-xs font-bold text-purple-600">R<?php echo (int)$ref['right_count']; ?></span>
              </td>
              <td class="px-5 py-3 text-right font-semibold text-slate-800 text-sm">INR <?php echo number_format((float)$ref['tamount'], 2); ?></td>
              <td class="px-5 py-3 text-center">
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
      Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> — <?php echo $total_refs; ?> total records
    </div>
    <div class="flex gap-1 flex-wrap justify-center">
      <?php
      $q_base = http_build_query(array_filter(['search' => $search, 'filter' => $filter !== 'all' ? $filter : '']));
      $q_sep  = $q_base ? '&' : '';

      // Prev
      if ($current_page > 1): ?>
        <a href="?<?php echo $q_base . $q_sep; ?>page=<?php echo $current_page - 1; ?>"
           class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all">← Prev</a>
      <?php endif;

      // Page numbers with ellipsis
      $range = 2;
      for ($p = 1; $p <= $total_pages; $p++):
        if ($p == 1 || $p == $total_pages || abs($p - $current_page) <= $range):
          if ($p == $current_page): ?>
            <span class="px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 text-white border border-indigo-700"><?php echo $p; ?></span>
          <?php else: ?>
            <a href="?<?php echo $q_base . $q_sep; ?>page=<?php echo $p; ?>"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all"><?php echo $p; ?></a>
          <?php endif;
        elseif (abs($p - $current_page) == $range + 1): ?>
          <span class="px-2 py-1.5 text-xs text-slate-400">…</span>
        <?php endif;
      endfor;

      // Next
      if ($current_page < $total_pages): ?>
        <a href="?<?php echo $q_base . $q_sep; ?>page=<?php echo $current_page + 1; ?>"
           class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all">Next →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include("layout_footer.php"); ?>
