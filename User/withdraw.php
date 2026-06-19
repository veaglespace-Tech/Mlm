<?php
include_once ("z_db.php");
// Initialize session
session_start();

// Check if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['username'])) {
    print "
        <script language='javascript'>
            window.location = 'index.php';
        </script>
    ";
    exit;
}

$page_title = "Withdraw Funds";
$active_nav = "withdraw";
include("layout_header.php");
ensure_payout_schema($pdo);

$username = $_SESSION['username'];

// Fetch user data
$stmtUser = $pdo->prepare("SELECT Id, pcktaken, tamount, bankname, accountno, ifsccode FROM affiliateuser WHERE username = ? LIMIT 1");
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();

$pck = $user['pcktaken'] ?? 0;

// Dynamic Minimum Payout Logic
$min_withdraw = 500.00; // Default fallback
if ($pck) {
    $stmtPkg = $pdo->prepare("SELECT mpay FROM packages WHERE id = ? LIMIT 1");
    $stmtPkg->execute([$pck]);
    $pkg = $stmtPkg->fetch();
    if ($pkg && isset($pkg['mpay'])) {
        $min_withdraw = (float)$pkg['mpay'];
    }
}

$userId = $user['Id'];
$current_balance = (float)$user['tamount'];
$pending_row = mlmp_pdo_fetch($pdo, "SELECT COALESCE(SUM(payment_amount), 0) AS pending_total FROM payments WHERE userid = ? AND payment_status = 0", [$userId]);
$pending_reserved = (float)($pending_row['pending_total'] ?? 0);
$available_balance = max(0, $current_balance - $pending_reserved);
$has_bank_details = ($user['bankname'] !== 'Not Available' && $user['accountno'] != 0 && $user['ifsccode'] !== 'Not Available');

// Process Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdraw'])) {
    $req_amount = (float)$_POST['amount'];
    
    if (!$has_bank_details) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Please update your bank details first.</div>";
    } elseif ($req_amount < $min_withdraw) {
        $msg = "<div class='bg-amber-500/10 border border-amber-500/20 text-amber-500 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-triangle-exclamation'></i> Minimum withdrawal is INR $min_withdraw.</div>";
    } elseif ($req_amount > $available_balance) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Insufficient available balance. You can request up to INR " . number_format($available_balance, 2) . ".</div>";
    } else {
        try {
            $itemid = "WREQ-" . time() . "-" . rand(100, 999);
            $stmtInsert = $pdo->prepare("INSERT INTO payments (userid, payment_amount, payment_status, itemid, createdtime) VALUES (?, ?, 0, ?, NOW())");
            $stmtInsert->execute([$userId, $req_amount, $itemid]);

            $pending_reserved += $req_amount;
            $available_balance = max(0, $current_balance - $pending_reserved);
            $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Withdrawal request for INR " . number_format($req_amount, 2) . " submitted successfully. It is pending admin approval.</div>";
        } catch (Exception $e) {
            $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-triangle-exclamation'></i> System error occurred.</div>";
        }
    }
}
?>

<?php
// --- Payout Requests Pagination ---
$req_per_page = 10;
$req_page = max(1, (int)($_GET['req_page'] ?? 1));
$total_reqs = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM payments WHERE userid = ?", [$userId]);
$req_total_pages = max(1, (int)ceil($total_reqs / $req_per_page));
if ($req_page > $req_total_pages) $req_page = $req_total_pages;
$req_offset = ($req_page - 1) * $req_per_page;
?>

<div>
    <?php if(isset($msg)) echo "<div class='mb-4'>$msg</div>"; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Request Payout</h3>
            </div>
            <div class="p-5">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-center mb-6">
                    <span class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Available Balance</span>
                    <h2 class="m-0 text-3xl font-bold text-emerald-600">INR <?php echo number_format($available_balance, 2); ?></h2>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <div class="bg-white border border-slate-200 rounded-lg p-2">
                            <span class="block font-semibold">Wallet</span>
                            INR <?php echo number_format($current_balance, 2); ?>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-lg p-2">
                            <span class="block font-semibold">Pending</span>
                            INR <?php echo number_format($pending_reserved, 2); ?>
                        </div>
                    </div>
                </div>
                
                <?php if(!$has_bank_details): ?>
                    <div class="text-center p-5 border border-dashed border-red-500/50 rounded-xl bg-red-500/5">
                        <i class="fa-solid fa-building-columns text-3xl text-red-500 mb-3 block"></i>
                        <h4 class="text-red-600 font-bold m-0 mb-2">Bank Details Missing</h4>
                        <p class="text-sm text-slate-600 mb-4">You must add your bank details before requesting a withdrawal.</p>
                        <a href="profile.php" class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2 px-6 rounded-lg transition-colors">Update Bank Details</a>
                    </div>
                <?php else: ?>
                    <form action="withdraw.php" method="post">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Amount to Withdraw (INR)</label>
                            <input type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" name="amount" min="<?php echo $min_withdraw; ?>" max="<?php echo $available_balance; ?>" step="1" placeholder="Enter amount" required>
                            <small class="block mt-1.5 text-slate-600 text-xs">Minimum withdrawal limit: <strong class="text-white">INR <?php echo $min_withdraw; ?></strong></small>
                        </div>
                        
                        <button type="submit" name="request_withdraw" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg shadow-indigo-500/30 <?php echo ($available_balance < $min_withdraw) ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo ($available_balance < $min_withdraw) ? 'disabled' : ''; ?>>
                            Submit Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Recent Payout Requests</h3>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-600 border-b border-slate-200">
                            <th class="px-5 py-3 font-semibold">#</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-white/5">
                        <?php 
                        $stmtReq = $pdo->prepare("SELECT payment_amount, payment_status, createdtime FROM payments WHERE userid = ? ORDER BY id DESC LIMIT $req_per_page OFFSET $req_offset");
                        $stmtReq->execute([$userId]);
                        $requests = $stmtReq->fetchAll();
                        
                        if(count($requests) > 0) {
                            foreach($requests as $idx => $req) {
                                $row_num = $req_offset + $idx + 1;
                                $date = date('d M, Y', strtotime($req['createdtime']));
                                $amt = number_format($req['payment_amount'], 2);
                                if($req['payment_status'] == 1) {
                                    $status = "<span class='text-emerald-600 font-semibold bg-emerald-500/10 px-2 py-1 rounded-md text-xs border border-emerald-500/20'><i class='fa-solid fa-check-circle mr-1'></i> Paid</span>";
                                } elseif($req['payment_status'] == 0) {
                                    $status = "<span class='text-amber-500 font-semibold bg-amber-500/10 px-2 py-1 rounded-md text-xs border border-amber-500/20'><i class='fa-solid fa-clock mr-1'></i> Pending</span>";
                                } else {
                                    $status = "<span class='text-red-600 font-semibold bg-red-500/10 px-2 py-1 rounded-md text-xs border border-red-500/20'><i class='fa-solid fa-circle-xmark mr-1'></i> Rejected</span>";
                                }
                                echo "<tr class='hover:bg-slate-50 transition-colors'>
                                    <td class='px-5 py-3 text-slate-500 text-xs'>$row_num</td>
                                    <td class='px-5 py-3 text-slate-600'>$date</td>
                                    <td class='px-5 py-3 font-semibold text-slate-800'>INR $amt</td>
                                    <td class='px-5 py-3'>$status</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='px-5 py-8 text-center text-slate-600'>No withdrawal requests yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Pagination for Payout Requests -->
                <?php if ($req_total_pages > 1): ?>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-5 py-4 border-t border-slate-100">
                    <div class="text-xs text-slate-500 font-medium">
                        Page <strong class="text-slate-700"><?php echo $req_page; ?></strong> of <strong class="text-slate-700"><?php echo $req_total_pages; ?></strong> &mdash; <?php echo $total_reqs; ?> total requests
                    </div>
                    <div class="flex items-center gap-1.5">
                        <?php
                        $base_url = '?req_page=';
                        if ($req_page > 1): ?>
                            <a href="<?php echo $base_url . ($req_page - 1); ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all">
                                <i class="fa-solid fa-chevron-left"></i> Prev
                            </a>
                        <?php endif;
                        $range = 2;
                        $rs = max(1, $req_page - $range);
                        $re = min($req_total_pages, $req_page + $range);
                        for ($p = $rs; $p <= $re; $p++): ?>
                            <a href="<?php echo $base_url . $p; ?>" class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-all <?php echo ($p === $req_page) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700'; ?>"><?php echo $p; ?></a>
                        <?php endfor;
                        if ($req_page < $req_total_pages): ?>
                            <a href="<?php echo $base_url . ($req_page + 1); ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-all">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include("layout_footer.php"); ?>
