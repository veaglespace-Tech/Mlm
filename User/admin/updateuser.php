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
$toupdate = $_GET['username'] ?? '';

// Fetch details for the user to be updated
$rowUser = mlmp_pdo_fetch($pdo, "SELECT * FROM affiliateuser WHERE username = ?", [$toupdate]);
if ($rowUser) {
    $id = $rowUser['Id'] ?? '';
    $username = $rowUser['username'] ?? '';
    $pass = $rowUser['password'] ?? '';
    $address = $rowUser['address'] ?? '';
    $fname = $rowUser['fname'] ?? '';
    $email = $rowUser['email'] ?? '';
    $mobile = $rowUser['mobile'] ?? '';
    $active = $rowUser['active'] ?? '';
    $doj = $rowUser['doj'] ?? '';
    $country = $rowUser['country'] ?? '';
    $ear = $rowUser['tamount'] ?? 0;
    $ref = $rowUser['referedby'] ?? '';
    $pck = $rowUser['pcktaken'] ?? '';
    $lprofile = $rowUser['launch'] ?? '';
    
    if ($active == 1) {
        $status = "Active/Paid";
    } else if ($active == 0) {
        $status = "UnActive/Unpaid";
    } else {
        $status = "Unknown";
    }
    
    $pckname = '';
    $pckprice = 0;
    $pcktax = 0;
    $pckcur = '';
    $pcksbonus = 0;
    if ($pck !== '') {
        $r = mlmp_pdo_fetch($pdo, "SELECT * FROM packages WHERE id = ?", [$pck]);
        if ($r) {
            $pckid = $r['id'] ?? '';
            $pckname = $r['name'] ?? '';
            $pckprice = $r['price'] ?? 0;
            $pcktax = $r['tax'] ?? 0;
            $pckcur = $r['currency'] ?? '';
            $pcksbonus = $r['sbonus'] ?? 0;
        }
    }
    $total = (float)$pckprice + (float)$pcktax;
} else {
    $id = '';
    $username = '';
    $pass = '';
    $address = '';
    $fname = '';
    $email = '';
    $mobile = '';
    $active = '';
    $doj = '';
    $country = '';
    $ear = 0;
    $ref = '';
    $pck = '';
    $lprofile = '';
    $status = 'Unknown';
    $pckname = '';
    $total = 0;
}

$page_title = "Edit Member Settings";
$active_nav = "users";
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

include("layout_header.php");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Member Settings Customization:</strong> Customize registration parameters, credentials, MLM earnings, and matrix package bindings securely. Ensure all modifications align with platform policies.</p>
</div>

<!-- Quick Stats Header Card -->
<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-600 text-xl shadow-lg shadow-indigo-500/10">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 m-0"><?php echo mlmp_escape($fname); ?></h2>
                <p class="text-sm text-slate-600 m-0 mt-1">Username: <strong class="text-slate-700">@<?php echo mlmp_escape($username); ?></strong> &bull; Joined: <?php echo mlmp_escape($doj); ?></p>
            </div>
        </div>
        <div class="flex gap-3">
            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border <?php echo ($active == 1) ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-red-500/10 text-red-600 border-red-500/20'; ?>">
                Status: <?php echo $status; ?>
            </span>
            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                Package: <?php echo $pckname ? mlmp_escape($pckname) : 'None'; ?>
            </span>
        </div>
    </div>
</div>

<form action="updateusersettings.php" method="post" class="flex flex-col gap-6 mb-10">
    <input type="hidden" value="<?php print mlmp_escape($id); ?>" name="pckmainid">
    <input type="hidden" value="<?php print mlmp_escape($toupdate); ?>" name="username">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Left Panel: Core Personal Settings -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-user text-indigo-600"></i> Profile Credentials & Contact Details
            </div>
            <div class="p-6 flex flex-col gap-4">
                
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Status Selector</label>
                    <select name="act" class="w-full sm:w-64 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                        <option value="1" <?php if ($active == 1) echo 'selected'; ?> class="bg-white">1 (Active / Paid)</option>
                        <option value="0" <?php if ($active == 0) echo 'selected'; ?> class="bg-white">0 (Inactive / Unpaid)</option>
                    </select>
                    <small class="block mt-1.5 text-xs text-slate-600">0 prevents login and disables active network metrics.</small>
                </div>
                
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Member Name</label>
                    <input type="text" value="<?php print mlmp_escape($fname); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter full registration name" name="fname" required>
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">E-Mail Address</label>
                    <input type="email" value="<?php print mlmp_escape($email); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="member@domain.com" name="email" required>
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Mobile Number</label>
                    <input type="text" value="<?php print mlmp_escape($mobile); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter contact number" name="mobile">
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Country</label>
                    <input type="text" value="<?php print mlmp_escape($country); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter registration country" name="country">
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Address Info</label>
                    <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" placeholder="Enter primary residential address" name="address" rows="3"><?php print mlmp_escape($address); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right Panel: Network & MLM Settings -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-sitemap text-emerald-600"></i> MLM Alignment & System Values
                </div>
                <div class="p-6 flex flex-col gap-4">
                    
                    <div class="mb-2">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sponsor / Referred By</label>
                        <input type="text" value="<?php print mlmp_escape($ref); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter sponsor username" name="refer" required>
                        <small class="block mt-1.5 text-xs text-slate-600">Must match a valid, existing platform username.</small>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">MLM Package Association</label>
                        <select name="package" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                            <?php
                            $packages = mlmp_pdo_fetch_all($pdo, "SELECT id, name, price, currency, tax FROM packages WHERE active = 1");
                            foreach ($packages as $prow) {
                                $pid = $prow['id'] ?? '';
                                $pname_opt = $prow['name'] ?? '';
                                $pprice = $prow['price'] ?? 0;
                                $pcur = $prow['currency'] ?? '';
                                $ptax = $prow['tax'] ?? 0;
                                $ptotal = (float)$pprice + (float)$ptax;
                                $selected = ($pid == $pck) ? 'selected' : '';
                                print "<option value='" . mlmp_escape($pid) . "' $selected class='bg-white'>" . mlmp_escape($pname_opt) . " | Price - " . mlmp_escape($pcur) . " " . mlmp_escape($ptotal) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cumulative Earnings</label>
                        <input type="number" step="0.01" value="<?php print mlmp_escape($ear); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="earnings" required>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Administrative Password Override (Optional)</label>
                        <input type="password" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Leave completely blank to retain current password" name="password" autocomplete="new-password">
                        <small class="block mt-1.5 text-xs text-slate-600">For security, keep this empty unless member requested password recovery.</small>
                    </div>
                </div>
            </div>

            <!-- Danger Zone panel inside right column -->
            <div class="m-5 p-5 border border-dashed border-red-500/30 bg-red-500/5 rounded-xl">
                <h4 class="m-0 mb-2 text-red-600 text-sm font-bold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</h4>
                <p class="m-0 mb-4 text-xs text-slate-600 leading-relaxed">Completely delete this user account from the system. This action is irreversible and can break downstream matrix lines if active referrals exist.</p>
                <button type="button" onclick="confirmDeleteUser()" class="bg-red-600 hover:bg-red-500 text-white border-none py-2 px-4 text-xs font-bold rounded-lg shadow-lg shadow-red-500/20 transition-all flex items-center gap-2">
                    Delete Platform Member Account
                </button>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl">
        <div class="p-5 flex gap-3 justify-end items-center">
            <a href="users.php" class="bg-slate-700 hover:bg-slate-600 text-white border-none py-2.5 px-5 text-sm font-bold rounded-xl transition-all flex items-center gap-2 shadow-lg"><i class="fa-solid fa-ban"></i> Cancel</a>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white border-none py-2.5 px-5 text-sm font-bold rounded-xl transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/20"><i class="fa-solid fa-circle-check"></i> Save Modifications</button>
        </div>
    </div>
</form>

<script>
function confirmDeleteUser() {
    Swal.fire({
        title: 'Are you absolutely sure?',
        text: "You are about to delete user '<?php echo mlmp_escape($username); ?>'. This action will purge all associated MLM records!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: 'rgba(255,255,255,0.1)',
        confirmButtonText: 'Yes, delete user!',
        background: '#16181f',
        color: '#e2e8f0'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'deleteuser.php?username=<?php echo urlencode($username); ?>';
        }
    });
}
</script>

<?php
include("layout_footer.php");
?>
