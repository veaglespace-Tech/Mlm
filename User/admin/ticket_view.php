<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

ensure_ticket_schema($pdo);

$ticket_id = (int)($_GET['id'] ?? 0);
if (!$ticket_id) { header("Location: tickets.php"); exit; }

$ticket = mlmp_pdo_fetch($pdo, "
    SELECT t.*, u.fname AS user_fname, u.email AS user_email
    FROM support_tickets t 
    LEFT JOIN affiliateuser u ON u.Id = t.user_id
    WHERE t.id = ?
", [$ticket_id]);

if (!$ticket) { header("Location: tickets.php"); exit; }

$page_title = "Ticket #$ticket_id";
$active_nav = 'tickets';
include("layout_header.php");

// Get admin name
$admin_row = mlmp_pdo_fetch($pdo, "SELECT fname FROM affiliateuser WHERE username = ?", [$_SESSION['adminidusername']]);
$admin_name = $admin_row['fname'] ?? 'Admin';

$msg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'] ?? '';
    $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (in_array($new_status, $valid_statuses)) {
        mlmp_pdo_execute($pdo, "UPDATE support_tickets SET status = ? WHERE id = ?", [$new_status, $ticket_id]);
        $ticket['status'] = $new_status;
        $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Status updated to " . ucfirst(str_replace('_', ' ', $new_status)) . "</div>";
    }
}

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $message = trim($_POST['message'] ?? '');
    if (strlen($message) >= 3) {
        $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, sender_name, message) VALUES (?, 'admin', ?, ?)");
        $stmt->execute([$ticket_id, $admin_name, $message]);
        
        // Auto-set to in_progress if currently open
        if ($ticket['status'] === 'open') {
            mlmp_pdo_execute($pdo, "UPDATE support_tickets SET status = 'in_progress' WHERE id = ?", [$ticket_id]);
            $ticket['status'] = 'in_progress';
        }
        
        $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Reply sent successfully!</div>";
    } else {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Message must be at least 3 characters.</div>";
    }
}

// Fetch replies
$replies = mlmp_pdo_fetch_all($pdo, "SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC", [$ticket_id]);

$status_colors = ['open' => 'blue', 'in_progress' => 'amber', 'resolved' => 'emerald', 'closed' => 'slate'];
$sc = $status_colors[$ticket['status']] ?? 'blue';
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeInUp 0.4s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
</style>

<!-- Back -->
<a href="tickets.php" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-indigo-600 font-medium mb-4 transition-colors fade-in">
  <i class="fa-solid fa-arrow-left"></i> Back to All Tickets
</a>

<?php if ($msg): ?><div class="mb-4 fade-in"><?php echo $msg; ?></div><?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
  
  <!-- Conversation (Left/Center) -->
  <div class="xl:col-span-2 space-y-4">
    
    <!-- Ticket Header -->
    <div class="fade-in delay-1 bg-white rounded-2xl border border-slate-200 p-6">
      <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-bold text-slate-900"><?php echo mlmp_escape($ticket['subject']); ?></h2>
          <div class="flex items-center gap-3 mt-1.5 text-[12px] text-slate-400 flex-wrap">
            <span><i class="fa-solid fa-user mr-1"></i> <?php echo mlmp_escape($ticket['user_fname'] ?? $ticket['username']); ?> (@<?php echo mlmp_escape($ticket['username']); ?>)</span>
            <span><i class="fa-solid fa-envelope mr-1"></i> <?php echo mlmp_escape($ticket['user_email'] ?? ''); ?></span>
            <span><i class="fa-solid fa-calendar mr-1"></i> <?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></span>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold <?php echo $ticket['priority'] === 'high' ? 'bg-red-50 text-red-600' : ($ticket['priority'] === 'medium' ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-500'); ?>">
            <?php echo ucfirst($ticket['priority']); ?>
          </span>
          <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-50 text-slate-600">
            <?php echo ucfirst($ticket['category']); ?>
          </span>
        </div>
      </div>
    </div>
    
    <!-- Conversation Thread -->
    <div class="fade-in delay-2 space-y-3">
      <?php foreach ($replies as $reply): 
        $is_admin = ($reply['sender_type'] === 'admin');
      ?>
      <div class="flex <?php echo $is_admin ? 'justify-end' : 'justify-start'; ?>">
        <div class="max-w-[85%] sm:max-w-[75%]">
          <div class="rounded-2xl p-4 <?php echo $is_admin 
            ? 'bg-gradient-to-br from-indigo-600 to-blue-600 text-white rounded-tr-sm shadow-lg shadow-indigo-200' 
            : 'bg-white border border-slate-200 rounded-tl-sm'; ?>">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold <?php echo $is_admin ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-600'; ?>">
                <?php echo $is_admin ? 'A' : 'U'; ?>
              </div>
              <span class="text-xs font-semibold <?php echo $is_admin ? 'text-white/90' : 'text-slate-700'; ?>">
                <?php echo mlmp_escape($reply['sender_name']); ?>
                <?php if ($is_admin): ?><span class="text-[10px] bg-white/20 text-white px-1.5 py-0.5 rounded-full ml-1">Admin</span>
                <?php else: ?><span class="text-[10px] bg-purple-50 text-purple-600 px-1.5 py-0.5 rounded-full ml-1">User</span>
                <?php endif; ?>
              </span>
            </div>
            <p class="text-sm <?php echo $is_admin ? 'text-white' : 'text-slate-700'; ?> leading-relaxed whitespace-pre-wrap"><?php echo mlmp_escape($reply['message']); ?></p>
          </div>
          <div class="text-[10px] text-slate-400 mt-1 <?php echo $is_admin ? 'text-right' : 'text-left'; ?> px-2">
            <?php echo date('d M Y, h:i A', strtotime($reply['created_at'])); ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Admin Reply Form -->
    <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="fade-in delay-3 bg-white rounded-2xl border border-slate-200 p-5">
      <form method="POST">
        <input type="hidden" name="send_reply" value="1">
        <label class="block text-xs font-semibold text-slate-600 mb-2"><i class="fa-solid fa-reply text-indigo-500 mr-1"></i> Admin Reply</label>
        <textarea name="message" required rows="4" minlength="3" placeholder="Type your response to the user..."
                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 transition-all resize-none mb-3"></textarea>
        <div class="flex justify-between items-center">
          <p class="text-[11px] text-slate-400"><i class="fa-solid fa-info-circle mr-1"></i> Replying auto-sets status to "In Progress"</p>
          <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-200 transition-all hover:shadow-xl active:scale-[0.98]">
            <i class="fa-solid fa-paper-plane mr-2"></i> Send Reply
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar (Right) -->
  <div class="xl:col-span-1 space-y-4">
    
    <!-- Status Management -->
    <div class="fade-in delay-2 bg-white rounded-2xl border border-slate-200 overflow-hidden sticky top-20">
      <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-900">Manage Ticket</h3>
      </div>
      <div class="p-5 space-y-4">
        <!-- Current Status -->
        <div>
          <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Current Status</label>
          <div class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border bg-<?php echo $sc; ?>-50 text-<?php echo $sc; ?>-600 border-<?php echo $sc; ?>-200 font-semibold text-sm">
            <i class="fa-solid <?php echo $ticket['status'] === 'open' ? 'fa-circle-dot' : ($ticket['status'] === 'in_progress' ? 'fa-spinner' : ($ticket['status'] === 'resolved' ? 'fa-circle-check' : 'fa-lock')); ?> text-xs"></i>
            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
          </div>
        </div>

        <!-- Update Status -->
        <form method="POST">
          <input type="hidden" name="update_status" value="1">
          <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Change Status</label>
          <select name="new_status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 mb-3">
            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>🔵 Open</option>
            <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>🟡 In Progress</option>
            <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>🟢 Resolved</option>
            <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>⚫ Closed</option>
          </select>
          <button type="submit" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
            <i class="fa-solid fa-refresh mr-1"></i> Update Status
          </button>
        </form>

        <hr class="border-slate-100">

        <!-- Ticket Info -->
        <div class="space-y-3">
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Ticket ID</div>
            <div class="text-sm font-bold text-slate-800">#<?php echo $ticket['id']; ?></div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">User</div>
            <div class="text-sm font-medium text-slate-800"><?php echo mlmp_escape($ticket['user_fname'] ?? ''); ?></div>
            <div class="text-[11px] text-slate-400">@<?php echo mlmp_escape($ticket['username']); ?></div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Category</div>
            <div class="text-sm text-slate-700"><?php echo ucfirst($ticket['category']); ?></div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Priority</div>
            <div class="text-sm font-medium <?php echo $ticket['priority'] === 'high' ? 'text-red-600' : ($ticket['priority'] === 'medium' ? 'text-blue-600' : 'text-slate-600'); ?>">
              <?php echo ucfirst($ticket['priority']); ?>
            </div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Created</div>
            <div class="text-sm text-slate-700"><?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Last Updated</div>
            <div class="text-sm text-slate-700"><?php echo date('d M Y, h:i A', strtotime($ticket['updated_at'])); ?></div>
          </div>
          <div>
            <div class="text-[10px] font-semibold text-slate-400 uppercase">Total Replies</div>
            <div class="text-sm font-medium text-slate-800"><?php echo count($replies); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include("layout_footer.php"); ?>
