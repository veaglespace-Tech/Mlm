<?php
include_once ("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Rejected Users History';
$active_nav = 'rejected_users';
include("layout_header.php");

// --- Pagination ---
$per_page = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_rejected = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM rejected_registrations");
$total_pages = max(1, (int)ceil($total_rejected / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated rejected registrations
$sql = "SELECT * FROM rejected_registrations ORDER BY rejected_at DESC, id DESC LIMIT $per_page OFFSET $offset";
$rejected_users = mlmp_pdo_fetch_all($pdo, $sql);

?>

<?php if (isset($_SESSION['approval_msg'])): ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-circle-info"></i> <?php echo $_SESSION['approval_msg']; unset($_SESSION['approval_msg']); ?>
</div>
<?php endif; ?>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="p-6 bg-slate-50 border-b border-slate-200">
        <h3 class="font-bold text-slate-800 text-lg m-0"><i class="fa-solid fa-user-xmark text-red-500 mr-2"></i> Rejected Registrations</h3>
        <p class="text-sm text-slate-500 mt-1">This is a permanent audit log of all users whose registration requests were rejected by an administrator.</p>
    </div>
    
    <div class="p-0">
        <?php if (count($rejected_users) === 0): ?>
            <div class="bg-white p-12 text-center">
                <i class="fa-solid fa-inbox text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No Rejected Users</h3>
                <p class="text-sm text-slate-500">There are no rejected registrations in the history.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Rejected</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User Details</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Contact</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Attempted Sponsor</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach($rejected_users as $user): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-sm text-slate-600 font-medium">
                                    <?php echo htmlspecialchars($user['rejected_at']); ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800"><?php echo htmlspecialchars($user['fname']); ?></div>
                                    <div class="text-xs text-red-600 font-semibold mt-0.5">@<?php echo htmlspecialchars($user['username']); ?></div>
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
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold border border-red-200">
                                        Rejected
                                    </span>
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
        Showing page <strong class="text-slate-700"><?php echo $current_page; ?></strong> of <strong class="text-slate-700"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_rejected; ?> total rejected
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
