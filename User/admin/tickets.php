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

// Filter
$status_filter = $_GET['status'] ?? 'all';
$where_clause = "1=1";
$params = [];
if ($status_filter !== 'all') {
    $where_clause .= " AND t.status = ?";
    $params[] = $status_filter;
}

// Fetch all tickets
$tickets = mlmp_pdo_fetch_all($pdo, "
    SELECT t.*, 
           u.fname AS user_fname,
           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) AS reply_count,
           (SELECT MAX(created_at) FROM ticket_replies WHERE ticket_id = t.id AND sender_type = 'user') AS last_user_reply
    FROM support_tickets t
    LEFT JOIN affiliateuser u ON u.Id = t.user_id
    WHERE $where_clause
    ORDER BY 
        CASE t.status 
            WHEN 'open' THEN 1 
            WHEN 'in_progress' THEN 2 
            WHEN 'resolved' THEN 3 
            WHEN 'closed' THEN 4 
        END,
        CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
        t.updated_at DESC
", $params);

// Counts
$total = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets");
$open_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
$progress_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status = 'in_progress'");
$resolved_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE status IN ('resolved','closed')");

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
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeInUp 0.4s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
</style>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 fade-in">
  <a href="?status=all" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'all' ? 'ring-2 ring-indigo-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-ticket text-indigo-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-slate-900"><?php echo $total; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Total Tickets</div>
  </a>
  <a href="?status=open" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'open' ? 'ring-2 ring-blue-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-dot text-blue-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-blue-600"><?php echo $open_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Open</div>
  </a>
  <a href="?status=in_progress" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'in_progress' ? 'ring-2 ring-amber-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-spinner text-amber-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-amber-600"><?php echo $progress_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">In Progress</div>
  </a>
  <a href="?status=resolved" class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all <?php echo $status_filter === 'resolved' ? 'ring-2 ring-emerald-400' : ''; ?>">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i></div>
      <span class="text-2xl font-bold text-emerald-600"><?php echo $resolved_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Resolved</div>
  </a>
</div>

<!-- Tickets Table -->
<div class="fade-in delay-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
  <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
    <div class="flex items-center gap-2.5">
      <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
        <i class="fa-solid fa-headset text-indigo-500"></i>
      </div>
      <h3 class="text-[15px] font-bold text-slate-900">All Support Tickets</h3>
    </div>
  </div>

  <?php if (empty($tickets)): ?>
  <div class="text-center py-12">
    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
      <i class="fa-solid fa-inbox text-2xl text-slate-300"></i>
    </div>
    <p class="text-sm font-medium text-slate-500">No tickets found</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50">
        <tr class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
          <th class="px-6 py-3">Ticket</th>
          <th class="px-4 py-3">User</th>
          <th class="px-4 py-3">Category</th>
          <th class="px-4 py-3">Priority</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Replies</th>
          <th class="px-4 py-3">Date</th>
          <th class="px-4 py-3">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($tickets as $ticket): ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-3.5">
            <div class="text-[13px] font-semibold text-slate-800">#<?php echo $ticket['id']; ?></div>
            <div class="text-[11px] text-slate-500 truncate max-w-[200px]"><?php echo mlmp_escape($ticket['subject']); ?></div>
          </td>
          <td class="px-4 py-3.5">
            <div class="text-[13px] font-medium text-slate-700"><?php echo mlmp_escape($ticket['user_fname'] ?? $ticket['username']); ?></div>
            <div class="text-[11px] text-slate-400">@<?php echo mlmp_escape($ticket['username']); ?></div>
          </td>
          <td class="px-4 py-3.5">
            <span class="text-[11px] font-medium text-slate-600 bg-slate-50 px-2 py-0.5 rounded-full"><?php echo ucfirst($ticket['category']); ?></span>
          </td>
          <td class="px-4 py-3.5"><?php echo admin_priority_badge($ticket['priority']); ?></td>
          <td class="px-4 py-3.5"><?php echo admin_ticket_status_badge($ticket['status']); ?></td>
          <td class="px-4 py-3.5">
            <span class="text-[13px] font-medium text-slate-700"><?php echo (int)$ticket['reply_count']; ?></span>
            <?php if ($ticket['last_user_reply'] && ($ticket['status'] === 'open' || $ticket['status'] === 'in_progress')): ?>
            <span class="ml-1 w-2 h-2 bg-red-500 rounded-full inline-block" title="User awaiting reply"></span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3.5">
            <div class="text-[12px] text-slate-600"><?php echo date('d M Y', strtotime($ticket['created_at'])); ?></div>
            <div class="text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></div>
          </td>
          <td class="px-4 py-3.5">
            <a href="ticket_view.php?id=<?php echo $ticket['id']; ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-semibold hover:bg-indigo-100 transition-colors">
              <i class="fa-solid fa-eye"></i> View
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include("layout_footer.php"); ?>
