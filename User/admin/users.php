<?php
include_once ("z_db.php");
// Initialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'Users Directory';
$active_nav = 'users';
include("layout_header.php");

// Fetch quick stats (unfiltered)
$total_users    = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2");
$active_users   = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 1");
$inactive_users = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE level = 2 AND active = 0");

// --- Search, Filter & Sort inputs ---
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all | active | inactive
$sort   = $_GET['sort'] ?? 'doj';
$order  = strtolower($_GET['order'] ?? 'desc');
if (!in_array($order, ['asc', 'desc'])) $order = 'desc';

$sort_columns = [
    'fname'        => 'u.fname',
    'username'     => 'u.username',
    'package_name' => 'package_name',
    'tamount'      => 'u.tamount',
    'doj'          => 'u.doj',
    'active'       => 'u.active',
    'referedby'    => 'u.referedby',
];
$sort_sql = $sort_columns[$sort] ?? 'u.doj';

// Query builder
$where_clauses = ["u.level = 2"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(u.username LIKE ? OR u.fname LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR u.country LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filter === 'active') {
    $where_clauses[] = "u.active = 1";
} elseif ($filter === 'inactive') {
    $where_clauses[] = "u.active = 0";
}

$where_sql = implode(' AND ', $where_clauses);

// --- Pagination ---
$per_page       = 20;
$current_page   = max(1, (int)($_GET['page'] ?? 1));
$total_filtered = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser u WHERE $where_sql", $params);
$total_pages    = max(1, (int)ceil($total_filtered / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset         = ($current_page - 1) * $per_page;

// Fetch paginated users
$users = mlmp_pdo_fetch_all($pdo, "
    SELECT u.*, p.name as package_name 
    FROM affiliateuser u 
    LEFT JOIN packages p ON u.pcktaken = p.id 
    WHERE $where_sql 
    ORDER BY $sort_sql $order 
    LIMIT $per_page OFFSET $offset
", $params);

// Helper for column sort headers
function sort_th_users($key, $label, $cur_sort, $cur_order, $align = 'left') {
    $is_active = ($cur_sort === $key);
    $next_order = ($is_active && $cur_order === 'asc') ? 'desc' : 'asc';
    $icon = '<i class="fa-solid fa-sort text-slate-300 ml-1 text-[11px]"></i>';
    if ($is_active) {
        $icon = ($cur_order === 'asc') 
            ? '<i class="fa-solid fa-sort-up text-indigo-600 ml-1 text-[11px]"></i>' 
            : '<i class="fa-solid fa-sort-down text-indigo-600 ml-1 text-[11px]"></i>';
    }
    $align_cls = $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
    return "<th class=\"px-5 py-3 font-semibold {$align_cls} cursor-pointer select-none hover:text-indigo-600 transition-colors\" onclick=\"applyTableSort('{$key}', '{$next_order}')\">
        <span>{$label}</span> {$icon}
    </th>";
}

// Helper query string builder
function build_users_query($override = []) {
    global $search, $filter, $sort, $order, $current_page;
    $params = [
        'search' => $search,
        'filter' => $filter,
        'sort'   => $sort,
        'order'  => $order,
        'page'   => $current_page
    ];
    $merged = array_merge($params, $override);
    if (empty($merged['search'])) unset($merged['search']);
    if ($merged['filter'] === 'all') unset($merged['filter']);
    if ($merged['sort'] === 'doj') unset($merged['sort']);
    if ($merged['order'] === 'desc') unset($merged['order']);
    return http_build_query($merged);
}
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Users Management:</strong> View, search, filter, sort, edit, activate, or remove platform members. Use the edit panel to customize individual user settings, balances, and packages.</p>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-6">
    <a href="?filter=all" class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 text-center shadow-sm hover:border-indigo-300 hover:shadow-md transition-all">
        <span class="block text-3xl font-extrabold text-indigo-600 mb-1"><?php echo $total_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Total Members</small>
    </a>
    <a href="?filter=active" class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 text-center shadow-sm hover:border-emerald-300 hover:shadow-md transition-all">
        <span class="block text-3xl font-extrabold text-emerald-600 mb-1"><?php echo $active_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Active / Paid</small>
    </a>
    <a href="?filter=inactive" class="bg-white border border-slate-200 rounded-2xl p-5 md:p-6 text-center shadow-sm hover:border-red-300 hover:shadow-md transition-all">
        <span class="block text-3xl font-extrabold text-red-600 mb-1"><?php echo $inactive_users; ?></span>
        <small class="text-slate-600 font-semibold uppercase tracking-wider text-xs">Inactive / Unpaid</small>
    </a>
</div>

<!-- Search, Filter & Toolbar -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
    <form id="usersSearchForm" method="GET" action="users.php" class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">

        <!-- Debounced Search Box -->
        <div class="relative flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="usersSearchInput" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search name, username, email, phone, country..." 
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-8 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all">
            <?php if (!empty($search)): ?>
                <a href="users.php?<?php echo build_users_query(['search' => '', 'page' => 1]); ?>" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs p-1" title="Clear Search">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter Dropdown -->
        <div class="flex items-center gap-2">
            <select name="filter" onchange="document.getElementById('usersSearchForm').submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs md:text-sm text-slate-700 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white transition-all cursor-pointer">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
            </select>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs md:text-sm font-semibold px-4 py-2 rounded-xl transition-all shadow-sm shadow-indigo-600/20 whitespace-nowrap">
                <i class="fa-solid fa-search mr-1"></i> Search
            </button>

            <?php if ($search !== '' || $filter !== 'all' || $sort !== 'doj'): ?>
                <a href="users.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs md:text-sm font-semibold px-3 py-2 rounded-xl transition-all whitespace-nowrap" title="Reset all filters">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="text-xs text-slate-500 font-semibold whitespace-nowrap flex items-center gap-1.5 self-end md:self-center">
        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
        <?php echo $total_filtered; ?> member<?php echo $total_filtered !== 1 ? 's' : ''; ?> found
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-users text-indigo-600"></i> Registered Platform Members
        </h3>
        <span class="text-xs text-slate-500">Click column header to sort</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <?php echo sort_th_users('fname', 'Member Details', $sort, $order); ?>
                    <th class="px-5 py-3 font-semibold">Contact Info</th>
                    <?php echo sort_th_users('package_name', 'MLM Package', $sort, $order); ?>
                    <?php echo sort_th_users('tamount', 'Earnings', $sort, $order); ?>
                    <?php echo sort_th_users('referedby', 'Referred By', $sort, $order); ?>
                    <?php echo sort_th_users('doj', 'Joined Date', $sort, $order); ?>
                    <?php echo sort_th_users('active', 'Status', $sort, $order, 'center'); ?>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700 divide-y divide-slate-100">
                <?php if (count($users) === 0): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                            <i class="fa-solid fa-users-slash text-4xl text-slate-300 mb-3 block"></i>
                            <div class="font-semibold text-slate-700">No members found matching your search or filter.</div>
                            <div class="text-xs text-slate-400 mt-1">Try clearing search terms or changing filters.</div>
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
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullname); ?>&background=6366f1&color=fff" 
                                         alt="Avatar" class="w-8 h-8 rounded-full shadow-sm object-cover shrink-0">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 text-sm truncate"><?php echo mlmp_escape($fullname); ?></div>
                                        <div class="text-xs text-indigo-600 font-mono">@<?php echo mlmp_escape($uname); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="text-slate-800 text-xs"><?php echo mlmp_escape($email); ?></div>
                                <div class="text-xs text-slate-400 mt-0.5"><?php echo mlmp_escape($mobile); ?> <?php if ($country): ?>(<?php echo mlmp_escape($country); ?>)<?php endif; ?></div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1 rounded-md text-xs font-semibold whitespace-nowrap"><?php echo mlmp_escape($pkg_name); ?></span>
                            </td>
                            <td class="px-5 py-3.5 font-bold text-emerald-600 whitespace-nowrap">
                                ₹ <?php echo number_format((float)$earnings, 2); ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-xs font-mono text-slate-600">@<?php echo mlmp_escape($ref_by); ?></span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                <?php echo !empty($doj) && $doj !== '0000-00-00' ? date('M d, Y', strtotime($doj)) : '—'; ?>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <?php if ($is_active == 1): ?>
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                        <i class="fa-solid fa-circle-check text-[9px]"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-200 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                        <i class="fa-solid fa-clock text-[9px]"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="updateuser.php?username=<?php echo urlencode($uname); ?>" 
                                   class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors mr-1.5" title="Edit Member">
                                    <i class="fa-solid fa-user-pen"></i> Edit
                                </a>
                                <button onclick="confirmDelete('<?php echo mlmp_escape($uname); ?>')" 
                                        class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors" title="Delete Member">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-slate-500 font-medium">
            Showing page <strong class="text-slate-800"><?php echo $current_page; ?></strong> of <strong class="text-slate-800"><?php echo $total_pages; ?></strong> &mdash; records <strong class="text-slate-800"><?php echo ($offset + 1); ?></strong>-<strong><?php echo min($total_filtered, $offset + $per_page); ?></strong> of <strong><?php echo $total_filtered; ?></strong>
        </div>
        <div class="flex items-center gap-1 flex-wrap justify-center">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo build_users_query(['page' => $current_page - 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                </a>
            <?php endif; ?>

            <?php
            $range = 2;
            $start = max(1, $current_page - $range);
            $end   = min($total_pages, $current_page + $range);
            if ($start > 1): ?>
                <a href="?<?php echo build_users_query(['page' => 1]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">1</a>
                <?php if ($start > 2): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="?<?php echo build_users_query(['page' => $p]); ?>" 
                   class="px-2.5 py-1.5 rounded-lg border font-bold transition-all shadow-sm <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
                <a href="?<?php echo build_users_query(['page' => $total_pages]); ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo build_users_query(['page' => $current_page + 1]); ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shadow-sm">
                    Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize debounced search for users
    initTableSearchDebounce('usersSearchInput', 'usersSearchForm', 450);
});

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

<?php include("layout_footer.php"); ?>
