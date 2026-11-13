<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$page_title = "Notifications";
$active_nav = "notifications";
include 'layout_header.php';

// Search, Sort & Pagination
$search     = trim($_GET['search'] ?? '');
$sort_order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$where_clauses = ["valid = 1"];
$params = [];
if ($search !== '') {
    $where_clauses[] = "(subject LIKE ? OR body LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$count_sql  = "SELECT COUNT(*) FROM notifications $where_sql";
$total_rows = mlmp_pdo_count($pdo, $count_sql, $params);
$per_page   = 10;
$total_pages= max(1, ceil($total_rows / $per_page));
$page       = max(1, min((int)($_GET['page'] ?? 1), $total_pages));
$offset     = ($page - 1) * $per_page;

$notifs = mlmp_pdo_fetch_all($pdo, "
    SELECT id, subject, body, posteddate 
    FROM notifications 
    $where_sql 
    ORDER BY id $sort_order 
    LIMIT $per_page OFFSET $offset
", $params);

function build_user_notif_query($overrides = []) {
    $params = array_merge([
        'search' => $_GET['search'] ?? '',
        'order'  => $_GET['order'] ?? 'desc',
        'page'   => 1
    ], $overrides);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
}
?>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl flex flex-col mb-6">
  <!-- Top Header with Actions -->
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
    <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm"><i class="fa-solid fa-bell"></i></span>
      <div>
        <h3 class="text-sm font-bold text-slate-900 leading-tight">Notifications &amp; Announcements</h3>
        <span class="text-[11px] text-slate-400 font-medium"><?php echo $total_rows; ?> active messages</span>
      </div>
    </div>

    <!-- Search and Sort Toolbar -->
    <div class="flex items-center gap-3 flex-wrap">
      <!-- Search Form with Debouncing -->
      <form method="GET" action="notifications.php" id="userNotifSearchForm" class="flex items-center">
        <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
        <div class="relative min-w-[220px]">
          <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
          <input type="text" id="userNotifSearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search subject or body..." class="w-full pl-8 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
          <?php if ($search !== ''): ?>
            <a href="<?php echo build_user_notif_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs" title="Clear search"><i class="fa-solid fa-times-circle"></i></a>
          <?php endif; ?>
        </div>
      </form>

      <!-- Sort Toggle (Newest / Oldest) -->
      <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-xs">
        <a href="<?php echo build_user_notif_query(['order' => 'desc', 'page' => 1]); ?>" class="px-2.5 py-1 rounded-lg font-semibold transition-colors <?php echo $sort_order === 'DESC' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
          <i class="fa-solid fa-arrow-down-wide-short mr-1"></i> Newest
        </a>
        <a href="<?php echo build_user_notif_query(['order' => 'asc', 'page' => 1]); ?>" class="px-2.5 py-1 rounded-lg font-semibold transition-colors <?php echo $sort_order === 'ASC' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>">
          <i class="fa-solid fa-arrow-up-wide-short mr-1"></i> Oldest
        </a>
      </div>
    </div>
  </div>

  <!-- Notifications List -->
  <div class="p-6">
    <?php if (empty($notifs)): ?>
      <div class="text-center py-12">
        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <i class="fa-solid fa-bell-slash text-2xl text-slate-300"></i>
        </div>
        <p class="text-slate-600 text-sm font-semibold">No notifications found.</p>
        <p class="text-slate-400 text-xs mt-1"><?php echo $search !== '' ? 'Try adjusting your search criteria.' : 'Check back later for updates from administration.'; ?></p>
        <?php if ($search !== ''): ?>
          <a href="notifications.php" class="inline-block mt-3 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition-colors">Clear Search</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($notifs as $n): ?>
          <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-5 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
            <div class="flex justify-between items-start flex-wrap gap-2 mb-3">
              <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs shrink-0"><i class="fa-solid fa-bullhorn"></i></span>
                <?php echo mlmp_escape($n['subject']); ?>
              </div>
              <span class="text-[11px] text-slate-500 bg-white px-2.5 py-1 rounded-full border border-slate-200 font-medium shrink-0">
                <i class="fa-regular fa-clock mr-1 text-slate-400"></i><?php echo mlmp_escape($n['posteddate']); ?>
              </span>
            </div>
            <div class="text-sm text-slate-600 leading-relaxed mb-3 whitespace-pre-line pl-8">
              <?php echo nl2br(mlmp_escape($n['body'])); ?>
            </div>
            <div class="text-[11px] text-slate-400 font-medium pl-8 flex items-center gap-2">
              <span><i class="fa-solid fa-user-shield text-slate-400 mr-1"></i>Official Announcement</span>
              <span>&bull;</span>
              <span>Msg #<?php echo (int)$n['id']; ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
    <div class="text-xs text-slate-500">
      Showing <span class="font-bold text-slate-800"><?php echo $total_rows > 0 ? $offset + 1 : 0; ?></span> to <span class="font-bold text-slate-800"><?php echo min($total_rows, $offset + $per_page); ?></span> of <span class="font-bold text-slate-800"><?php echo $total_rows; ?></span> notifications
    </div>
    <div class="flex items-center gap-1">
      <?php if ($page > 1): ?>
        <a href="<?php echo build_user_notif_query(['page' => $page - 1]); ?>" class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-chevron-left"></i> Prev</a>
      <?php endif; ?>
      <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
        <a href="<?php echo build_user_notif_query(['page' => $p]); ?>" class="px-2.5 py-1 text-xs rounded-lg font-semibold <?php echo ($p === $page) ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>"><?php echo $p; ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a href="<?php echo build_user_notif_query(['page' => $page + 1]); ?>" class="px-2.5 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Next <i class="fa-solid fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initTableSearchDebounce('userNotifSearchInput', 'userNotifSearchForm', 400);
});
</script>

<?php include 'layout_footer.php'; ?>