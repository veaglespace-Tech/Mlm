<?php
include_once ("z_db.php");
// Initialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
        print "
				<script language='javascript'>
					window.location = 'index.php';
				</script>
			";
        exit;
}

$page_title = 'Users Directory';
$active_nav = 'users';
include("layout_header.php");

// Fetch quick stats
$total_users = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2");
$active_users = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 1");
$inactive_users = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 0");

// --- Pagination ---
$per_page = 20;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_pages = max(1, (int)ceil($total_users / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated users
$users = mlmp_pdo_fetch_all($pdo, "
    SELECT u.*, p.name as package_name 
    FROM affiliateuser u 
    LEFT JOIN packages p ON u.pcktaken = p.id 
    WHERE u.level = 2 
    ORDER BY u.doj DESC
    LIMIT $per_page OFFSET $offset
");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Users Management:</strong> View, edit, activate, or remove platform members. Use the edit panel to customize individual user settings, balances, and packages.</p>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 text-center">
        <span class="block text-3xl font-bold text-indigo-600 mb-2"><?php echo $total_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Total Members</small>
    </div>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 text-center">
        <span class="block text-3xl font-bold text-emerald-600 mb-2"><?php echo $active_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Active / Paid</small>
    </div>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 text-center">
        <span class="block text-3xl font-bold text-red-600 mb-2"><?php echo $inactive_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Inactive / Unpaid</small>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
        <h3 class="text-sm font-bold text-slate-900">Registered Platform Members</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-black/20 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                    <th class="px-5 py-3 font-semibold">Member Details</th>
                    <th class="px-5 py-3 font-semibold">Contact info</th>
                    <th class="px-5 py-3 font-semibold">MLM Package</th>
                    <th class="px-5 py-3 font-semibold">Earnings</th>
                    <th class="px-5 py-3 font-semibold">Referred By</th>
                    <th class="px-5 py-3 font-semibold">Joined Date</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700 divide-y divide-white/5">
                <?php if (count($users) === 0): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-600">
                            <i class="fa-solid fa-users-slash text-3xl mb-3 block"></i>
                            No members registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): 
                        $uname = $user['username'] ?? '';
                        $fullname = $user['fname'] ?? 'No Name';
                        $email = $user['email'] ?? '';
                        $mobile = $user['mobile'] ?? '';
                        $country = $user['country'] ?? '';
                        $pkg_name = $user['package_name'] ?? 'None';
                        $earnings = $user['tamount'] ?? 0;
                        $ref_by = $user['referedby'] ?? 'None';
                        $doj = $user['doj'] ?? '';
                        $is_active = $user['active'] ?? 0;
                    ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=6366f1&color=fff" 
                                         alt="Avatar" class="w-8 h-8 rounded-full shadow-lg shadow-indigo-500/20">
                                    <div>
                                        <div class="font-bold text-slate-800"><?php echo mlmp_escape($fullname); ?></div>
                                        <div class="text-xs text-slate-600">@<?php echo mlmp_escape($uname); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-800"><?php echo mlmp_escape($email); ?></div>
                                <div class="text-xs text-slate-600"><?php echo mlmp_escape($mobile); ?> (<?php echo mlmp_escape($country); ?>)</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-2 py-1 rounded text-xs font-semibold"><?php echo mlmp_escape($pkg_name); ?></span>
                            </td>
                            <td class="px-5 py-4 font-bold text-emerald-600">
                                INR <?php echo number_format((float)$earnings, 2); ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs text-slate-600">@<?php echo mlmp_escape($ref_by); ?></div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600">
                                <?php echo date('M d, Y', strtotime($doj)); ?>
                            </td>
                            <td class="px-5 py-4">
                                <?php if ($is_active == 1): ?>
                                    <span class="bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded text-xs font-semibold">Active</span>
                                <?php else: ?>
                                    <span class="bg-red-500/20 text-red-600 border border-red-500/20 px-2 py-1 rounded text-xs font-semibold">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="updateuser.php?username=<?php echo urlencode($uname); ?>" 
                                   class="inline-flex items-center gap-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors mr-2" title="Edit Member">
                                    <i class="fa-solid fa-user-pen"></i> Edit
                                </a>
                                <button onclick="confirmDelete('<?php echo mlmp_escape($uname); ?>')" 
                                        class="inline-flex items-center gap-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" title="Delete Member">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(username) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete user @" + username + ". This action is irreversible!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#4b5563',
        confirmButtonText: 'Yes, delete member!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'deleteuser.php?username=' + encodeURIComponent(username);
        }
    });
}
</script>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-1">
    <div class="text-sm text-slate-500 font-medium">
        Showing page <strong class="text-slate-700"><?php echo $current_page; ?></strong> of <strong class="text-slate-700"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_users; ?> total members
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
