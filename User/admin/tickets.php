<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Support Tickets';
$active_nav = 'tickets';
include("layout_header.php");
ensure_ticket_schema($pdo);

// Counts
$total = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets");
$open_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
$progress_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'");
$resolved_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status IN ('resolved','closed')");

// Filter, Search & Sorting
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? '';
$order = strtolower($_GET['order'] ?? 'desc');
if (!in_array($order, ['asc', 'desc'])) $order = 'desc';

$sort_columns = [
    'id'         => 't.id',
    'subject'    => 't.subject',
    'user_fname' => 'u.fname',
    'priority'   => "CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END",
    'status'     => "CASE t.status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 WHEN 'closed' THEN 4 END",
    'created_at' => 't.created_at',
];

if (!empty($sort) && isset($sort_columns[$sort])) {
    $sort_sql = $sort_columns[$sort] . ' ' . $order;
} else {
    $sort_sql = "CASE t.status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 WHEN 'closed' THEN 4 END, CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END, t.updated_at DESC";
}

$where_clauses = ["1=1"];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($search !== '') {
    $where_clauses[] = "(t.subject LIKE ? OR t.message LIKE ? OR t.username LIKE ? OR u.fname LIKE ? OR t.id = ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $params[] = is_numeric($search) ? (int)$search : 0;
}

$where_sql = implode(' AND ', $where_clauses);

// Pagination
$per_page = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_filtered = mlmp_pdo_count($pdo, "
    SELECT COUNT(*) 
    FROM support_tickets t 
    LEFT JOIN affiliateuser u ON u.Id = t.user_id 
    WHERE $where_sql
", $params);
$total_pages = max(1, (int)ceil($total_filtered / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated tickets
$tickets = mlmp_pdo_fetch_all($pdo, "
    SELECT t.*, 
           u.fname AS user_fname,
           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) AS reply_count,
           (SELECT MAX(created_at) FROM ticket_replies WHERE ticket_id = t.id AND sender_type = 'user') AS last_user_reply
    FROM support_tickets t
    LEFT JOIN affiliateuser u ON u.Id = t.user_id
    WHERE $where_sql
    ORDER BY $sort_sql
    LIMIT $per_page OFFSET $offset
", $params);

function admin_ticket_status_badge($status) {
    $map = [
        'open' => ['bg-blue-50 text-blue-600 border-blue-200', 'fa-circle-dot'],
        'in_progress' => ['bg-amber-50 text-amber-600 border-amber-200', 'fa-spinner'],
        'resolved' => ['bg-emerald-50 text-emerald-600 border-emerald-200', 'fa-circle-check'],
        'closed' => ['bg-slate-100 text-slate-500 border-slate-200', 'fa-lock'],
    ];
    $info = $map[$status] ?? $map['open'];
    $label = ucfirst(str_replace('_', ' ', $status));
    return "<span class='inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] font-semibold {$info[0]}'><i class='fa-solid {$info[1]} text-[9px]'></i> $label</span>";
}

function admin_priority_badge($priority) {
    $map = [
        'low' => 'bg-slate-50 text-slate-500',
        'medium' => 'bg-blue-50 text-blue-600',
        'high' => 'bg-red-50 text-red-600 animate-pulse',
    ];
    $cls = $map[$priority] ?? $map['medium'];
    return "<span class='px-2 py-0.5 rounded-full text-[10px] font-semibold $cls'>" . ucfirst($priority) . "</span>";
}

function sort_th_tickets($key, $label, $cur_sort, $cur_order, $align = 'left') {
    $is_active = ($cur_sort === $key);
    $next_order = ($is_active && $cur_order === 'asc') ? 'desc' : 'asc';
    $icon = '<i class="fa-solid fa-sort text-slate-300 ml-1 text-[11px]"></i>';
    if ($is_active) {
        $icon = ($cur_order === 'asc') 
            ? '<i class="fa-solid fa-sort-up text-indigo-600 ml-1 text-[11px]"></i>' 
            : '<i class="fa-solid fa-sort-down text-indigo-600 ml-1 text-[11px]"></i>';
    }
    $align_cls = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
    return "<th class=\"px-4 py-3 font-semibold {$align_cls} cursor-pointer select-none hover:text-indigo-600 transition-colors\" onclick=\"applyTableSort('{$key}', '{$next_order}')\">
        <span>{$label}</span> {$icon}
    </th>";
}

function build_tickets_query($override = []) {
    global $status_filter, $search, $sort, $order, $current_page;
    $p = [
        'status' => $status_filter,
        'search' => $search,
        'sort'   => $sort,
        'order'  => $order,
        'page'   => $current_page
    ];
    $merged = array_merge($p, $override);
    if ($merged['status'] === 'all') unset($merged['status']);
    if (empty($merged['search'])) unset($merged['search']);
    if (empty($merged['sort'])) unset($merged['sort']);
    if ($merged['order'] === 'desc') unset($merged['order']);
    return http_build_query($merged);
}
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeInUp 0.4s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
</style>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 fade-in">
  <a href="?<?php echo build_tickets_query(['status' => 'all', 'page' => 1]); ?>" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'all' ? 'ring-2 ring-indigo-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-ticket text-indigo-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-slate-900"><?php echo $total; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Total Tickets</div>
  </a>
  <a href="?<?php echo build_tickets_query(['status' => 'open', 'page' => 1]); ?>" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'open' ? 'ring-2 ring-blue-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-dot text-blue-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-blue-600"><?php echo $open_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Open</div>
  </a>
  <a href="?<?php echo build_tickets_query(['status' => 'in_progress', 'page' => 1]); ?>" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'in_progress' ? 'ring-2 ring-amber-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-spinner text-amber-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-amber-600"><?php echo $progress_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">In Progress</div>
  </a>
  <a href="?<?php echo build_tickets_query(['status' => 'resolved', 'page' => 1]); ?>" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'resolved' ? 'ring-2 ring-emerald-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-emerald-600"><?php echo $resolved_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Resolved</div>
  </a>
</div>

<!-- Search & Filter Toolbar -->
<div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
        <?php 
        $tabs = ['all' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
        foreach ($tabs as $key => $label): 
        ?>
        <a href="?<?php echo build_tickets_query(['status' => $key, 'page' => 1]); ?>" 
           class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap
           <?php echo ($status_filter === $key) ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
          <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Debounced Search Box -->
    <form id="ticketsSearchForm" method="GET" class="flex items-center gap-2">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">
        <input type="hidden" name="page" value="1">
        <div class="relative w-full md:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="ticketsSearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ticket #, subject, user..." 
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-8 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            <?php if (!empty($search)): ?>
                <a href="?<?php echo build_tickets_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1" title="Clear Search">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </div>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition-all shadow-sm">
            Search
        </button>
    </form>
</div>

<!-- Tickets Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50">
    <div class="flex items-center gap-2">
      <i class="fa-solid fa-ticket text-indigo-500"></i>
      <h3 class="text-sm font-bold text-slate-900">Support Tickets Queue</h3>
      <span class="text-xs text-slate-500 font-semibold">(<?php echo $total_filtered; ?> found)</span>
    </div>
    <span class="text-xs text-slate-400">Click column header to sort</span>
  </div>

  <?php if (empty($tickets)): ?>
  <div class="text-center py-12">
    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
      <i class="fa-solid fa-inbox text-2xl text-slate-300"></i>
    </div>
    <p class="text-sm font-medium text-slate-500">No tickets found</p>
    <p class="text-xs text-slate-400 mt-1"><?php echo !empty($search) ? 'Try clearing your search terms' : 'No tickets in this view'; ?></p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead class="bg-slate-50">
        <tr class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200">
          <?php echo sort_th_tickets('id', 'Ticket', $sort, $order); ?>
          <?php echo sort_th_tickets('user_fname', 'User', $sort, $order); ?>
          <th class="px-4 py-3">Category</th>
          <?php echo sort_th_tickets('priority', 'Priority', $sort, $order); ?>
          <?php echo sort_th_tickets('status', 'Status', $sort, $order); ?>
          <th class="px-4 py-3">Replies</th>
          <?php echo sort_th_tickets('created_at', 'Date', $sort, $order); ?>
          <th class="px-4 py-3 text-right">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($tickets as $ticket): ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-4 py-3.5">
            <div class="text-[13px] font-semibold text-slate-800">#<?php echo $ticket['id']; ?></div>
            <div class="text-[11px] text-slate-500 truncate max-w-[200px]"><?php echo mlmp_escape($ticket['subject']); ?></div>
          </td>
          <td class="px-4 py-3.5">
            <div class="text-[13px] font-medium text-slate-700"><?php echo mlmp_escape($ticket['user_fname'] ?? $ticket['username']); ?></div>
            <div class="text-[11px] text-slate-400 font-mono">@<?php echo mlmp_escape($ticket['username']); ?></div>
          </td>
          <td class="px-4 py-3.5">
            <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200"><?php echo ucfirst($ticket['category']); ?></span>
          </td>
          <td class="px-4 py-3.5"><?php echo admin_priority_badge($ticket['priority']); ?></td>
          <td class="px-4 py-3.5"><?php echo admin_ticket_status_badge($ticket['status']); ?></td>
          <td class="px-4 py-3.5">
            <span class="text-[13px] font-medium text-slate-700"><?php echo (int)$ticket['reply_count']; ?></span>
            <?php if ($ticket['last_user_reply'] && ($ticket['status'] === 'open' || $ticket['status'] === 'in_progress')): ?>
            <span class="ml-1 w-2 h-2 bg-red-500 rounded-full inline-block" title="User awaiting reply"></span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3.5 whitespace-nowrap">
            <div class="text-[12px] text-slate-600"><?php echo date('d M Y', strtotime($ticket['created_at'])); ?></div>
            <div class="text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></div>
          </td>
          <td class="px-4 py-3.5 text-right whitespace-nowrap">
            <a href="ticket_view.php?id=<?php echo $ticket['id']; ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition-colors">
              <i class="fa-solid fa-eye"></i> View
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Pagination Controls -->
  <?php if ($total_pages > 1): ?>
  <div class="p-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
      <div class="text-slate-500 font-medium">
          Showing page <strong class="text-slate-800"><?php echo $current_page; ?></strong> of <strong class="text-slate-800"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_filtered; ?> total records
      </div>
      <div class="flex items-center gap-1 flex-wrap justify-center">
          <?php if ($current_page > 1): ?>
              <a href="?<?php echo build_tickets_query(['page' => $current_page - 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                  <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
              </a>
          <?php endif; ?>

          <?php
          $range = 2;
          $start = max(1, $current_page - $range);
          $end   = min($total_pages, $current_page + $range);
          if ($start > 1): ?>
              <a href="?<?php echo build_tickets_query(['page' => 1]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">1</a>
              <?php if ($start > 2): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
          <?php endif; ?>

          <?php for ($p = $start; $p <= $end; $p++): ?>
              <a href="?<?php echo build_tickets_query(['page' => $p]); ?>" 
                 class="px-2.5 py-1.5 rounded-lg border font-bold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600'; ?>">
                  <?php echo $p; ?>
              </a>
          <?php endfor; ?>

          <?php if ($end < $total_pages): ?>
              <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
              <a href="?<?php echo build_tickets_query(['page' => $total_pages]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm"><?php echo $total_pages; ?></a>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
              <a href="?<?php echo build_tickets_query(['page' => $current_page + 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                  Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
              </a>
          <?php endif; ?>
      </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    initTableSearchDebounce('ticketsSearchInput', 'ticketsSearchForm', 450);
});
</script>

<?php include("layout_footer.php"); ?>
