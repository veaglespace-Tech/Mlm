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

$page_title = 'Packages Settings';
$active_nav = 'pacsettings';
include("layout_header.php");
ensure_package_schema($pdo);

// Fetch all packages for the grid
$packages = mlmp_pdo_fetch_all($pdo, "SELECT * FROM packages ORDER BY id ASC");
?>

<?php if (!empty($_SESSION['package_message'])) { ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-circle-info"></i> <?php print $_SESSION['package_message']; unset($_SESSION['package_message']); ?>
</div>
<?php } ?>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Use the Manage Packages tab to edit or toggle package status. Create new packages under the Create Package tab. Set referral levels to 0 to disable them.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-0 border-b border-slate-200 bg-slate-50">
        <ul class="flex overflow-x-auto gap-2 no-scrollbar nav-tabs">
            <li class="active"><a href="#manage" class="block px-4 py-4 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-layer-group mr-1.5"></i> Manage Packages</a></li>
            <li><a href="#create" class="block px-4 py-4 text-sm font-bold text-slate-600 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-plus mr-1.5"></i> Create Package</a></li>
        </ul>
    </div>
    
    <div class="p-6 bg-slate-50">
        <div class="tab-content">
            
            <!-- Manage Packages Tab -->
            <div class="tab-pane active" id="manage">
                <?php if (count($packages) === 0): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
                        <i class="fa-solid fa-box-open text-4xl text-slate-400 mb-3"></i>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">No Packages Found</h3>
                        <p class="text-sm text-slate-500">You haven't created any MLM packages yet. Go to the Create Package tab to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($packages as $pkg): 
                            $id = $pkg['id'];
                            $pname = $pkg['name'];
                            $price = $pkg['price'];
                            $tax = $pkg['tax'];
                            $total = $price + $tax;
                            $cur = $pkg['currency'];
                            $active = $pkg['active'];
                            $validity = $pkg['validity'] ?? 0;
                            $bin_pct = $pkg['binary_percent'] ?? 0;
                            $spon_pct = $pkg['sponsor_percent'] ?? 0;
                        ?>
                            <div class="bg-white border <?php echo $active ? 'border-indigo-200' : 'border-slate-200'; ?> rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden relative">
                                <?php if($active): ?>
                                    <div class="absolute top-0 right-0 w-16 h-16 overflow-hidden">
                                        <div class="absolute transform rotate-45 bg-emerald-500 text-white text-[10px] font-bold py-1 right-[-35px] top-[15px] w-[120px] text-center shadow-sm">ACTIVE</div>
                                    </div>
                                <?php else: ?>
                                    <div class="absolute top-0 right-0 w-16 h-16 overflow-hidden">
                                        <div class="absolute transform rotate-45 bg-red-500 text-white text-[10px] font-bold py-1 right-[-35px] top-[15px] w-[120px] text-center shadow-sm">INACTIVE</div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-5 border-b border-slate-100 flex-1">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-lg <?php echo $active ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500'; ?> flex items-center justify-center text-lg shadow-sm">
                                            <i class="fa-solid fa-cube"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-lg m-0"><?php echo mlmp_escape($pname); ?></h3>
                                            <div class="text-xs font-semibold text-slate-500">ID: #<?php echo $id; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-slate-50 rounded-xl p-4 mb-4 border border-slate-100">
                                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-1">Total Price</div>
                                        <div class="text-2xl font-black text-slate-800 flex items-baseline gap-1">
                                            <span class="text-lg"><?php echo mlmp_escape($cur); ?></span> <?php echo number_format($total, 2); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 flex justify-between">
                                            <span>Base: <?php echo number_format($price, 2); ?></span>
                                            <span>Tax: <?php echo number_format($tax, 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3 mb-2">
                                        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Binary Match</div>
                                            <div class="text-sm font-bold text-slate-700"><?php echo mlmp_escape($bin_pct); ?>%</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Direct Sponsor</div>
                                            <div class="text-sm font-bold text-slate-700"><?php echo mlmp_escape($spon_pct); ?>%</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center px-1 text-xs font-medium text-slate-500 mt-3">
                                        <span><i class="fa-regular fa-clock mr-1"></i> Validity: <?php echo $validity; ?> days</span>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                                    <a href="updatepck.php?upackage=<?php echo $id; ?>" class="flex-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 py-2 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                    </a>
                                    
                                    <form action="togglepackage.php" method="post" class="flex-1">
                                        <input type="hidden" name="pkg_id" value="<?php echo $id; ?>">
                                        <?php if($active): ?>
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-2 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
                                                <i class="fa-solid fa-power-off mr-1"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 py-2 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
                                                <i class="fa-solid fa-check mr-1"></i> Activate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Create Package Tab -->
            <div class="tab-pane" id="create">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 max-w-4xl mx-auto">
                    <form action="createpac.php" method="post" class="flex flex-col gap-6">
                        
                        <!-- Core Details -->
                        <div class="mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-800 m-0"><i class="fa-solid fa-info-circle text-indigo-500 mr-1.5"></i> Core Package Info</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Package Name</label>
                                <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter Package Name" name="pckname" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Plan Validity (Days)</label>
                                <input type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 365" name="renewdays" required min="1">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Package Details</label>
                            <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" rows="2" placeholder="Intro of package features..." name="pckdetail" required></textarea>
                        </div>
                        
                        <!-- Financials -->
                        <div class="mt-4 mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-800 m-0"><i class="fa-solid fa-sack-dollar text-emerald-500 mr-1.5"></i> Pricing & Thresholds</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Base Price</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="pckprice" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Tax Amount</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="0.00" name="pcktax" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Currency</label>
                                <select name="currency" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" required>
                                    <?php 
                                    $query = "SELECT id, name, code FROM currency"; 
                                    $result = mysqli_query($con, $query);
                                    while ($row = mysqli_fetch_array($result)) {
                                        $curname = $row['name'];
                                        $curcode = $row['code'];
                                        print "<option value='$curcode' class='bg-white'>$curname - $curcode</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Min User Payout Limit</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 50" name="pckmpay" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Signup Welcome Bonus</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 10 (or 0)" name="pcksbonus" required>
                            </div>
                        </div>

                        <div class="mt-4 mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-800 m-0"><i class="fa-solid fa-percent text-blue-500 mr-1.5"></i> Binary & Sponsor Percentages</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Binary Match Commission (%)</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="30" name="binary_percent" required value="30">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Direct Sponsor Commission (%)</label>
                                <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="10" name="sponsor_percent" required value="10">
                            </div>
                        </div>

                        <div class="mt-4 mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-800 m-0"><i class="fa-solid fa-shield-halved text-red-500 mr-1.5"></i> Daily Capping Limits</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Daily Binary Capping Status</label>
                                <select name="capping_status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]" onchange="document.getElementById('create_capping_limit_container').style.display = this.value == '1' ? 'block' : 'none';">
                                    <option value="1" class="bg-white">ON — Limit Daily Pairs</option>
                                    <option value="0" class="bg-white">OFF — Unlimited (Danger!)</option>
                                </select>
                            </div>
                            <div id="create_capping_limit_container">
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Daily Capping Limit (Pairs)</label>
                                <input type="number" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="2" name="capping_limit" required value="2">
                            </div>
                        </div>

                        <!-- Matrix Settings -->
                        <div class="mt-4 mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-800 m-0"><i class="fa-solid fa-sitemap text-purple-500 mr-1.5"></i> Matrix Levels (1 to 20)</h4>
                            <p class="text-xs text-slate-500 mt-1 mb-0">Set commission amounts for each upline level. Set to 0 if not used.</p>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <?php for ($l = 1; $l <= 20; $l++) { ?>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Level <?php echo $l; ?></label>
                                    <input type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-sm font-semibold focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" name="lev<?php echo $l; ?>" value="0" required>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <div class="border-t border-slate-200 mt-2 pt-4">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none py-3 px-8 text-sm font-bold rounded-xl shadow-md transition-all w-full md:w-auto md:float-right">
                                <i class="fa-solid fa-check-circle mr-1.5"></i> Create New Package
                            </button>
                            <div class="clear-both"></div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.tab-pane { display: none; }
.tab-pane.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.nav-tabs li { cursor: pointer; list-style: none; }
.nav-tabs li.active a { color: #4f46e5; border-bottom-color: #4f46e5; background: #f8fafc; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll(".nav-tabs a");
    const panes = document.querySelectorAll(".tab-pane");

    tabs.forEach(tab => {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            tabs.forEach(t => t.parentElement.classList.remove("active"));
            panes.forEach(p => p.classList.remove("active"));

            this.parentElement.classList.add("active");
            const targetId = this.getAttribute("href");
            const targetPane = document.querySelector(targetId);
            if (targetPane) targetPane.classList.add("active");
        });
    });
});
</script>

<?php include("layout_footer.php"); ?>
