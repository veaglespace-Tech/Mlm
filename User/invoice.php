<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Fetch user details
$user_row = mlmp_pdo_fetch($pdo, "SELECT id, fname, doj, active, address, pcktaken, email, mobile, country FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$aid        = $user_row['id']       ?? 0;
$name       = $user_row['fname']    ?? '';
$email      = $user_row['email']    ?? '';
$mobile     = $user_row['mobile']   ?? '';
$country    = $user_row['country']  ?? 'India';
$regdate    = $user_row['doj']      ?? date('Y-m-d');
$acti       = (int)($user_row['active'] ?? 0);
$address    = $user_row['address']  ?? 'N/A';
$pck        = (int)($user_row['pcktaken'] ?? 1);

// Fetch settings
$settings_row = mlmp_pdo_fetch($pdo, "SELECT invoicedetails, coname, email FROM settings LIMIT 1");
$inv_details  = $settings_row['invoicedetails'] ?? 'Veagle Space Tech Solutions';
$co_name      = (!empty($settings_row['coname']) && $settings_row['coname'] !== 'Header') ? $settings_row['coname'] : 'Veagle Space Network';
$co_email     = $settings_row['email'] ?? 'support@veaglespace.com';

// Fetch package
$pname = 'Standard MLM Membership'; $pdetails = 'Digital Affiliate Kit & Business Center'; $pprice = 1000.00; $pcur = 'INR'; $ptax = 0.00;
if ($pck) {
    $pkg_row = mlmp_pdo_fetch($pdo, "SELECT name, details, price, currency, tax FROM packages WHERE id = ?", [$pck]);
    if ($pkg_row) {
        $pname   = $pkg_row['name'];
        $pdetails= $pkg_row['details'] ?: 'Digital Affiliate Kit & Binary Position';
        $pprice  = (float)$pkg_row['price'];
        $pcur    = $pkg_row['currency'] ?: 'INR';
        $ptax    = (float)$pkg_row['tax'];
    }
}

// Fetch latest payment record for this user
$pay_record = mlmp_pdo_fetch($pdo, "SELECT transacid, date, gateway FROM payu_payments WHERE orderid = ? ORDER BY id DESC LIMIT 1", [$aid]);
$txn_id = $pay_record['transacid'] ?? ('TXN-' . strtoupper(substr(md5($_SESSION['username'] . $aid), 0, 10)));
$txn_date = !empty($pay_record['date']) ? date('M d, Y', strtotime($pay_record['date'])) : date('M d, Y', strtotime($regdate));
$inv_number = 'INV-' . date('Y', strtotime($regdate)) . '-' . str_pad((string)$aid, 5, '0', STR_PAD_LEFT);

$page_title = "Tax Invoice Receipt";
$active_nav = "invoice";
include 'layout_header.php';
?>

<style>
@media print {
  body * { visibility: hidden; }
  #printableInvoice, #printableInvoice * { visibility: visible; }
  #printableInvoice { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
  .no-print { display: none !important; }
}
</style>

<!-- Action Bar -->
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 no-print">
  <div>
    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Membership Tax Invoice</h2>
    <p class="text-xs text-slate-500 mt-0.5">Official payment receipt and tax invoice for your affiliate membership package.</p>
  </div>
  <div class="flex items-center gap-2">
    <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2.5 px-4 text-xs font-bold cursor-pointer inline-flex items-center gap-2 transition-all shadow-sm shadow-indigo-600/20">
      <i class="fa-solid fa-print"></i> Print / Save PDF
    </button>
  </div>
</div>

<!-- Invoice Card Container -->
<div id="printableInvoice" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8 max-w-4xl mx-auto">
  <!-- Invoice Top Banner -->
  <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 bg-gradient-to-r from-slate-50 to-indigo-50/30">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-indigo-600/30 shrink-0">
        <i class="fa-solid fa-gem"></i>
      </div>
      <div>
        <h1 class="text-xl font-extrabold text-slate-900"><?php echo mlmp_escape($co_name); ?></h1>
        <div class="text-xs text-slate-500 mt-0.5 leading-relaxed"><?php echo nl2br(mlmp_escape($inv_details)); ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i><?php echo mlmp_escape($co_email); ?></div>
      </div>
    </div>
    <div class="md:text-right flex flex-col md:items-end">
      <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider mb-2 <?php echo $acti === 1 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'; ?>">
        <i class="fa-solid <?php echo $acti === 1 ? 'fa-circle-check text-emerald-600' : 'fa-clock text-amber-600'; ?> mr-1"></i>
        <?php echo $acti === 1 ? 'PAID / ACTIVE' : 'PAYMENT PENDING'; ?>
      </span>
      <div class="text-lg font-black text-slate-900 font-mono tracking-tight"><?php echo $inv_number; ?></div>
      <div class="text-xs text-slate-500 mt-0.5">Date: <strong class="text-slate-700"><?php echo $txn_date; ?></strong></div>
    </div>
  </div>

  <div class="p-6 md:p-8">
    <!-- Parties & Payment Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-5 bg-slate-50 rounded-2xl border border-slate-200/80 mb-8 text-xs">
      <div>
        <div class="font-bold uppercase tracking-wider text-slate-400 text-[10px] mb-2">Billed To</div>
        <div class="font-extrabold text-slate-900 text-sm mb-1"><?php echo mlmp_escape($name); ?></div>
        <div class="text-slate-600">@<?php echo mlmp_escape($_SESSION['username']); ?> (ID #<?php echo $aid; ?>)</div>
        <div class="text-slate-600 mt-1"><?php echo mlmp_escape($address); ?></div>
        <div class="text-slate-600"><?php echo mlmp_escape($country); ?></div>
        <?php if (!empty($mobile)): ?><div class="text-slate-600 mt-1"><i class="fa-solid fa-phone text-slate-400 mr-1"></i><?php echo mlmp_escape($mobile); ?></div><?php endif; ?>
      </div>

      <div>
        <div class="font-bold uppercase tracking-wider text-slate-400 text-[10px] mb-2">Payment Details</div>
        <div class="text-slate-600">Payment Mode: <strong class="text-slate-800">Online Gateway (PayU)</strong></div>
        <div class="text-slate-600 mt-1">Transaction Ref: <code class="bg-white border border-slate-200 px-1.5 py-0.5 rounded text-indigo-700 font-mono"><?php echo mlmp_escape($txn_id); ?></code></div>
        <div class="text-slate-600 mt-1">Order Status: <strong class="text-emerald-700">Settled & Completed</strong></div>
        <div class="text-slate-600 mt-1">Currency: <strong class="text-slate-800"><?php echo mlmp_escape($pcur); ?> (INR)</strong></div>
      </div>

      <div>
        <div class="font-bold uppercase tracking-wider text-slate-400 text-[10px] mb-2">Membership Entitlements</div>
        <div class="text-slate-600">Plan: <strong class="text-slate-800"><?php echo mlmp_escape($pname); ?></strong></div>
        <div class="text-slate-600 mt-1">Binary Center: <strong class="text-indigo-700">1:1 Pair Commission Qualified</strong></div>
        <div class="text-slate-600 mt-1">Validity: <strong class="text-slate-800">Lifetime Active Access</strong></div>
        <div class="text-slate-600 mt-1">Direct Referrals: <strong class="text-slate-800">Unlimited Sponsorship</strong></div>
      </div>
    </div>

    <!-- Line Items Table -->
    <div class="overflow-x-auto mb-6">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[10px]">
            <th class="py-3 px-4 font-bold">Item Description</th>
            <th class="py-3 px-4 font-bold text-center">Qty</th>
            <th class="py-3 px-4 font-bold text-right">Taxable Price</th>
            <th class="py-3 px-4 font-bold text-right">Tax / GST</th>
            <th class="py-3 px-4 font-bold text-right">Total Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr>
            <td class="py-4 px-4">
              <div class="font-bold text-slate-900 text-sm"><?php echo mlmp_escape($pname); ?></div>
              <div class="text-slate-500 text-[11px] mt-0.5"><?php echo mlmp_escape($pdetails); ?></div>
            </td>
            <td class="py-4 px-4 text-center font-medium text-slate-700">1</td>
            <td class="py-4 px-4 text-right font-medium text-slate-800"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($pprice, 2); ?></td>
            <td class="py-4 px-4 text-right font-medium text-slate-600"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($ptax, 2); ?></td>
            <td class="py-4 px-4 text-right font-bold text-slate-900"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($pprice + $ptax, 2); ?></td>
          </tr>
        </tbody>
        <tfoot class="border-t border-slate-200">
          <tr>
            <td colspan="4" class="py-2.5 px-4 text-right text-slate-500 font-medium">Subtotal</td>
            <td class="py-2.5 px-4 text-right text-slate-800 font-semibold"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($pprice, 2); ?></td>
          </tr>
          <tr>
            <td colspan="4" class="py-2.5 px-4 text-right text-slate-500 font-medium">GST / Taxes</td>
            <td class="py-2.5 px-4 text-right text-slate-800 font-semibold"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($ptax, 2); ?></td>
          </tr>
          <tr class="border-t border-slate-200 text-sm">
            <td colspan="4" class="py-3.5 px-4 text-right font-extrabold text-indigo-900">Total Paid Amount</td>
            <td class="py-3.5 px-4 text-right font-black text-indigo-600 text-base"><?php echo mlmp_escape($pcur); ?> <?php echo number_format($pprice + $ptax, 2); ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Invoice Footer & Signatory -->
    <div class="pt-6 border-t border-slate-100 flex flex-col md:flex-row items-start md:items-end justify-between gap-6 text-xs text-slate-500">
      <div class="max-w-md">
        <div class="font-bold text-slate-800 mb-1">Terms & Conditions:</div>
        <p class="leading-relaxed mb-0">This document serves as an electronic payment receipt for affiliate marketing business participation. All binary matching and referral commissions are calculated dynamically per network compensation rules.</p>
      </div>
      <div class="text-center md:text-right shrink-0">
        <div class="w-28 h-10 border-b border-slate-300 mx-auto md:ml-auto mb-1 flex items-end justify-center">
          <span class="text-[11px] font-script italic text-indigo-600 font-bold">Authorized Signatory</span>
        </div>
        <div class="font-bold text-slate-700"><?php echo mlmp_escape($co_name); ?></div>
        <div class="text-[10px] text-slate-400">Computer-Generated Tax Receipt</div>
      </div>
    </div>
  </div>
</div>

<?php include 'layout_footer.php'; ?>

