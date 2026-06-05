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

$page_title = 'General Settings';
$active_nav = 'gensettings';
include("layout_header.php");

// Fetch settings
$query = "SELECT * FROM settings LIMIT 1";
$result = mysqli_query($con, $query);
if ($row = mysqli_fetch_array($result)) {
    $email = $row['email'];
    $wlink = $row['wlink'];
    $ide = $row['invoicedetails'];
    $coname = $row['coname'];
    $fblink = $row['fblink'];
    $tlink = $row['twitterlink'];
    $pid = $row['paypalid']; // Used for PayU Merchant Key
    $sno = $row['sno'];
    $ftrtext = $row['footer'];
    $hdrtext = $row['header'];
    $maintain = $row['maintain'];
    $payzaid = $row['payzaid']; // Used for PayU Salt
    $payu_salt_v2 = $row['solidtrustid'] ?? ''; // Used for optional PayU Salt v2
    
    // SMTP Server configurations
    $smtp_enabled = $row['smtp_enabled'] ?? 0;
    $smtp_host = $row['smtp_host'] ?? '';
    $smtp_port = $row['smtp_port'] ?? 587;
    $smtp_username = $row['smtp_username'] ?? '';
    $smtp_password = $row['smtp_password'] ?? '';
    $smtp_encryption = $row['smtp_encryption'] ?? 'tls';
}

// Fetch current gateway statuses securely
$gateway_paypal_row = mlmp_pdo_fetch($pdo, "SELECT status FROM paymentgateway WHERE id = 1");
$alwdpaypal = $gateway_paypal_row['status'] ?? 0;

?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700">Welcome to the Platform Settings Room. declutter and manage core platform behaviors, PayU payment gateways, and system parameters here.</p>
</div>

<form action="updategensettings.php" method="post" class="flex flex-col gap-6 mb-10">
    <input type="hidden" value="<?php print $sno ?>" name="sno">

    <!-- Card 1: General Platform Configurations -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-gears text-indigo-600"></i> General Platform Settings
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Website Link (Host URL)</label>
                <input type="text" value="<?php print mlmp_escape($wlink); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g., http://www.yourwebsite.com/user/" name="wlink" required>
                <small class="block mt-1.5 text-xs text-slate-600">Must match the absolute URL where the script is hosted. Invalid link may affect website navigation.</small>
            </div>
            
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Company Name</label>
                <input type="text" value="<?php print mlmp_escape($coname); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter Company Name to be used on Invoices" name="coname" required>
            </div>
            
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Company Full Address</label>
                <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" placeholder="Address displayed on generated member invoices" name="codetail" rows="3" required><?php print mlmp_escape($ide); ?></textarea>
            </div>
            
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Admin Notification Email</label>
                <input type="email" value="<?php print mlmp_escape($email); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g., admin@yourwebsite.com" name="coemail" required>
            </div>
        </div>
    </div>

    <!-- Card 2: PayU Payment Gateway settings -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-credit-card text-emerald-600"></i> PayU Gateway Configurations
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Enable PayU Electronic Payments</label>
                <select name="alwdpaypal" class="w-full sm:w-64 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                    <option value="1" <?php if ($alwdpaypal == 1) echo 'selected'; ?> class="bg-white">Yes (Active)</option>
                    <option value="0" <?php if ($alwdpaypal == 0) echo 'selected'; ?> class="bg-white">No (Inactive)</option>
                </select>
            </div>
            
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">PayU Merchant Key</label>
                <input type="text" value="<?php print mlmp_escape($pid); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter PayU Merchant Key" name="payemail">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">PayU Salt v1</label>
                <input type="text" value="<?php print mlmp_escape($payzaid); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter PayU Salt v1" name="payzaid">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">PayU Salt v2</label>
                <input type="text" value="<?php print mlmp_escape($payu_salt_v2); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Optional: enter PayU Salt v2" name="solidid">
            </div>
            
            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4 mt-2">
                <small class="text-xs text-indigo-300 block leading-relaxed">
                    <strong>Web checkout routing:</strong> The registration checkout will automatically validate electronic payments over the PayU secure checkout interface using these parameters. Set status to "Yes" to enable electronic onboarding.
                </small>
            </div>
        </div>
    </div>

    <!-- Card 3: SMTP Config -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-envelope-circle-check text-rose-400"></i> SMTP Mail Server Configuration
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Enable SMTP Email Routing</label>
                <select name="smtp_enabled" class="w-full sm:w-64 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                    <option value="1" <?php if ($smtp_enabled == 1) echo 'selected'; ?> class="bg-white">Yes (Route via SMTP socket)</option>
                    <option value="0" <?php if ($smtp_enabled == 0) echo 'selected'; ?> class="bg-white">No (Native PHP mail() fallback)</option>
                </select>
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">SMTP Host</label>
                <input type="text" value="<?php print mlmp_escape($smtp_host); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. smtp.gmail.com" name="smtp_host">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">SMTP Port</label>
                <input type="number" value="<?php print mlmp_escape($smtp_port); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 587" name="smtp_port">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">SMTP Username / Email</label>
                <input type="text" value="<?php print mlmp_escape($smtp_username); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g., your-email@example.com" name="smtp_username">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">SMTP Password</label>
                <input type="password" value="<?php print mlmp_escape($smtp_password); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter SMTP account password" name="smtp_password">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">SMTP Encryption</label>
                <select name="smtp_encryption" class="w-full sm:w-64 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
                    <option value="tls" <?php if ($smtp_encryption == 'tls') echo 'selected'; ?> class="bg-white">TLS (Port 587)</option>
                    <option value="ssl" <?php if ($smtp_encryption == 'ssl') echo 'selected'; ?> class="bg-white">SSL (Port 465)</option>
                    <option value="none" <?php if ($smtp_encryption == 'none' || empty($smtp_encryption)) echo 'selected'; ?> class="bg-white">None</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card 4: Social Branding -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-circle-nodes text-blue-600"></i> Branding & Social Integration
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Header Brand Text</label>
                <input type="text" value="<?php print mlmp_escape($hdrtext); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Header Logo Brand Text" name="hdrtext" required>
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Footer Copyright Text</label>
                <input type="text" value="<?php print mlmp_escape($ftrtext); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Footer Copyright Text (displayed inside sidebar)" name="ftrtext" required>
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Facebook Page Link</label>
                <input type="text" value="<?php print mlmp_escape($fblink); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g., http://facebook.com/username" name="fblink">
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Twitter Link</label>
                <input type="text" value="<?php print mlmp_escape($tlink); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g., http://twitter.com/username" name="twitterlink">
            </div>
        </div>
    </div>

    <!-- Card 5: Website Maintenance Controls -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-hammer text-amber-600"></i> System Maintenance Controls
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Current Website Mode Status</label>
                <input type="text" value="<?php 
                    if ($maintain==0) { print "Website is fully LIVE for users"; } 
                    else if ($maintain==1) { print "Maintenance: Member login is DISABLED"; } 
                    else if ($maintain==2) { print "Maintenance: Member registration is DISABLED"; } 
                    else if ($maintain==3) { print "Maintenance: Both login & registration are DISABLED"; }
                ?>" class="w-full bg-black/20 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-600 text-sm focus:outline-none cursor-not-allowed" disabled>
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Update Website Status</label>
                <select name="maintain" class="w-full sm:w-80 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
                    <option value="0" <?php if ($maintain == 0) echo 'selected'; ?> class="bg-white">Go Live (Standard Operations)</option>
                    <option value="1" <?php if ($maintain == 1) echo 'selected'; ?> class="bg-white">Disable User Logins</option>
                    <option value="2" <?php if ($maintain == 2) echo 'selected'; ?> class="bg-white">Disable User Signups</option>
                    <option value="3" <?php if ($maintain == 3) echo 'selected'; ?> class="bg-white">Disable Both Logins & Signups</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Hidden payment settings submitted seamlessly -->
    <input type="hidden" name="alwdpayment" value="2">
    <input type="hidden" name="payzaid_old" value="<?php echo mlmp_escape($payzaid); ?>">
    <input type="hidden" name="solidbuttonid" value="">
    <input type="hidden" name="alwdpayza" value="0">
    <input type="hidden" name="alwdsolid" value="0">

    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-3 px-6 text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all flex items-center justify-center gap-2 mt-2 w-full md:w-auto self-start">
        <i class="fa-solid fa-floppy-disk"></i> Save All Platform Configurations
    </button>
</form>

<?php include("layout_footer.php"); ?>
