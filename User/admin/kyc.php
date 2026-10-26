<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'KYC Verifications';
$active_nav = 'kyc';
include("layout_header.php");
ensure_kyc_schema($pdo);

$admin_user = $_SESSION['adminidusername'];
$msg = '';

// Handle Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $kyc_id = (int)($_POST['kyc_id'] ?? 0);

    if ($kyc_id > 0) {
        $kyc = mlmp_pdo_fetch($pdo, "SELECT * FROM kyc_documents WHERE id = ?", [$kyc_id]);
        if ($kyc) {
            $user_id = $kyc['user_id'];
            $username = $kyc['username'];

            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE kyc_documents SET status = 'approved', reviewed_at = NOW(), reviewed_by = ?, reject_reason = NULL WHERE id = ?");
                $stmt->execute([$admin_user, $kyc_id]);

                $stmt2 = $pdo->prepare("UPDATE affiliateuser SET kyc_status = 'approved' WHERE Id = ?");
                $stmt2->execute([$user_id]);

                $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> KYC for <strong>" . htmlspecialchars($username) . "</strong> has been approved successfully!</div>";
            } elseif ($action === 'reject') {
                $reason = trim($_POST['reject_reason'] ?? 'Documents incomplete or invalid.');
                $stmt = $pdo->prepare("UPDATE kyc_documents SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ?, reject_reason = ? WHERE id = ?");
                $stmt->execute([$admin_user, $reason, $kyc_id]);

                $stmt2 = $pdo->prepare("UPDATE affiliateuser SET kyc_status = 'rejected' WHERE Id = ?");
                $stmt2->execute([$user_id]);

                $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> KYC for <strong>" . htmlspecialchars($username) . "</strong> has been rejected. Reason recorded.</div>";
            }
        }
    }
}

// Counts
$total_count    = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM kyc_documents");
$pending_count  = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM kyc_documents WHERE status = 'pending'");
$approved_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM kyc_documents WHERE status = 'approved'");
$rejected_count = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM kyc_documents WHERE status = 'rejected'");

// Filter
$status_filter = $_GET['status'] ?? 'pending';
$where_clause = "1=1";
$params = [];
if ($status_filter !== 'all') {
    $where_clause .= " AND k.status = ?";
    $params[] = $status_filter;
}

// Fetch Records with User Details
$records = mlmp_pdo_fetch_all($pdo, "
    SELECT k.*, u.email, u.mobile, u.fname as registered_name
    FROM kyc_documents k
    LEFT JOIN affiliateuser u ON k.user_id = u.Id
    WHERE $where_clause
    ORDER BY (k.status = 'pending') DESC, k.submitted_at DESC
", $params);
?>

<div class="dash-content space-y-6">
  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2.5">
        <i class="fa-solid fa-id-card text-indigo-600"></i> KYC Document Verifications
      </h1>
      <p class="text-sm text-slate-500 mt-0.5">Review, verify, or reject member Aadhaar and PAN KYC submissions.</p>
    </div>
  </div>

  <?php if ($msg) echo $msg; ?>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <a href="?status=pending" class="block p-5 rounded-2xl border transition-all <?php echo $status_filter === 'pending' ? 'bg-amber-500/10 border-amber-500/30 ring-2 ring-amber-500/20' : 'bg-white border-slate-200/80 hover:border-slate-300'; ?>">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Review</div>
          <div class="text-2xl font-bold text-amber-600 mt-1"><?php echo $pending_count; ?></div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-xl">
          <i class="fa-solid fa-clock"></i>
        </div>
      </div>
    </a>

    <a href="?status=approved" class="block p-5 rounded-2xl border transition-all <?php echo $status_filter === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30 ring-2 ring-emerald-500/20' : 'bg-white border-slate-200/80 hover:border-slate-300'; ?>">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approved KYC</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1"><?php echo $approved_count; ?></div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-xl">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
    </a>

    <a href="?status=rejected" class="block p-5 rounded-2xl border transition-all <?php echo $status_filter === 'rejected' ? 'bg-red-500/10 border-red-500/30 ring-2 ring-red-500/20' : 'bg-white border-slate-200/80 hover:border-slate-300'; ?>">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rejected</div>
          <div class="text-2xl font-bold text-red-600 mt-1"><?php echo $rejected_count; ?></div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center text-xl">
          <i class="fa-solid fa-circle-xmark"></i>
        </div>
      </div>
    </a>

    <a href="?status=all" class="block p-5 rounded-2xl border transition-all <?php echo $status_filter === 'all' ? 'bg-indigo-500/10 border-indigo-500/30 ring-2 ring-indigo-500/20' : 'bg-white border-slate-200/80 hover:border-slate-300'; ?>">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Submissions</div>
          <div class="text-2xl font-bold text-slate-800 mt-1"><?php echo $total_count; ?></div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-xl">
          <i class="fa-solid fa-layer-group"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Records Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <!-- Filters Header -->
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <a href="?status=pending" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'pending' ? 'bg-amber-100 text-amber-800 font-bold' : 'text-slate-600 hover:bg-slate-100'; ?>">
          Pending (<?php echo $pending_count; ?>)
        </a>
        <a href="?status=approved" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'approved' ? 'bg-emerald-100 text-emerald-800 font-bold' : 'text-slate-600 hover:bg-slate-100'; ?>">
          Approved (<?php echo $approved_count; ?>)
        </a>
        <a href="?status=rejected" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'rejected' ? 'bg-red-100 text-red-800 font-bold' : 'text-slate-600 hover:bg-slate-100'; ?>">
          Rejected (<?php echo $rejected_count; ?>)
        </a>
        <a href="?status=all" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all <?php echo $status_filter === 'all' ? 'bg-indigo-100 text-indigo-800 font-bold' : 'text-slate-600 hover:bg-slate-100'; ?>">
          All (<?php echo $total_count; ?>)
        </a>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-semibold text-xs uppercase tracking-wider">
            <th class="py-3.5 px-4">User</th>
            <th class="py-3.5 px-4">Aadhaar & PAN</th>
            <th class="py-3.5 px-4">Attached Documents</th>
            <th class="py-3.5 px-4">Submitted Date</th>
            <th class="py-3.5 px-4">Status</th>
            <th class="py-3.5 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($records)): ?>
            <tr>
              <td colspan="6" class="py-12 text-center text-slate-400">
                <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                No KYC records found for the selected filter.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($records as $r): ?>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <!-- User -->
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800"><?php echo htmlspecialchars($r['full_name']); ?></div>
                  <div class="text-xs text-indigo-600 font-medium">@<?php echo htmlspecialchars($r['username']); ?></div>
                  <div class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars($r['mobile'] ?? ''); ?></div>
                </td>

                <!-- Aadhaar & PAN -->
                <td class="py-3.5 px-4 font-mono text-xs">
                  <div>
                    <span class="text-slate-400">Aadhaar:</span> 
                    <strong><?php echo htmlspecialchars($r['aadhar_number']); ?></strong>
                  </div>
                  <div class="mt-1">
                    <span class="text-slate-400">PAN:</span> 
                    <strong class="text-indigo-600 uppercase"><?php echo htmlspecialchars($r['pan_number']); ?></strong>
                  </div>
                </td>

                <!-- Attached Documents -->
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1.5">
                    <?php if (!empty($r['aadhar_front'])): ?>
                      <a href="../<?php echo htmlspecialchars($r['aadhar_front']); ?>" target="_blank" 
                         class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 transition-colors border border-slate-200">
                        <i class="fa-solid fa-image"></i> Aadhaar Front
                      </a>
                    <?php endif; ?>

                    <?php if (!empty($r['aadhar_back'])): ?>
                      <a href="../<?php echo htmlspecialchars($r['aadhar_back']); ?>" target="_blank" 
                         class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 transition-colors border border-slate-200">
                        <i class="fa-solid fa-image"></i> Aadhaar Back
                      </a>
                    <?php endif; ?>

                    <?php if (!empty($r['pan_card'])): ?>
                      <a href="../<?php echo htmlspecialchars($r['pan_card']); ?>" target="_blank" 
                         class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 transition-colors border border-slate-200">
                        <i class="fa-solid fa-credit-card"></i> PAN Card
                      </a>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Submitted Date -->
                <td class="py-3.5 px-4 text-xs text-slate-500 whitespace-nowrap">
                  <div><?php echo date('d M Y, h:i A', strtotime($r['submitted_at'])); ?></div>
                  <?php if (!empty($r['reviewed_at'])): ?>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                      Reviewed: <?php echo date('d M Y', strtotime($r['reviewed_at'])); ?> by <?php echo htmlspecialchars($r['reviewed_by'] ?? 'Admin'); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Status -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <?php if ($r['status'] === 'approved'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-100 text-emerald-800">
                      <i class="fa-solid fa-check"></i> Approved
                    </span>
                  <?php elseif ($r['status'] === 'pending'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-100 text-amber-800">
                      <i class="fa-solid fa-clock"></i> Pending
                    </span>
                  <?php elseif ($r['status'] === 'rejected'): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 text-red-800" title="<?php echo htmlspecialchars($r['reject_reason'] ?? ''); ?>">
                      <i class="fa-solid fa-xmark"></i> Rejected
                    </span>
                    <?php if (!empty($r['reject_reason'])): ?>
                      <div class="text-[11px] text-red-500 mt-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($r['reject_reason']); ?>">
                        <?php echo htmlspecialchars($r['reject_reason']); ?>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <?php if ($r['status'] !== 'approved'): ?>
                      <form action="kyc.php?status=<?php echo urlencode($status_filter); ?>" method="POST" onsubmit="return confirm('Approve KYC for <?php echo htmlspecialchars($r['username']); ?>?');" class="inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="kyc_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-sm transition-all flex items-center gap-1">
                          <i class="fa-solid fa-check"></i> Approve
                        </button>
                      </form>
                    <?php endif; ?>

                    <?php if ($r['status'] !== 'rejected'): ?>
                      <button onclick="openRejectModal(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars(addslashes($r['username'])); ?>')" 
                              class="px-3 py-1.5 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 font-semibold text-xs transition-all flex items-center gap-1">
                        <i class="fa-solid fa-xmark"></i> Reject
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-base">Reject KYC Verification</h3>
      </div>
      <button onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="kyc.php?status=<?php echo urlencode($status_filter); ?>" method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="reject">
      <input type="hidden" name="kyc_id" id="reject_kyc_id" value="">

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">User</label>
        <div id="reject_username_display" class="font-bold text-indigo-600 text-sm"></div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rejection Reason *</label>
        <textarea name="reject_reason" required rows="3"
                  placeholder="e.g. Document image is blurred, Name does not match registered profile, PAN number invalid." 
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 transition-all font-medium"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" onclick="closeRejectModal()" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 text-sm font-semibold transition-colors">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm shadow-sm transition-all flex items-center gap-1.5">
          <i class="fa-solid fa-xmark"></i> Confirm Rejection
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openRejectModal(id, username) {
  document.getElementById('reject_kyc_id').value = id;
  document.getElementById('reject_username_display').textContent = '@' + username;
  const modal = document.getElementById('rejectModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeRejectModal() {
  const modal = document.getElementById('rejectModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>

<?php include("layout_footer.php"); ?>
