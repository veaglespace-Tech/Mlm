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

// Search & Sorting inputs
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'doj';
$order = strtolower($_GET['order'] ?? 'desc');
if (!in_array($order, ['asc', 'desc'])) $order = 'desc';

$sort_columns = [
    'doj'       => 'p.doj',
    'fname'     => 'p.fname',
    'username'  => 'p.username',
    'email'     => 'p.email',
    'referedby' => 'p.referedby',
];
$sort_sql = $sort_columns[$sort] ?? 'p.doj';

// Query builder
$where_clauses = ["a.level = 1", "p.admin_approval_status = 'Pending'"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(p.username LIKE ? OR p.fname LIKE ? OR p.email LIKE ? OR p.mobile LIKE ? OR p.referedby LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$where_sql = implode(' AND ', $where_clauses);

// --- Pagination ---
$per_page = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_approvals = mlmp_pdo_count($pdo, "
    SELECT COUNT(*) 
    FROM pending_registrations p 
    JOIN affiliateuser a ON p.referedby = a.username 
    WHERE $where_sql
", $params);
$total_pages = max(1, (int)ceil($total_approvals / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated pending registrations where referedby is an admin (level = 1)
$sql = "SELECT p.*, a.username as admin_username 
        FROM pending_registrations p 
        JOIN affiliateuser a ON p.referedby = a.username 
        WHERE $where_sql
        ORDER BY $sort_sql $order, p.id DESC
        LIMIT $per_page OFFSET $offset";
$pending_users = mlmp_pdo_fetch_all($pdo, $sql, $params);

function sort_th_approvals($key, $label, $cur_sort, $cur_order, $align = 'left') {
    $is_active = ($cur_sort === $key);
    $next_order = ($is_active && $cur_order === 'asc') ? 'desc' : 'asc';
    $icon = '<i class="fa-solid fa-sort text-slate-300 ml-1 text-[11px]"></i>';
    if ($is_active) {
        $icon = ($cur_order === 'asc') 
            ? '<i class="fa-solid fa-sort-up text-indigo-600 ml-1 text-[11px]"></i>' 
            : '<i class="fa-solid fa-sort-down text-indigo-600 ml-1 text-[11px]"></i>';
    }
    $align_cls = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
    return "<th class=\"p-4 text-xs font-bold uppercase tracking-wider text-slate-500 {$align_cls} cursor-pointer select-none hover:text-indigo-600 transition-colors\" onclick=\"applyTableSort('{$key}', '{$next_order}')\">
        <span>{$label}</span> {$icon}
    </th>";
}

function build_approvals_query($override = []) {
    global $search, $sort, $order, $current_page;
    $p = [
        'search' => $search,
        'sort'   => $sort,
        'order'  => $order,
        'page'   => $current_page
    ];
    $merged = array_merge($p, $override);
    if (empty($merged['search'])) unset($merged['search']);
    if ($merged['sort'] === 'doj') unset($merged['sort']);
    if ($merged['order'] === 'desc') unset($merged['order']);
    return http_build_query($merged);
}
?>

<?php if (isset($_SESSION['approval_msg'])): ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-circle-info"></i> <?php echo $_SESSION['approval_msg']; unset($_SESSION['approval_msg']); ?>
</div>
<?php endif; ?>

<!-- Search & Action Toolbar -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
    <form id="approvalsSearchForm" method="GET" class="flex-1 flex items-center gap-2">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">
        <input type="hidden" name="page" value="1">
        <div class="relative w-full max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="approvalsSearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search name, username, email, sponsor..." 
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-8 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            <?php if (!empty($search)): ?>
                <a href="?<?php echo build_approvals_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1" title="Clear Search">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </div>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs md:text-sm font-semibold px-4 py-2 rounded-xl transition-all shadow-sm">
            Search
        </button>
        <?php if (!empty($search) || $sort !== 'doj'): ?>
            <a href="approvals.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs md:text-sm font-semibold px-3 py-2 rounded-xl transition-all">
                Reset
            </a>
        <?php endif; ?>
    </form>
    <div class="text-xs text-slate-500 font-semibold whitespace-nowrap">
        <?php echo $total_approvals; ?> pending request<?php echo $total_approvals !== 1 ? 's' : ''; ?>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h3 class="font-bold text-slate-800 text-base m-0"><i class="fa-solid fa-user-check text-indigo-500 mr-2"></i> Pending Approval Requests</h3>
            <p class="text-xs text-slate-500 mt-0.5">Users listed below have registered using an Administrator's referral ID. Manually approve to place them into the network.</p>
        </div>
        <span class="text-xs text-slate-400">Click column header to sort</span>
    </div>
    
    <div class="p-0">
        <?php if (count($pending_users) === 0): ?>
            <div class="bg-white p-12 text-center">
                <i class="fa-solid fa-inbox text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-base font-bold text-slate-700 mb-1">No Pending Requests</h3>
                <p class="text-xs text-slate-500"><?php echo !empty($search) ? 'No approval requests match your search criteria.' : 'There are no pending registrations requiring manual approval.'; ?></p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <?php echo sort_th_approvals('doj', 'Date', $sort, $order); ?>
                            <?php echo sort_th_approvals('fname', 'User Details', $sort, $order); ?>
                            <?php echo sort_th_approvals('email', 'Contact', $sort, $order); ?>
                            <?php echo sort_th_approvals('referedby', 'Sponsor', $sort, $order); ?>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php foreach($pending_users as $user): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-xs text-slate-600 font-medium whitespace-nowrap">
                                    <?php echo htmlspecialchars($user['doj']); ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($user['fname']); ?></div>
                                    <div class="text-xs text-indigo-600 font-semibold font-mono mt-0.5">@<?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td class="p-4">
                                    <div class="text-xs text-slate-700"><i class="fa-solid fa-envelope text-slate-400 w-4 text-center mr-1"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                    <div class="text-xs text-slate-400 mt-1"><i class="fa-solid fa-phone text-slate-400 w-4 text-center mr-1"></i> <?php echo htmlspecialchars($user['mobile']); ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-bold border border-purple-200 font-mono">
                                        @<?php echo htmlspecialchars($user['referedby']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right whitespace-nowrap">
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

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="p-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-slate-500 font-medium">
            Showing page <strong class="text-slate-800"><?php echo $current_page; ?></strong> of <strong class="text-slate-800"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_approvals; ?> total pending
        </div>
        <div class="flex items-center gap-1 flex-wrap justify-center">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo build_approvals_query(['page' => $current_page - 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                </a>
            <?php endif; ?>

            <?php
            $range = 2;
            $start = max(1, $current_page - $range);
            $end   = min($total_pages, $current_page + $range);
            if ($start > 1): ?>
                <a href="?<?php echo build_approvals_query(['page' => 1]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">1</a>
                <?php if ($start > 2): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="?<?php echo build_approvals_query(['page' => $p]); ?>" 
                   class="px-2.5 py-1.5 rounded-lg border font-bold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
                <a href="?<?php echo build_approvals_query(['page' => $total_pages]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo build_approvals_query(['page' => $current_page + 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                    Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    initTableSearchDebounce('approvalsSearchInput', 'approvalsSearchForm', 450);
});
</script>

<?php include("layout_footer.php"); ?>
