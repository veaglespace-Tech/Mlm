<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

$page_title = "Notifications";
$active_nav = "notifications";
include 'layout_header.php';
?>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col mb-6">
  <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-4">
    <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-bell"></i></span>
      Notifications &amp; Announcements
    </div>
  </div>
  <div class="p-6">
    <?php
    $notifs = mlmp_pdo_fetch_all($pdo, "SELECT id, subject, body, posteddate FROM notifications WHERE valid = 1 ORDER BY id DESC");
    if (empty($notifs)): ?>
      <div class="text-center p-10">
        <i class="fa-solid fa-bell-slash text-5xl text-slate-700 mb-4 block"></i>
        <p class="text-slate-600 text-sm font-medium">No notifications yet.</p>
      </div>
    <?php else:
      foreach ($notifs as $n): ?>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-4 hover:bg-slate-50 transition-colors">
          <div class="flex justify-between items-start flex-wrap gap-2 mb-3">
            <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
              <i class="fa-solid fa-circle-info text-indigo-600"></i>
              <?php echo mlmp_escape($n['subject']); ?>
            </div>
            <span class="text-[11px] text-slate-600 bg-black/20 px-2.5 py-1 rounded-full border border-slate-200">
              <?php echo mlmp_escape($n['posteddate']); ?>
            </span>
          </div>
          <div class="text-sm text-slate-600 leading-relaxed mb-3">
            <?php echo nl2br(mlmp_escape($n['body'])); ?>
          </div>
          <div class="text-[11px] text-slate-600 font-medium">
            Posted by Admin · Msg #<?php echo (int)$n['id']; ?>
          </div>
        </div>
      <?php endforeach;
    endif; ?>
  </div>
</div>

<?php include 'layout_footer.php'; ?>
