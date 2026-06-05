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

$page_title = 'Profile Settings';
$active_nav = 'profile';
include("layout_header.php");

// Fetch active admin details
$admin_user = mlmp_pdo_fetch($pdo, "SELECT fname, email, address, country, bankname, accountname, accountno, accounttype, ifsccode FROM affiliateuser WHERE username = ?", [$_SESSION['adminidusername']]);
$name = $admin_user['fname'] ?? '';
$email = $admin_user['email'] ?? '';
$address = $admin_user['address'] ?? '';
$country = $admin_user['country'] ?? '';
$bname = $admin_user['bankname'] ?? '';
$accnamee = $admin_user['accountname'] ?? '';
$accnumber = $admin_user['accountno'] ?? '';
$acctyppe = (int)($admin_user['accounttype'] ?? 0);
$ifsc = $admin_user['ifsccode'] ?? '';

$settings_row = mlmp_pdo_fetch($pdo, "SELECT wlink FROM settings WHERE sno = 0 LIMIT 1");
$admin_referral_url = mlmp_build_referral_url($settings_row['wlink'] ?? '', $_SESSION['adminidusername']);
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Profile & Credential Update:</strong> Keep your official name, email routing address, and banking credentials correct for payouts and platform identification.</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
        My Referral Link
    </div>
    <div class="p-6">
        <p class="mt-0 mb-3 text-slate-600 text-sm">
            Share this invite URL with anyone who should register under your account. New signups through this link will automatically prefill your username as the sponsor.
        </p>
        <div class="flex flex-wrap gap-0">
            <input type="text" id="refUrl" value="<?php echo mlmp_escape($admin_referral_url); ?>" readonly
                   class="flex-1 min-w-[240px] bg-slate-50 border border-slate-200 rounded-l-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none">
            <button type="button" onclick="copyRefUrl()"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-2.5 px-6 text-sm font-bold rounded-r-lg transition-all whitespace-nowrap">
                <i class="fa-solid fa-copy"></i> Copy Link
            </button>
        </div>
        <div id="copySuccess" class="hidden mt-2 text-emerald-500 text-xs font-bold">
            <i class="fa-solid fa-circle-check"></i> Copied to clipboard!
        </div>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
        General & Financial Payout Profile
    </div>
    <div class="p-6">
        <form action="profileupdate.php" method="post" class="flex flex-col gap-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Name</label>
                    <input type="text" value="<?php echo mlmp_escape($name); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Full Name" name="fullname" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Country</label>
                    <input type="text" value="<?php echo mlmp_escape($country); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Country" name="country" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">E-Mail Address</label>
                    <input type="email" value="<?php echo mlmp_escape($email); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="E-Mail" name="email" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Physical Address</label>
                    <input type="text" value="<?php echo mlmp_escape($address); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Address" name="address" required>
                </div>
            </div>

            <div class="my-2 border-t border-slate-200"></div>
            <h4 class="mb-2 font-bold text-indigo-600"><i class="fa-solid fa-building-columns"></i> Financial Payout Bank Credentials</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Type</label>
                    <select name="acctype" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                        <option value='0' <?php if($acctyppe === 0) echo 'selected'; ?> class="bg-white">Select Type</option>	  
                        <option value='1' <?php if($acctyppe === 1) echo 'selected'; ?> class="bg-white">Current Account</option>
                        <option value='2' <?php if($acctyppe === 2) echo 'selected'; ?> class="bg-white">Savings Account</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Bank Name</label>
                    <input type="text" value="<?php echo mlmp_escape($bname); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Bank Name" name="bankname" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Holder Name</label>
                    <input type="text" value="<?php echo mlmp_escape($accnamee); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Account Holder Name" name="accname" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Number</label>
                    <input type="text" value="<?php echo mlmp_escape($accnumber); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Bank Account Number" name="accno" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">IFSC Code</label>
                <input type="text" value="<?php echo mlmp_escape($ifsc); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="IFSC Code" name="ifsccode" required>
            </div>

            <div class="my-2 border-t border-slate-200"></div>
            <h4 class="mb-2 font-bold text-red-600"><i class="fa-solid fa-lock"></i> Change Administrative Password</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New Password</label>
                    <input type="password" value="" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Leave blank to keep current password" name="p1">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Repeat New Password</label>
                    <input type="password" value="" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Repeat only if changing password" name="p2">
                </div>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-3 px-6 text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all w-full mt-6">I Have Filled And Checked All Details. Update My Profile Now.</button>
        </form>
    </div>
</div>

<script>
function copyRefUrl() {
    var input = document.getElementById('refUrl');
    input.select();
    input.setSelectionRange(0, 99999);
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value);
    } else {
        document.execCommand('copy');
    }
    var success = document.getElementById('copySuccess');
    success.style.display = 'block';
    setTimeout(function () {
        success.style.display = 'none';
    }, 2500);
}
</script>

<?php include("layout_footer.php"); ?>
