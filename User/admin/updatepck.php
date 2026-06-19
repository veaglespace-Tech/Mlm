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

ensure_package_schema($pdo);

$upid = (int)($_POST['upackage'] ?? $_GET['upackage'] ?? 0);
if ($upid <= 0) {
    $_SESSION['package_message'] = "Invalid package selected.";
    header("Location: pacsettings.php");
    exit;
}

// Fetch package details safely using secure PDO helper
$row = mlmp_pdo_fetch($pdo, "SELECT * FROM packages WHERE id = ?", [$upid]);
if (!$row) {
    $_SESSION['package_message'] = "Selected package does not exist.";
    header("Location: pacsettings.php");
    exit;
}

$pname = $row['name'] ?? '';
$pdetail = $row['details'] ?? '';
$pprice = $row['price'] ?? 0;
$pcurid = $row['currency'] ?? '';
$pckmpay = $row['mpay'] ?? 0;
$pcktax = $row['tax'] ?? 0;
$pcksbonus = $row['sbonus'] ?? 0;
$pckactive = $row['active'] ?? 0;
$validity = $row['validity'] ?? 0;
$binary_percent = $row['binary_percent'] ?? 30;
$sponsor_percent = $row['sponsor_percent'] ?? 10;
$capping_limit = $row['capping_limit'] ?? 10;
$capping_status = $row['capping_status'] ?? 1;

$levels = [];
for ($i = 1; $i <= 20; $i++) {
    $levels[$i] = $row["level{$i}"] ?? 0;
}

$page_title = "Edit MLM Package Settings";
$active_nav = "pacsettings";
$extra_head = '';

include("layout_header.php");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700">
        <i class="fa-solid fa-circle-info text-blue-600 mr-2"></i>
        <strong>Configuration Instructions:</strong> All package details are mandatory. Set any matrix level commission to <strong>0</strong> to disable that level's earnings. All amounts must be formatted as numeric integers or float numbers.
    </p>
</div>

<!-- Header Detail Panel -->
<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-500/15 flex items-center justify-center text-xl text-indigo-600">
                <i class="fa-solid fa-cubes"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 m-0"><?php echo mlmp_escape($pname); ?> Settings</h2>
                <p class="text-sm text-slate-600 mt-1 mb-0">Configure base pricing, taxes, user thresholds, and active commission schedules.</p>
            </div>
        </div>
        <div>
            <span class="inline-block px-4 py-1.5 rounded-lg text-xs font-bold border <?php echo ($pckactive == 1) ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-red-500/10 text-red-600 border-red-500/20'; ?>">
                <?php echo ($pckactive == 1) ? 'Active Product Package' : 'Inactive / Disabled'; ?>
            </span>
        </div>
    </div>
</div>

<form action="updatepcksettings.php" method="post" class="flex flex-col gap-6 mb-10">
    <input type="hidden" value="<?php echo mlmp_escape($upid); ?>" name="pckmainid">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Card 1: Core Package Settings -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-gear text-slate-600"></i> Basic Package Details
            </div>
            <div class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Package Active Status</label>
                    <select name="pckact" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
                        <option value="1" <?php if ($pckactive == 1) echo 'selected'; ?> class="bg-white">1 — Active & List in Shop</option>
                        <option value="0" <?php if ($pckactive == 0) echo 'selected'; ?> class="bg-white">0 — Inactive / Hidden</option>
                    </select>
                    <small class="block mt-1 text-xs text-slate-600">Setting status to inactive hides this package from checkout and signups.</small>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Package Name</label>
                    <input type="text" value="<?php echo mlmp_escape($pname); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter package name (e.g. VIP Member)" name="pckname" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Package Details / Intro</label>
                    <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" placeholder="Brief summary of package features..." name="pckdetail" rows="3" required><?php echo mlmp_escape($pdetail); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Validity Period (Days)</label>
                    <input type="number" value="<?php echo mlmp_escape($validity); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 30" name="renewdays" required min="1">
                    <small class="block mt-1 text-xs text-slate-600">Enter number of active days (e.g., 30 for one month, 365 for a year). Must be greater than 0.</small>
                </div>
            </div>
        </div>

        <!-- Card 2: Financial Configuration -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-sack-dollar text-emerald-600"></i> Pricing & Financials
            </div>
            <div class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Base Price</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($pprice); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="pckprice" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Tax Amount</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($pcktax); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="pcktax" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Minimum User Payout Threshold</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($pckmpay); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="50.00" name="pckmpay" required>
                    <small class="block mt-1 text-xs text-slate-600">Minimum accumulated commissions needed before a user can submit a withdrawal request.</small>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Signup Welcome Bonus</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($pcksbonus); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="pcksbonus" required>
                    <small class="block mt-1 text-xs text-slate-600">Initial signup reward credited directly to user wallet. Set to 0 to disable.</small>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Binary Match Commission (%)</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($binary_percent); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="30" name="binary_percent" required>
                    <small class="block mt-1 text-xs text-slate-600">Percentage of Base Price awarded per matched pair.</small>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Direct Sponsor Commission (%)</label>
                    <input type="number" step="0.01" value="<?php echo mlmp_escape($sponsor_percent); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="10" name="sponsor_percent" required>
                    <small class="block mt-1 text-xs text-slate-600">Percentage of Base Price awarded to direct referrer.</small>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Daily Binary Capping Status</label>
                    <select name="capping_status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px] mb-2" onchange="document.getElementById('capping_limit_container').style.display = this.value == '1' ? 'block' : 'none';">
                        <option value="1" <?php if ($capping_status == 1) echo 'selected'; ?> class="bg-white">ON — Limit Daily Pairs</option>
                        <option value="0" <?php if ($capping_status == 0) echo 'selected'; ?> class="bg-white">OFF — Unlimited (Danger!)</option>
                    </select>

                    <div id="capping_limit_container" style="display: <?php echo $capping_status == 1 ? 'block' : 'none'; ?>;">
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Daily Capping Limit (Pairs)</label>
                        <input type="number" value="<?php echo mlmp_escape($capping_limit); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="2" name="capping_limit" required>
                        <small class="block mt-1 text-xs text-slate-600">Max number of pairs a user can earn from per day. Extra pairs flush.</small>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Settlement Currency</label>
                    <select name="currency" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
                        <?php 
                        $cur_query = mlmp_pdo_fetch_all($pdo, "SELECT id, name, code FROM currency"); 
                        foreach($cur_query as $cur) {
                            $curname = $cur['name'] ?? '';
                            $curcode = $cur['code'] ?? '';
                            $selected = ($curcode === $pcurid) ? " selected" : "";
                            echo "<option value='" . mlmp_escape($curcode) . "'$selected class='bg-white'>" . mlmp_escape($curname) . " — (" . mlmp_escape($curcode) . ")</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: 20-Level Network Commission Grid -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-sitemap text-indigo-600"></i> Matrix Referral Commission Structure (Levels 1 — 20)
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-600 mb-6 mt-0">
                Specify the payout credited to higher-level sponsors when a new registration is processed. Use integer or decimal values.
            </p>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php for ($i = 1; $i <= 20; $i++): ?>
                    <div class="bg-black/20 border border-slate-200 rounded-xl p-3 flex flex-col gap-2 hover:border-indigo-500/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="bg-indigo-500/20 text-indigo-600 text-[11px] font-bold px-2 py-0.5 rounded">Level <?php echo $i; ?></span>
                            <span class="text-xs text-slate-600 font-semibold"><i class="fa-solid fa-users-viewfinder"></i> Sponsor</span>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600 font-bold"><?php echo mlmp_escape($pcurid); ?></span>
                            <input type="number" step="0.01" value="<?php echo mlmp_escape($levels[$i]); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-12 pr-3 py-2 text-slate-800 text-sm font-bold focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" name="lev<?php echo $i; ?>" required min="0">
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons Panel -->
    <div class="bg-black/20 border border-slate-200 rounded-2xl p-4 flex flex-wrap justify-end gap-4">
        <a href="pacsettings.php" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 py-2.5 px-6 text-sm font-bold rounded-lg transition-colors flex items-center gap-2">
            <i class="fa-solid fa-ban"></i> Cancel
        </a>
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white border-none py-2.5 px-6 text-sm font-bold rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> Save Package Settings
        </button>
    </div>
</form>

<?php
include("layout_footer.php");
?>


