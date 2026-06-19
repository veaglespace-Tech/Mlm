<?php
include_once ("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Approval Requests';
$active_nav = 'approvals';
include("layout_header.php");

// --- Pagination ---
$per_page = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_approvals = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM pending_registrations p JOIN affiliateuser a ON p.referedby = a.username WHERE a.level = 1 AND p.admin_approval_status = 'Pending'");
$total_pages = max(1, (int)ceil($total_approvals / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated pending registrations where referedby is an admin (level = 1)
$sql = "SELECT p.*, a.username as admin_username 
        FROM pending_registrations p 
        JOIN affiliateuser a ON p.referedby = a.username 
        WHERE a.level = 1 AND p.admin_approval_status = 'Pending'
        ORDER BY p.doj DESC, p.id DESC
        LIMIT $per_page OFFSET $offset";
$pending_users = mlmp_pdo_fetch_all($pdo, $sql);

?>

<?php if (isset($_SESSION['approval_msg'])): ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-circle-info"></i> <?php echo $_SESSION['approval_msg']; unset($_SESSION['approval_msg']); ?>
</div>
<?php endif; ?>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="p-6 bg-slate-50 border-b border-slate-200">
        <h3 class="font-bold text-slate-800 text-lg m-0"><i class="fa-solid fa-user-check text-indigo-500 mr-2"></i> Pending Approval Requests</h3>
        <p class="text-sm text-slate-500 mt-1">Users listed below have registered using an Administrator's referral ID. You must manually approve their join requests to activate their accounts.</p>
    </div>
    
    <div class="p-0">
        <?php if (count($pending_users) === 0): ?>
            <div class="bg-white p-12 text-center">
                <i class="fa-solid fa-inbox text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No Pending Requests</h3>
                <p class="text-sm text-slate-500">There are no pending registrations requiring manual approval.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User Details</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Contact</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Sponsor</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach($pending_users as $user): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-sm text-slate-600 font-medium">
                                    <?php echo htmlspecialchars($user['doj']); ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?php echo htmlspecialchars($user['fname']); ?></div>
                                    <div class="text-xs text-indigo-600 font-semibold mt-0.5">@<?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td class="p-4">
                                    <div class="text-sm text-slate-700"><i class="fa-solid fa-envelope text-slate-400 w-4 text-center mr-1"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                    <div class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-phone text-slate-400 w-4 text-center mr-1"></i> <?php echo htmlspecialchars($user['mobile']); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold border border-purple-200">
                                        @<?php echo htmlspecialchars($user['referedby']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex gap-2 justify-end">
                                        <form action="approve_user.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                            <button type="submit" onclick="return confirm('Are you sure you want to APPROVE this user? This will place them in the network.');" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 py-1.5 px-3 text-xs font-bold rounded-lg transition-colors shadow-sm">
                                                <i class="fa-solid fa-check mr-1"></i> Approve
                                            </button>
                                        </form>
                                        <form action="approve_user.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                            <button type="submit" onclick="return confirm('Are you sure you want to REJECT and DELETE this user?');" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-1.5 px-3 text-xs font-bold rounded-lg transition-colors shadow-sm">
                                                <i class="fa-solid fa-xmark mr-1"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-1">
    <div class="text-sm text-slate-500 font-medium">
        Showing page <strong class="text-slate-700"><?php echo $current_page; ?></strong> of <strong class="text-slate-700"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_approvals; ?> total pending
    </div>
    <div class="flex items-center gap-1.5">
        <?php
        $base_url = '?page=';
        if ($current_page > 1): ?>
            <a href="<?php echo $base_url . ($current_page - 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                <i class="fa-solid fa-chevron-left text-xs"></i> Prev
            </a>
        <?php endif;
        $range = 2;
        $start = max(1, $current_page - $range);
        $end   = min($total_pages, $current_page + $range);
        if ($start > 1): ?>
            <a href="<?php echo $base_url . 1; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">1</a>
            <?php if ($start > 2): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif;
        endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?php echo $base_url . $p; ?>" class="px-3 py-2 rounded-lg border text-sm font-semibold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700'; ?>"><?php echo $p; ?></a>
        <?php endfor;
        if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif; ?>
            <a href="<?php echo $base_url . $total_pages; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm"><?php echo $total_pages; ?></a>
        <?php endif;
        if ($current_page < $total_pages): ?>
            <a href="<?php echo $base_url . ($current_page + 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                Next <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include("layout_footer.php"); ?>
