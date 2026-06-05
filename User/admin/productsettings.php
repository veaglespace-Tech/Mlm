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

$page_title = 'Product Settings';
$active_nav = 'productsettings';
include("layout_header.php");

// Fetch all products for the grid
$products = mlmp_pdo_fetch_all($pdo, "SELECT * FROM products ORDER BY id ASC");
?>

<?php if (!empty($_SESSION['product_message'])) { ?>
<div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-circle-info"></i> <?php print $_SESSION['product_message']; unset($_SESSION['product_message']); ?>
</div>
<?php } ?>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Use the Manage Products tab to edit or toggle product status. Create new products under the Create Product tab. You can configure FontAwesome classes and Emojis to represent products beautifully in the store.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-0 border-b border-slate-200 bg-slate-50">
        <ul class="flex overflow-x-auto gap-2 no-scrollbar nav-tabs">
            <li class="active"><a href="#manage" class="block px-4 py-4 text-sm font-bold text-slate-700 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-boxes-stacked mr-1.5"></i> Manage Products</a></li>
            <li><a href="#create" class="block px-4 py-4 text-sm font-bold text-slate-700 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-plus mr-1.5"></i> Create Product</a></li>
        </ul>
    </div>
    <div class="p-6 bg-slate-50">
        <div class="tab-content">
            
            <!-- Manage Products Tab -->
            <div class="tab-pane active" id="manage">
                <?php if (count($products) === 0): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
                        <i class="fa-solid fa-box-open text-4xl text-slate-400 mb-3"></i>
                        <h3 class="text-lg font-bold text-slate-800 mb-1">No Products Found</h3>
                        <p class="text-sm text-slate-500">You haven't created any products yet. Go to the Create Product tab to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($products as $prod): 
                            $id = $prod['id'];
                            $pname = $prod['name'];
                            $pdesc = $prod['description'];
                            $picon = $prod['icon'];
                            $pemoji = $prod['emoji'];
                            $active = $prod['active'];
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
                                
                                <div class="p-5 border-b border-slate-100 flex-1 flex flex-col">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-12 h-12 rounded-xl <?php echo $active ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500'; ?> flex items-center justify-center text-2xl shadow-sm">
                                            <i class="fa-solid <?php echo htmlspecialchars($picon); ?>"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-lg m-0"><?php echo mlmp_escape($pname); ?> <?php echo mlmp_escape($pemoji); ?></h3>
                                            <div class="text-xs font-semibold text-slate-500 mt-0.5">ID: #<?php echo $id; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-slate-50 rounded-xl p-4 flex-1 border border-slate-100">
                                        <p class="text-sm text-slate-700 m-0 leading-relaxed"><?php echo mlmp_escape($pdesc); ?></p>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                                    <a href="editproduct.php?id=<?php echo $id; ?>" class="flex-1 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 py-2.5 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                    </a>
                                    
                                    <form action="toggleproduct.php" method="post" class="flex-1">
                                        <input type="hidden" name="prod_id" value="<?php echo $id; ?>">
                                        <?php if($active): ?>
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 py-2.5 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
                                                <i class="fa-solid fa-power-off mr-1"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-2.5 text-center text-xs font-bold rounded-lg transition-colors shadow-sm">
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

            <!-- Create Product Tab -->
            <div class="tab-pane" id="create">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 max-w-2xl mx-auto">
                    <form action="createproduct.php" method="post" class="flex flex-col gap-5">
                        <div class="mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-900 m-0"><i class="fa-solid fa-plus-circle text-indigo-500 mr-1.5"></i> Add New Product</h4>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Product Name</label>
                            <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Enter Product Name" name="prodname" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Product Details (Description)</label>
                            <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" rows="3" placeholder="Short description of the product" name="proddetail" required></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">FontAwesome Icon Class</label>
                                <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. fa-star, fa-laptop-code" name="prodicon" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Emoji (Optional)</label>
                                <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="e.g. 💻, 📘, 🚀" name="prodemoji">
                            </div>
                        </div>
                        
                        <div class="border-t border-slate-200 mt-2 pt-4">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none py-3 px-8 text-sm font-bold rounded-xl shadow-md transition-all w-full md:w-auto md:float-right">
                                <i class="fa-solid fa-check-circle mr-1.5"></i> Create Product
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
