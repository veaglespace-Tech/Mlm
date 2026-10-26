<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Support Tickets';
$active_nav = 'tickets';
include("layout_header.php");
ensure_ticket_schema($pdo);

$username = $_SESSION['username'];
$user = mlmp_pdo_fetch($pdo, "SELECT Id, fname FROM affiliateuser WHERE username = ?", [$username]);
$user_id = $user['Id'] ?? 0;
$fname = $user['fname'] ?? '';

$msg = '';

// Handle new ticket creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? 'general');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message  = trim($_POST['message'] ?? '');

    if (strlen($subject) < 3) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Subject must be at least 3 characters.</div>";
    } elseif (strlen($message) < 10) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Message must be at least 10 characters.</div>";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, username, subject, category, priority, status) VALUES (?, ?, ?, ?, ?, 'open')");
            $stmt->execute([$user_id, $username, $subject, $category, $priority]);
            $ticket_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, sender_name, message) VALUES (?, 'user', ?, ?)");
            $stmt2->execute([$ticket_id, $fname, $message]);
            
            $pdo->commit();
            $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Ticket #$ticket_id created successfully! Our team will respond shortly.</div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Error creating ticket. Please try again.</div>";
        }
    }
}

// Fetch user's tickets
$status_filter = $_GET['status'] ?? 'all';
$where_clause = "WHERE user_id = ?";
$params = [$user_id];
if ($status_filter !== 'all') {
    $where_clause .= " AND status = ?";
    $params[] = $status_filter;
}

$tickets = mlmp_pdo_fetch_all($pdo, "
    SELECT t.*, 
           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) AS reply_count,
           (SELECT MAX(created_at) FROM ticket_replies WHERE ticket_id = t.id AND sender_type = 'admin') AS last_admin_reply
    FROM support_tickets t 
    $where_clause 
    ORDER BY t.updated_at DESC
", $params);

// Counts
$open_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status = 'open'", [$user_id]);
$progress_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status = 'in_progress'", [$user_id]);
$resolved_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status IN ('resolved','closed')", [$user_id]);

function ticket_status_badge($status) {
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

function ticket_priority_badge($priority) {
    $map = [
        'low' => 'bg-slate-50 text-slate-500',
        'medium' => 'bg-blue-50 text-blue-600',
        'high' => 'bg-red-50 text-red-600',
    ];
    $cls = $map[$priority] ?? $map['medium'];
    return "<span class='px-2 py-0.5 rounded-full text-[10px] font-semibold $cls'>" . ucfirst($priority) . "</span>";
}
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeInUp 0.5s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
</style>

<?php if ($msg): ?><div class="mb-4 fade-in"><?php echo $msg; ?></div><?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 fade-in delay-1">
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-7 h-7 bg-blue-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-dot text-blue-500 text-xs"></i></div>
      <span class="text-xl font-bold text-slate-900"><?php echo $open_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Open</div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-7 h-7 bg-amber-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-spinner text-amber-500 text-xs"></i></div>
      <span class="text-xl font-bold text-slate-900"><?php echo $progress_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">In Progress</div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-7 h-7 bg-emerald-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500 text-xs"></i></div>
      <span class="text-xl font-bold text-slate-900"><?php echo $resolved_count; ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">Resolved</div>
  </div>
  <a href="?status=all" class="bg-white rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition-colors">
    <div class="flex items-center gap-2 mb-1">
      <div class="w-7 h-7 bg-purple-50 rounded-lg flex items-center justify-center"><i class="fa-solid fa-list text-purple-500 text-xs"></i></div>
      <span class="text-xl font-bold text-slate-900"><?php echo count($tickets); ?></span>
    </div>
    <div class="text-[11px] text-slate-500 font-medium">All Tickets</div>
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  
  <!-- Create Ticket Form -->
  <div class="fade-in delay-2 xl:col-span-1">
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden sticky top-20">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
        <div class="w-9 h-9 bg-purple-50 rounded-xl flex items-center justify-center">
          <i class="fa-solid fa-plus text-purple-500"></i>
        </div>
        <h3 class="text-[15px] font-bold text-slate-900">New Ticket</h3>
      </div>
      <form method="POST" class="p-5 space-y-4">
        <input type="hidden" name="create_ticket" value="1">
        
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Subject *</label>
          <input type="text" name="subject" required maxlength="255" placeholder="Brief description of your issue"
                 class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition-all">
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Category</label>
            <select name="category" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition-all">
              <option value="general">General</option>
              <option value="technical">Technical</option>
              <option value="payment">Payment</option>
              <option value="account">Account</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Priority</label>
            <select name="priority" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition-all">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Message *</label>
          <textarea name="message" required rows="5" minlength="10" placeholder="Describe your issue in detail..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition-all resize-none"></textarea>
        </div>

        <button type="submit" class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-purple-200 transition-all hover:shadow-xl hover:shadow-purple-200 active:scale-[0.98]">
          <i class="fa-solid fa-paper-plane mr-2"></i> Submit Ticket
        </button>
      </form>
    </div>
  </div>

  <!-- Tickets List -->
  <div class="fade-in delay-3 xl:col-span-2">
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
            <i class="fa-solid fa-ticket text-indigo-500"></i>
          </div>
          <h3 class="text-[15px] font-bold text-slate-900">My Tickets</h3>
        </div>
        <div class="flex gap-1.5">
          <?php 
          $filters = ['all' => 'All', 'open' => 'Open', 'in_progress' => 'Active', 'resolved' => 'Resolved'];
          foreach ($filters as $key => $label): 
          ?>
          <a href="?status=<?php echo $key; ?>" 
             class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-colors
             <?php echo ($status_filter === $key) ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:bg-slate-100'; ?>">
            <?php echo $label; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="divide-y divide-slate-100">
        <?php if (empty($tickets)): ?>
        <div class="text-center py-12">
          <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <i class="fa-solid fa-inbox text-2xl text-slate-300"></i>
          </div>
          <p class="text-sm font-medium text-slate-500">No tickets found</p>
          <p class="text-xs text-slate-400 mt-1">Create a new ticket to get started</p>
        </div>
        <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
        <a href="ticket_view.php?id=<?php echo $ticket['id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 transition-colors group">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
            <?php echo $ticket['status'] === 'open' ? 'bg-blue-50 text-blue-500' : ($ticket['status'] === 'in_progress' ? 'bg-amber-50 text-amber-500' : 'bg-emerald-50 text-emerald-500'); ?>">
            <i class="fa-solid <?php echo $ticket['status'] === 'open' ? 'fa-envelope-open' : ($ticket['status'] === 'in_progress' ? 'fa-comments' : 'fa-check-circle'); ?>"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[13px] font-semibold text-slate-800 truncate">#<?php echo $ticket['id']; ?> — <?php echo mlmp_escape($ticket['subject']); ?></span>
              <?php echo ticket_status_badge($ticket['status']); ?>
              <?php echo ticket_priority_badge($ticket['priority']); ?>
            </div>
            <div class="flex items-center gap-3 mt-1 text-[11px] text-slate-400">
              <span><i class="fa-solid fa-tag mr-1"></i><?php echo ucfirst($ticket['category']); ?></span>
              <span><i class="fa-solid fa-message mr-1"></i><?php echo (int)$ticket['reply_count']; ?> replies</span>
              <span><i class="fa-solid fa-clock mr-1"></i><?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></span>
              <?php if ($ticket['last_admin_reply']): ?>
              <span class="text-indigo-500"><i class="fa-solid fa-reply mr-1"></i>Admin replied <?php echo date('d M', strtotime($ticket['last_admin_reply'])); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-500 transition-colors"></i>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include("layout_footer.php"); ?>
