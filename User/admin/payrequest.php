<?php
include_once ("z_db.php");
// Inialize session
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

$page_title = 'Payment Requests';
$active_nav = 'payrequest';
include("layout_header.php");

// Fetch payment requests
$requests = mlmp_pdo_fetch_all($pdo, "SELECT * FROM payments ORDER BY id DESC");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Clicking the "Paid" button will NOT automatically execute a bank wire. It only updates the system status. Please initiate the manual bank transfer separately and verify the member's referral status before dispatching payouts.</p>
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
                                            <a href="makepayment.php?payid=<?php echo urlencode($prid); ?>" class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Mark Paid</a>
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

<?php include("layout_footer.php"); ?>
