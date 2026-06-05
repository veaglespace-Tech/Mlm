<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

// Fetch user details
$user_row = mlmp_pdo_fetch($pdo, "SELECT id, fname, doj, active, address, pcktaken FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$aid        = $user_row['id']       ?? 0;
$name       = $user_row['fname']    ?? '';
$regdate    = $user_row['doj']      ?? '';
$acti       = $user_row['active']   ?? 0;
$address    = $user_row['address']  ?? '';
$pck        = $user_row['pcktaken'] ?? 0;

// Fetch settings
$settings_row = mlmp_pdo_fetch($pdo, "SELECT invoicedetails, coname FROM settings LIMIT 1");
$inv_details  = $settings_row['invoicedetails'] ?? '';
$co_name      = $settings_row['coname']         ?? '';

// Fetch package
$pname = ''; $pdetails = ''; $pprice = 0; $pcur = ''; $ptax = 0;
if ($pck) {
    $pkg_row = mlmp_pdo_fetch($pdo, "SELECT name, details, price, currency, tax FROM packages WHERE id = ?", [$pck]);
    if ($pkg_row) {
        $pname   = $pkg_row['name'];
        $pdetails= $pkg_row['details'];
        $pprice  = (float)$pkg_row['price'];
        $pcur    = $pkg_row['currency'];
        $ptax    = (float)$pkg_row['tax'];
    }
}

$page_title = "Invoice / Account Status";
$active_nav = "invoice";
include 'layout_header.php';
?>

<div class="mb-4 flex justify-end">
  <button onclick="window.print()" class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white border-none rounded-lg py-2.5 px-5 text-sm font-semibold cursor-pointer flex items-center gap-2 hover:from-indigo-500 hover:to-purple-600 transition-colors shadow-lg shadow-indigo-500/30">
    <i class="fa-solid fa-print"></i> Print Invoice
  </button>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col mb-6">
  <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-4">
    <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-file-invoice"></i></span>
      Invoice #<?php echo (int)$aid; ?>
    </div>
    <?php if ($acti == 1): ?>
      <span class="text-emerald-600 font-semibold bg-emerald-500/10 px-2.5 py-1 rounded-md text-xs border border-emerald-500/20">Active</span>
    <?php else: ?>
      <span class="text-red-600 font-semibold bg-red-500/10 px-2.5 py-1 rounded-md text-xs border border-red-500/20">Pending</span>
    <?php endif; ?>
  </div>
  <div class="p-6">

    <!-- Header row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
      <div>
        <div class="text-base font-bold text-slate-900 mb-1.5"><?php echo mlmp_escape($co_name); ?></div>
        <div class="text-sm text-slate-600 leading-relaxed"><?php echo nl2br(mlmp_escape($inv_details)); ?></div>
      </div>
      <div class="md:text-right">
        <div class="text-xl font-extrabold text-indigo-600 mb-1.5">Order #<?php echo (int)$aid; ?></div>
        <div class="text-xs text-slate-600">Registration: <strong class="text-slate-700"><?php echo mlmp_escape($regdate); ?></strong></div>
        <div class="text-xs text-slate-600 mt-1">Validity: <strong class="text-slate-700">Lifetime</strong></div>
      </div>
    </div>

    <!-- Address row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-slate-50 border border-slate-200 rounded-xl p-5 mb-6">
      <div>
        <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider mb-1.5">Bill To</div>
        <div class="text-sm font-bold text-slate-800"><?php echo mlmp_escape($name); ?></div>
        <div class="text-xs text-slate-600 mt-1"><?php echo mlmp_escape($address); ?></div>
      </div>
      <div>
        <div class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider mb-1.5">Ship To</div>
        <div class="text-sm font-bold text-slate-800"><?php echo mlmp_escape($name); ?></div>
        <div class="text-xs text-slate-600 mt-1"><?php echo mlmp_escape($address); ?></div>
      </div>
    </div>

    <!-- Order status -->
    <div class="text-sm text-slate-600 mb-4 flex items-center flex-wrap gap-2">
      <span>Order date: <strong class="text-slate-700"><?php echo mlmp_escape($regdate); ?></strong></span>
      <span class="text-slate-700">|</span>
      <span>Status:
      <?php if ($acti == 1): ?>
        <span class="bg-emerald-500/10 text-emerald-600 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-emerald-500/20 ml-1">Completed - Paid/Activated</span>
      <?php else: ?>
        <span class="bg-red-500/10 text-red-600 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-red-500/20 ml-1">Pending - Activation/Payment</span>
      <?php endif; ?>
      </span>
    </div>

    <!-- Invoice table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200">
            <th class="py-2.5 px-3.5 text-slate-600 text-[11px] font-semibold uppercase tracking-wider text-left">QTY</th>
            <th class="py-2.5 px-3.5 text-slate-600 text-[11px] font-semibold uppercase tracking-wider text-left">Description</th>
            <th class="py-2.5 px-3.5 text-slate-600 text-[11px] font-semibold uppercase tracking-wider text-right">Unit Price</th>
            <th class="py-2.5 px-3.5 text-slate-600 text-[11px] font-semibold uppercase tracking-wider text-right">Total</th>
          </tr>
        </thead>
        <tbody class="text-sm">
          <tr class="border-b border-slate-200">
            <td class="py-3.5 px-3.5 text-slate-700"><?php echo (int)$pck; ?></td>
            <td class="py-3.5 px-3.5 text-slate-800 font-medium"><?php echo mlmp_escape($pdetails ?: $pname); ?></td>
            <td class="py-3.5 px-3.5 text-right text-slate-800"><?php echo mlmp_escape($pcur) . ' ' . number_format($pprice, 2); ?></td>
            <td class="py-3.5 px-3.5 text-right text-slate-800 font-semibold"><?php echo mlmp_escape($pcur) . ' ' . number_format($pprice, 2); ?></td>
          </tr>
          <tr class="border-b border-slate-200">
            <td colspan="3" class="py-3 px-3.5 text-right text-slate-600 font-semibold">Subtotal</td>
            <td class="py-3 px-3.5 text-right text-slate-700"><?php echo mlmp_escape($pcur) . ' ' . number_format($pprice, 2); ?></td>
          </tr>
          <tr class="border-b border-slate-200">
            <td colspan="3" class="py-3 px-3.5 text-right text-slate-600 font-semibold">Tax / VAT</td>
            <td class="py-3 px-3.5 text-right text-slate-700"><?php echo mlmp_escape($pcur) . ' ' . number_format($ptax, 2); ?></td>
          </tr>
          <tr>
            <td colspan="3" class="py-4 px-3.5 text-right text-indigo-600 font-bold text-base">Total</td>
            <td class="py-4 px-3.5 text-right text-indigo-600 font-extrabold text-base"><?php echo mlmp_escape($pcur) . ' ' . number_format($pprice + $ptax, 2); ?></td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php include 'layout_footer.php'; ?>
