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
$has_bank_details = ($user['bankname'] !== 'Not Available' && $user['accountno'] != 0 && $user['ifsccode'] !== 'Not Available');

// Process Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdraw'])) {
    $req_amount = (float)$_POST['amount'];
    
    if (!$has_bank_details) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Please update your bank details first.</div>";
    } elseif ($req_amount < $min_withdraw) {
        $msg = "<div class='bg-amber-500/10 border border-amber-500/20 text-amber-500 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-triangle-exclamation'></i> Minimum withdrawal is INR $min_withdraw.</div>";
    } elseif ($req_amount > $current_balance) {
        $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Insufficient balance. You only have INR $current_balance.</div>";
    } else {
        // Proceed with withdrawal
        try {
            $pdo->beginTransaction();
            
            // Deduct balance
            $stmtDeduct = $pdo->prepare("UPDATE affiliateuser SET tamount = tamount - ? WHERE Id = ? AND tamount >= ?");
            $stmtDeduct->execute([$req_amount, $userId, $req_amount]);
            
            if ($stmtDeduct->rowCount() > 0) {
                // Insert request
                $itemid = "WREQ-" . time() . "-" . rand(100, 999);
                $stmtInsert = $pdo->prepare("INSERT INTO payments (userid, payment_amount, payment_status, itemid, createdtime) VALUES (?, ?, 0, ?, NOW())");
                $stmtInsert->execute([$userId, $req_amount, $itemid]);
                
                $pdo->commit();
                $current_balance -= $req_amount;
                $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Withdrawal request for INR $req_amount submitted successfully. It is pending admin approval.</div>";
            } else {
                $pdo->rollBack();
                $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-triangle-exclamation'></i> Failed to process withdrawal. Concurrency issue detected.</div>";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-triangle-exclamation'></i> System error occurred.</div>";
        }
    }
}
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
                    <h2 class="m-0 text-3xl font-bold text-emerald-600">INR <?php echo number_format($current_balance, 2); ?></h2>
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
                            <input type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" name="amount" min="<?php echo $min_withdraw; ?>" max="<?php echo $current_balance; ?>" step="1" placeholder="Enter amount" required>
                            <small class="block mt-1.5 text-slate-600 text-xs">Minimum withdrawal limit: <strong class="text-white">INR <?php echo $min_withdraw; ?></strong></small>
                        </div>
                        
                        <button type="submit" name="request_withdraw" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-4 rounded-lg transition-colors shadow-lg shadow-indigo-500/30 <?php echo ($current_balance < $min_withdraw) ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo ($current_balance < $min_withdraw) ? 'disabled' : ''; ?>>
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
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700 divide-y divide-white/5">
                        <?php 
                        $stmtReq = $pdo->prepare("SELECT payment_amount, payment_status, createdtime FROM payments WHERE userid = ? ORDER BY id DESC LIMIT 5");
                        $stmtReq->execute([$userId]);
                        $requests = $stmtReq->fetchAll();
                        
                        if(count($requests) > 0) {
                            foreach($requests as $req) {
                                $date = date('d M, Y', strtotime($req['createdtime']));
                                $amt = number_format($req['payment_amount'], 2);
                                if($req['payment_status'] == 1) {
                                    $status = "<span class='text-emerald-600 font-semibold bg-emerald-500/10 px-2 py-1 rounded-md text-xs border border-emerald-500/20'><i class='fa-solid fa-check-circle mr-1'></i> Paid</span>";
                                } else {
                                    $status = "<span class='text-amber-500 font-semibold bg-amber-500/10 px-2 py-1 rounded-md text-xs border border-amber-500/20'><i class='fa-solid fa-clock mr-1'></i> Pending</span>";
                                }
                                echo "<tr class='hover:bg-slate-50 transition-colors'>
                                    <td class='px-5 py-3 text-slate-600'>$date</td>
                                    <td class='px-5 py-3 font-semibold text-slate-800'>INR $amt</td>
                                    <td class='px-5 py-3'>$status</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='px-5 py-8 text-center text-slate-600'>No withdrawal requests yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("layout_footer.php"); ?>
