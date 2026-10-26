<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

ensure_ticket_schema($pdo);

$username = $_SESSION['username'];
$user = mlmp_pdo_fetch($pdo, "SELECT Id, fname FROM affiliateuser WHERE username = ?", [$username]);
$user_id = $user['Id'] ?? 0;
$fname = $user['fname'] ?? '';

$ticket_id = (int)($_GET['id'] ?? 0);
if (!$ticket_id) { header("Location: tickets.php"); exit; }

// Fetch ticket (only if owned by current user)
$ticket = mlmp_pdo_fetch($pdo, "SELECT * FROM support_tickets WHERE id = ? AND user_id = ?", [$ticket_id, $user_id]);
if (!$ticket) {
    header("Location: tickets.php");
    exit;
}

$page_title = "Ticket #$ticket_id";
$active_nav = 'tickets';
include("layout_header.php");

$msg = '';

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $message = trim($_POST['message'] ?? '');
    if (strlen($message) < 3) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Message must be at least 3 characters.</div>";
    } elseif ($ticket['status'] === 'closed') {
        $msg = "<div class='bg-amber-500/10 border border-amber-500/20 text-amber-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-lock'></i> This ticket is closed. You cannot reply.</div>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, sender_name, message) VALUES (?, 'user', ?, ?)");
        $stmt->execute([$ticket_id, $fname, $message]);
        
        // Reopen if it was resolved
        if ($ticket['status'] === 'resolved') {
            mlmp_pdo_execute($pdo, "UPDATE support_tickets SET status = 'open' WHERE id = ?", [$ticket_id]);
            $ticket['status'] = 'open';
        }
        
        $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Reply sent successfully!</div>";
    }
}

// Fetch replies
$replies = mlmp_pdo_fetch_all($pdo, "SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC", [$ticket_id]);

function status_color($status) {
    $map = ['open' => 'blue', 'in_progress' => 'amber', 'resolved' => 'emerald', 'closed' => 'slate'];
    return $map[$status] ?? 'blue';
}
$sc = status_color($ticket['status']);
?>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.fade-in { animation: fadeInUp 0.4s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
</style>

<!-- Back link -->
<a href="tickets.php" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-purple-600 font-medium mb-4 transition-colors fade-in">
  <i class="fa-solid fa-arrow-left"></i> Back to Tickets
</a>

<?php if ($msg): ?><div class="mb-4 fade-in"><?php echo $msg; ?></div><?php endif; ?>

<!-- Ticket Header -->
<div class="fade-in delay-1 bg-white rounded-2xl border border-slate-200 p-6 mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900"><?php echo mlmp_escape($ticket['subject']); ?></h2>
      <div class="flex items-center gap-3 mt-1.5 text-[12px] text-slate-400">
        <span><i class="fa-solid fa-calendar mr-1"></i> <?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></span>
        <span><i class="fa-solid fa-tag mr-1"></i> <?php echo ucfirst($ticket['category']); ?></span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?php echo $ticket['priority'] === 'high' ? 'bg-red-50 text-red-600' : ($ticket['priority'] === 'medium' ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-500'); ?>"><?php echo ucfirst($ticket['priority']); ?> Priority</span>
      </div>
    </div>
    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-sm font-semibold bg-<?php echo $sc; ?>-50 text-<?php echo $sc; ?>-600 border-<?php echo $sc; ?>-200">
      <i class="fa-solid <?php echo $ticket['status'] === 'open' ? 'fa-circle-dot' : ($ticket['status'] === 'in_progress' ? 'fa-spinner' : ($ticket['status'] === 'resolved' ? 'fa-circle-check' : 'fa-lock')); ?> text-xs"></i>
      <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
    </div>
  </div>
</div>

<!-- Conversation Thread -->
<div class="fade-in delay-2 space-y-4 mb-6">
  <?php foreach ($replies as $reply): 
    $is_admin = ($reply['sender_type'] === 'admin');
  ?>
  <div class="flex <?php echo $is_admin ? 'justify-start' : 'justify-end'; ?>">
    <div class="max-w-[85%] sm:max-w-[70%]">
      <div class="rounded-2xl p-4 <?php echo $is_admin 
        ? 'bg-white border border-slate-200 rounded-tl-sm' 
        : 'bg-gradient-to-br from-purple-600 to-indigo-600 text-white rounded-tr-sm shadow-lg shadow-purple-200'; ?>">
        <div class="flex items-center gap-2 mb-2">
          <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold <?php echo $is_admin ? 'bg-indigo-100 text-indigo-600' : 'bg-white/20 text-white'; ?>">
            <?php echo $is_admin ? 'A' : 'U'; ?>
          </div>
          <span class="text-xs font-semibold <?php echo $is_admin ? 'text-slate-700' : 'text-white/90'; ?>">
            <?php echo mlmp_escape($reply['sender_name']); ?>
            <?php if ($is_admin): ?><span class="text-[10px] bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded-full ml-1">Admin</span><?php endif; ?>
          </span>
        </div>
        <p class="text-sm <?php echo $is_admin ? 'text-slate-700' : 'text-white'; ?> leading-relaxed whitespace-pre-wrap"><?php echo mlmp_escape($reply['message']); ?></p>
      </div>
      <div class="text-[10px] text-slate-400 mt-1 <?php echo $is_admin ? 'text-left' : 'text-right'; ?> px-2">
        <?php echo date('d M Y, h:i A', strtotime($reply['created_at'])); ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Reply Form -->
<?php if ($ticket['status'] !== 'closed'): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-5">
  <form method="POST">
    <input type="hidden" name="send_reply" value="1">
    <label class="block text-xs font-semibold text-slate-600 mb-2">Your Reply</label>
    <textarea name="message" required rows="3" minlength="3" placeholder="Type your reply here..."
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-400 transition-all resize-none mb-3"></textarea>
    <div class="flex justify-end">
      <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-purple-200 transition-all hover:shadow-xl active:scale-[0.98]">
        <i class="fa-solid fa-reply mr-2"></i> Send Reply
      </button>
    </div>
  </form>
</div>
<?php else: ?>
<div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 text-center">
  <i class="fa-solid fa-lock text-slate-400 text-xl mb-2"></i>
  <p class="text-sm text-slate-500">This ticket is closed. No further replies can be sent.</p>
</div>
<?php endif; ?>

<?php include("layout_footer.php"); ?>
