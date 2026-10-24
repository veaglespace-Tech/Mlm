<?php
include_once ("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    print "<script>window.location='index.php';</script>";
    exit;
}
ensure_payout_schema($pdo);

// --- CSV Export Handler ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filter_status = $_GET['status'] ?? 'all';
    $search_q = trim($_GET['q'] ?? '');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payout_requests_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Microsoft Excel

    fputcsv($output, [
        'Request ID',
        'User ID',
        'Username',
        'Full Name',
        'Email',
        'Mobile',
        'Requested Amount (INR)',
        'Payment Status',
        'Bank Name',
        'Account Holder Name',
        'Account Number',
        'IFSC Code',
        'Account Type',
        'Package Plan',
        'Requested Date'
    ]);

    $sql = "
        SELECT p.id, p.userid, p.payment_amount, p.payment_status, p.createdtime,
               u.username, u.fname, u.email, u.mobile, u.bankname, u.accountname, u.accountno, u.ifsccode, u.accounttype,
               pkg.name as package_name
        FROM payments p
        LEFT JOIN affiliateuser u ON u.Id = p.userid
        LEFT JOIN packages pkg ON pkg.id = u.pcktaken
    ";
    $where = [];
    $params = [];

    if ($filter_status === 'pending') {
        $where[] = "p.payment_status = 0";
    } elseif ($filter_status === 'completed') {
        $where[] = "p.payment_status = 1";
    } elseif ($filter_status === 'rejected') {
        $where[] = "p.payment_status = 2";
    }

    if (!empty($search_q)) {
        $where[] = "(u.username LIKE ? OR u.fname LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR p.id = ?)";
        $term = '%' . $search_q . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = is_numeric($search_q) ? (int)$search_q : 0;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY p.id DESC";

    $export_rows = mlmp_pdo_fetch_all($pdo, $sql, $params);
    foreach ($export_rows as $row) {
        $st = match((int)$row['payment_status']) {
            1 => 'Completed',
            2 => 'Rejected',
            default => 'Pending'
        };
        $accType = match((int)$row['accounttype']) {
            1 => 'Current',
            2 => 'Savings',
            default => 'N/A'
        };
        fputcsv($output, [
            $row['id'],
            $row['userid'],
            $row['username'] ?? '',
            $row['fname'] ?? '',
            $row['email'] ?? '',
            $row['mobile'] ?? '',
            number_format((float)$row['payment_amount'], 2, '.', ''),
            $st,
            $row['bankname'] ?? '',
            $row['accountname'] ?? '',
            "'" . ($row['accountno'] ?? ''),
            $row['ifsccode'] ?? '',
            $accType,
            $row['package_name'] ?: 'Standard Package',
            $row['createdtime']
        ]);
    }
    fclose($output);
    exit;
}

// Status & Search inputs
$filter_status = $_GET['status'] ?? 'all';
$search_q = trim($_GET['q'] ?? '');

// Filter Counts for Badges
$count_all = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments");
$count_pending = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE payment_status = 0");
$count_paid = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE payment_status = 1");
$count_rejected = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE payment_status = 2");

// Pending Amount Sum
$pending_sum_row = mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(payment_amount), 0) as total FROM payments WHERE payment_status = 0");
$pending_total_amount = (float)($pending_sum_row['total'] ?? 0);

// --- Build Query ---
$where_clauses = [];
$query_params = [];

if ($filter_status === 'pending') {
    $where_clauses[] = "p.payment_status = 0";
} elseif ($filter_status === 'completed') {
    $where_clauses[] = "p.payment_status = 1";
} elseif ($filter_status === 'rejected') {
    $where_clauses[] = "p.payment_status = 2";
}

if (!empty($search_q)) {
    $where_clauses[] = "(u.username LIKE ? OR u.fname LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR p.id = ?)";
    $term = '%' . $search_q . '%';
    $query_params[] = $term;
    $query_params[] = $term;
    $query_params[] = $term;
    $query_params[] = $term;
    $query_params[] = is_numeric($search_q) ? (int)$search_q : 0;
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Pagination ---
$per_page = 15;
$current_page = max(1, (int)($_GET['page'] ?? 1));

$count_sql = "
    SELECT COUNT(*) 
    FROM payments p 
    LEFT JOIN affiliateuser u ON u.Id = p.userid
    $where_sql
";
$total_requests = mlmp_pdo_count($pdo, $count_sql, $query_params);
$total_pages = max(1, (int)ceil($total_requests / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Fetch paginated payment requests
$select_sql = "
    SELECT p.* 
    FROM payments p 
    LEFT JOIN affiliateuser u ON u.Id = p.userid 
    $where_sql 
    ORDER BY p.id DESC 
    LIMIT $per_page OFFSET $offset
";
$requests = mlmp_pdo_fetch_all($pdo, $select_sql, $query_params);

$page_title = 'Payment Requests';
$active_nav = 'payrequest';
include("layout_header.php");
?>

<!-- Header Actions Bar -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Withdrawal & Payout Management</h2>
        <p class="text-xs text-slate-500 mt-0.5">Review, approve bank disbursements, and export payout ledger for settlements.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
        <?php if ($pending_total_amount > 0): ?>
            <div class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-200 px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm">
                <i class="fa-solid fa-hourglass-half text-amber-500"></i> Pending Payouts: <b>INR <?php echo number_format($pending_total_amount, 2); ?></b>
            </div>
        <?php endif; ?>
        <a href="?export=csv&status=<?php echo urlencode($filter_status); ?>&q=<?php echo urlencode($search_q); ?>" 
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm shadow-emerald-600/20">
            <i class="fa-solid fa-file-csv text-sm"></i> Export CSV
        </a>
    </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
    <!-- Filter Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
        <a href="?status=all<?php echo !empty($search_q) ? '&q=' . urlencode($search_q) : ''; ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 <?php echo $filter_status === 'all' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            All <span class="text-[10px] px-1.5 py-0.2 rounded-full <?php echo $filter_status === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $count_all; ?></span>
        </a>
        <a href="?status=pending<?php echo !empty($search_q) ? '&q=' . urlencode($search_q) : ''; ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 <?php echo $filter_status === 'pending' ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Pending <span class="text-[10px] px-1.5 py-0.2 rounded-full <?php echo $filter_status === 'pending' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $count_pending; ?></span>
        </a>
        <a href="?status=completed<?php echo !empty($search_q) ? '&q=' . urlencode($search_q) : ''; ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 <?php echo $filter_status === 'completed' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Completed <span class="text-[10px] px-1.5 py-0.2 rounded-full <?php echo $filter_status === 'completed' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $count_paid; ?></span>
        </a>
        <a href="?status=rejected<?php echo !empty($search_q) ? '&q=' . urlencode($search_q) : ''; ?>" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5 <?php echo $filter_status === 'rejected' ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">
            Rejected <span class="text-[10px] px-1.5 py-0.2 rounded-full <?php echo $filter_status === 'rejected' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $count_rejected; ?></span>
        </a>
    </div>

    <!-- Search Box -->
    <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
        <div class="relative w-full md:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="Search user, ID or email..." 
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:bg-white transition-all">
        </div>
        <?php if (!empty($search_q)): ?>
            <a href="?status=<?php echo urlencode($filter_status); ?>" class="text-xs text-slate-400 hover:text-slate-600 p-1" title="Clear Search">
                <i class="fa-solid fa-circle-xmark"></i>
            </a>
        <?php endif; ?>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition-all shadow-sm">
            Search
        </button>
    </form>
</div>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Complete the manual bank transfer first, then click "Mark Paid". The system deducts the member wallet only when the request is marked paid. Use "Reject" for invalid requests.</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
        <h3 class="text-sm font-bold text-slate-900">Withdrawal & Payout Queue</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-black/20 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                    <th class="px-5 py-3 font-semibold">Req ID</th>
                    <th class="px-5 py-3 font-semibold">User Account</th>
                    <th class="px-5 py-3 font-semibold">User Status</th>
                    <th class="px-5 py-3 font-semibold">Request Date</th>
                    <th class="px-5 py-3 font-semibold">Amount Requested</th>
                    <th class="px-5 py-3 font-semibold">Package Taken</th>
                    <th class="px-5 py-3 font-semibold">Payout Destination</th>
                    <th class="px-5 py-3 font-semibold">Source</th>
                    <th class="px-5 py-3 font-semibold">Payment Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700 divide-y divide-white/5">
                <?php if (count($requests) === 0): ?>
                    <tr>
                        <td colspan="10" class="px-5 py-8 text-center text-slate-600">
                            <i class="fa-solid fa-hand-holding-dollar text-3xl mb-3 block"></i>
                            No withdrawal requests found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): 
                        $prid = $request['id'];
                        $pruid = $request['userid'];
                        $pramount = $request['payment_amount'];
                        $prstatus = $request['payment_status'];
                        $prdate = $request['createdtime'];
                        
                        // Fetch user details
                        $user = mlmp_pdo_fetch($pdo, "SELECT * FROM affiliateuser WHERE Id = ?", [$pruid]);
                        if (!$user) continue;
                        
                        $username = $user['username'];
                        $fname = $user['fname'];
                        $email = $user['email'];
                        $active = $user['active'];
                        $pck = $user['pcktaken'];
                        $getpayment = $user['getpayment'];
                        $bn = $user['bankname'];
                        $acname = $user['accountname'];
                        $accno = $user['accountno'];
                        $ifsc = $user['ifsccode'];
                        $acct = $user['accounttype'];
                        
                        if ($acct == 1) {
                            $acctype = "Current";
                        } else if ($acct == 2) {
                            $acctype = "Savings";
                        } else {
                            $acctype = "Unknown";
                        }
                        
                        if ($getpayment == 1) {
                            $sendto = "<div class='font-bold text-rose-400'><i class='fa-brands fa-paypal'></i> PayPal (Legacy Payout)</div>";
                            $sendto .= "<small class='text-slate-600 block'>$email</small>";
                        } else {
                            $sendto = "<div class='font-bold text-blue-600'><i class='fa-solid fa-building-columns'></i> Bank Transfer</div>";
                            $sendto .= "<small class='text-slate-600 block leading-relaxed'>
                                <strong class='text-slate-700'>Bank:</strong> " . mlmp_escape($bn) . "<br>
                                <strong class='text-slate-700'>Acc Name:</strong> " . mlmp_escape($acname) . "<br>
                                <strong class='text-slate-700'>Acc No:</strong> " . mlmp_escape($accno) . "<br>
                                <strong class='text-slate-700'>IFSC:</strong> " . mlmp_escape($ifsc) . " (" . $acctype . ")
                            </small>";
                        }
                        
                        if ($active == 1) {
                            $u_status = "Active/Paid";
                            $u_status_class = "bg-emerald-500/20 text-emerald-600 border-emerald-500/20";
                        } else if ($active == 0) {
                            $u_status = "Inactive/Unpaid";
                            $u_status_class = "bg-red-500/20 text-red-600 border-red-500/20";
                        } else {
                            $u_status = "Unknown";
                            $u_status_class = "bg-slate-500/20 text-slate-600 border-slate-500/20";
                        }
                        
                        if ($prstatus == 1) {
                            $pstatus = "Completed";
                            $pstatus_class = "bg-emerald-500/20 text-emerald-600 border-emerald-500/20";
                        } else if ($prstatus == 0) {
                            $pstatus = "Pending";
                            $pstatus_class = "bg-amber-500/20 text-amber-600 border-amber-500/20";
                        } else if ($prstatus == 2) {
                            $pstatus = "Rejected";
                            $pstatus_class = "bg-red-500/20 text-red-600 border-red-500/20";
                        } else {
                            $pstatus = "Unknown";
                            $pstatus_class = "bg-slate-500/20 text-slate-600 border-slate-500/20";
                        }
                        
                        // Fetch package
                        $package = mlmp_pdo_fetch($pdo, "SELECT name, price, currency FROM packages WHERE id = ?", [$pck]);
                        $pckname = $package['name'] ?? 'None';
                        $pckprice = $package['price'] ?? 0;
                        $pckcur = $package['currency'] ?? '';
                    ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-mono text-slate-600">#<?php echo mlmp_escape($prid); ?></td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800"><?php echo mlmp_escape($fname); ?></div>
                                <div class="text-xs text-slate-600">@<?php echo mlmp_escape($username); ?> (ID: <?php echo mlmp_escape($pruid); ?>)</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="<?php echo $u_status_class; ?> border px-2 py-1 rounded text-xs font-semibold whitespace-nowrap"><?php echo $u_status; ?></span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap">
                                <?php echo date('M d, Y H:i', strtotime($prdate)); ?>
                            </td>
                            <td class="px-5 py-4 font-bold text-amber-600 whitespace-nowrap">
                                INR <?php echo number_format((float)$pramount, 2); ?>
                            </td>
                            <td class="px-5 py-4">
                              <div class="font-bold text-slate-800"><?php echo mlmp_escape($pckname); ?></div>
                              <div class="text-xs text-slate-600"><?php echo mlmp_escape($pckprice); ?> <?php echo mlmp_escape($pckcur); ?></div>
                          </td>
                          <td class="px-5 py-4">
                              <?php echo $sendto; ?>
                          </td>
                          <td class="px-5 py-4">
                              <?php echo ($username == $_SESSION['adminidusername']) ? '<span class="text-purple-400 font-bold text-xs whitespace-nowrap">Admin Collection</span>' : '<span class="text-slate-600 text-xs whitespace-nowrap">User Request</span>'; ?>
                          </td>
                            <td class="px-5 py-4">
                                <span class="<?php echo $pstatus_class; ?> border px-2 py-1 rounded text-xs font-semibold whitespace-nowrap"><?php echo $pstatus; ?></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-col gap-2 items-end">
                                    <div class="flex gap-2">
                                        <?php if ($prstatus == 0): ?>
                                            <a href="makepayment.php?payid=<?php echo urlencode($prid); ?>" onclick="return confirm('Mark this withdrawal as paid and deduct the user wallet?');" class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Mark Paid</a>
                                            <a href="rejectpayment.php?payid=<?php echo urlencode($prid); ?>" onclick="return confirm('Reject this withdrawal request?');" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Reject</a>
                                        <?php endif; ?>
                                        <a href="updateuser.php?username=<?php echo urlencode($username); ?>" class="bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Edit User</a>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="deleteuser.php?username=<?php echo urlencode($username); ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-2 py-1 rounded transition-colors" title="Delete User"><i class="fa-solid fa-trash-can"></i></a>
                                        <?php if ($active == 1): ?>
                                            <a href="deactivateuser.php?username=<?php echo urlencode($username); ?>" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">De-Activate</a>
                                        <?php else: ?>
                                            <a href="activateuser.php?username=<?php echo urlencode($username); ?>" class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Activate</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-1">
    <div class="text-sm text-slate-500 font-medium">
        Showing page <strong class="text-slate-700"><?php echo $current_page; ?></strong> of <strong class="text-slate-700"><?php echo $total_pages; ?></strong> &mdash; <?php echo $total_requests; ?> total requests
    </div>
    <div class="flex items-center gap-1.5">
        <?php
        $base_url = '?status=' . urlencode($filter_status) . '&q=' . urlencode($search_q) . '&page=';
        // Previous
        if ($current_page > 1): ?>
            <a href="<?php echo $base_url . ($current_page - 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                <i class="fa-solid fa-chevron-left text-xs"></i> Prev
            </a>
        <?php endif;

        // Page number buttons
        $range = 2;
        $start = max(1, $current_page - $range);
        $end   = min($total_pages, $current_page + $range);
        if ($start > 1): ?>
            <a href="<?php echo $base_url . 1; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">1</a>
            <?php if ($start > 2): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif;
        endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?php echo $base_url . $p; ?>"
               class="px-3 py-2 rounded-lg border text-sm font-semibold transition-all shadow-sm
               <?php echo ($p === $current_page) ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700'; ?>">
                <?php echo $p; ?>
            </a>
        <?php endfor;
        if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-400 text-sm">…</span><?php endif; ?>
            <a href="<?php echo $base_url . $total_pages; ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm"><?php echo $total_pages; ?></a>
        <?php endif;

        // Next
        if ($current_page < $total_pages): ?>
            <a href="<?php echo $base_url . ($current_page + 1); ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all shadow-sm">
                Next <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include("layout_footer.php"); ?>
