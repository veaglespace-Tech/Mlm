<?php
include_once ("z_db.php");
session_start();

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

$prod_id = (int)($_GET['id'] ?? 0);
if ($prod_id <= 0) {
    $_SESSION['product_message'] = "Invalid product selected.";
    header("Location: productsettings.php");
    exit;
}

// Fetch product details
$row = mlmp_pdo_fetch($pdo, "SELECT * FROM products WHERE id = ?", [$prod_id]);
if (!$row) {
    $_SESSION['product_message'] = "Selected product does not exist.";
    header("Location: productsettings.php");
    exit;
}

$pname = $row['name'] ?? '';
$pdetail = $row['description'] ?? '';
$picon = $row['icon'] ?? '';
$pemoji = $row['emoji'] ?? '';
$active = $row['active'] ?? 0;

$page_title = "Edit Product Settings";
$active_nav = "productsettings";

include("layout_header.php");
?>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700">
        <i class="fa-solid fa-circle-info text-blue-600 mr-2"></i>
        <strong>Edit Product:</strong> Update the product details below.
    </p>
</div>

<!-- Header Detail Panel -->
<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-xl text-indigo-600">
                <i class="fa-solid <?php echo htmlspecialchars($picon); ?>"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 m-0"><?php echo mlmp_escape($pname); ?> <?php echo mlmp_escape($pemoji); ?></h2>
                <p class="text-sm text-slate-600 mt-1 mb-0">Update the product catalog information.</p>
            </div>
        </div>
        <div>
            <span class="inline-block px-4 py-1.5 rounded-lg text-xs font-bold border <?php echo ($active == 1) ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'; ?>">
                <?php echo ($active == 1) ? 'Active Product' : 'Inactive / Disabled'; ?>
            </span>
        </div>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden max-w-4xl mx-auto mb-10">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 font-bold text-slate-900 flex items-center gap-2">
        <i class="fa-solid fa-pen-to-square text-slate-600"></i> Edit Product Details
    </div>
    <div class="p-6 flex flex-col gap-4">
        <form action="updateproduct.php" method="post" class="flex flex-col gap-5">
            <input type="hidden" name="uproduct" value="<?php echo $prod_id; ?>">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New Product Name</label>
                <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" value="<?php echo mlmp_escape($pname); ?>" name="prodname" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New Product Details (Description)</label>
                <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" rows="3" name="proddetail" required><?php echo mlmp_escape($pdetail); ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New FontAwesome Icon Class</label>
                    <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" value="<?php echo mlmp_escape($picon); ?>" name="prodicon" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New Emoji</label>
                    <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" value="<?php echo mlmp_escape($pemoji); ?>" name="prodemoji">
                </div>
            </div>
            
            <div class="border-t border-slate-200 mt-2 pt-4 flex gap-4 justify-end">
                <a href="productsettings.php" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 py-2.5 px-6 text-sm font-bold rounded-xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-2.5 px-6 text-sm font-bold rounded-xl shadow-md transition-all">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>

<?php include("layout_footer.php"); ?>
