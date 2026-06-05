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

$page_title = 'PayU Payments Received';
$active_nav = 'payments';
include("layout_header.php");

// Fetch PayU payments
$payments = mlmp_pdo_fetch_all($pdo, "SELECT * FROM payu_payments WHERE gateway = 'PayU' ORDER BY id ASC");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Please verify electronic transaction statuses in your PayU dashboard before manually activating a user's account. Records are shown from oldest to newest.</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
        <h3 class="text-sm font-bold text-slate-900">PayU Payment History Logs</h3>
    </div>
    <div class="p-6 overflow-x-auto">
        <table id="paymentsTable" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-black/20 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                    <th class="px-5 py-3 font-semibold">Payment ID</th>
                    <th class="px-5 py-3 font-semibold">Transaction ID</th>
                    <th class="px-5 py-3 font-semibold">Amount Received</th>
                    <th class="px-5 py-3 font-semibold">Payment Date</th>
                    <th class="px-5 py-3 font-semibold">Username</th>
                    <th class="px-5 py-3 font-semibold">Package Taken</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700 divide-y divide-white/5">
                <?php if (count($payments) === 0): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-600">
                            <i class="fa-solid fa-receipt text-3xl mb-3 block"></i>
                            No PayU payments found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): 
                        $pid = $payment['id'];
                        $poid = $payment['orderid'];
                        $ptransac = $payment['transacid'];
                        $ppaypalprice = $payment['price'];
                        $pcur = $payment['currency'];
                        $pdate = $payment['date'];
                        
                        // Fetch user details
                        $user = mlmp_pdo_fetch($pdo, "SELECT * FROM affiliateuser WHERE Id = ?", [$poid]);
                        if (!$user) continue;
                        
                        $username = $user['username'];
                        $active = $user['active'];
                        $pck = $user['pcktaken'];
                        $lprofile = $user['launch'];
                        
                        if ($active == 1) {
                            $status = "Active/Paid";
                            $status_class = "bg-emerald-500/20 text-emerald-600 border-emerald-500/20";
                        } else if ($active == 0) {
                            $status = "Inactive/Unpaid";
                            $status_class = "bg-red-500/20 text-red-600 border-red-500/20";
                        } else {
                            $status = "Unknown";
                            $status_class = "bg-slate-500/20 text-slate-600 border-slate-500/20";
                        }
                        
                        // Fetch package details
                        $package = mlmp_pdo_fetch($pdo, "SELECT * FROM packages WHERE id = ?", [$pck]);
                        $pckname = $package['name'] ?? 'None';
                        $pckprice = $package['price'] ?? 0;
                        $pcktax = $package['tax'] ?? 0;
                        $pckcur = $package['currency'] ?? '';
                        $pcksbonus = $package['sbonus'] ?? 0;
                        $total = (float)$pckprice + (float)$pcktax;
                    ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-mono text-slate-600">#<?php echo mlmp_escape($pid); ?></td>
                            <td class="px-5 py-4"><code class="bg-indigo-500/10 text-indigo-600 px-2 py-1 rounded text-xs"><?php echo mlmp_escape($ptransac); ?></code></td>
                            <td class="px-5 py-4 font-bold text-emerald-600 whitespace-nowrap"><?php echo mlmp_escape($pcur); ?> <?php echo number_format((float)$ppaypalprice, 2); ?></td>
                            <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap"><?php echo date('M d, Y H:i', strtotime($pdate)); ?></td>
                            <td class="px-5 py-4 font-bold text-slate-800">@<?php echo mlmp_escape($username); ?></td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800"><?php echo mlmp_escape($pckname); ?></div>
                                <div class="text-xs text-slate-600"><?php echo mlmp_escape($pckcur); ?> <?php echo number_format((float)$total, 2); ?> (Bonus: <?php echo mlmp_escape($pcksbonus); ?>)</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="<?php echo $status_class; ?> border px-2 py-1 rounded text-xs font-semibold whitespace-nowrap"><?php echo $status; ?></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-col gap-2 items-end">
                                    <div class="flex gap-2">
                                        <a href="updateuser.php?username=<?php echo urlencode($username); ?>" class="bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Edit User</a>
                                        <a href="deleteuser.php?username=<?php echo urlencode($username); ?>" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-2 py-1 rounded transition-colors" title="Delete User"><i class="fa-solid fa-trash-can"></i></a>
                                    </div>
                                    <div class="flex gap-2">
                                        <?php if ($lprofile == 0): ?>
                                            <a href="launchprofile.php?username=<?php echo urlencode($username); ?>" class="bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2 py-1 rounded text-xs font-bold transition-colors whitespace-nowrap">Launch Account</a>
                                        <?php endif; ?>
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

<!-- DataTables & Export Plugins -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
/* Custom button styling for dataTables to match dashboard */
.dt-buttons {
    margin-bottom: 16px;
}
.dt-buttons .dt-button {
    background: rgba(99, 102, 241, 0.1) !important;
    color: #818cf8 !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 8px !important;
    padding: 8px 16px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    font-family: 'Inter', sans-serif !important;
    margin-right: 8px !important;
    transition: all 0.2s !important;
}
.dt-buttons .dt-button:hover {
    background: rgba(99, 102, 241, 0.2) !important;
    transform: translateY(-1px);
}
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgba(255, 255, 255, 0.05) !important;
    color: #e2e8f0 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    outline: none !important;
    margin-left: 8px !important;
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    background: rgba(22, 24, 31, 1) !important;
    color: #e2e8f0 !important;
    border-radius: 8px !important;
    padding: 6px !important;
    outline: none !important;
    margin: 0 4px !important;
}
.bottom {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 16px 0 0 0 !important;
    flex-wrap: wrap !important;
    gap: 12px !important;
}
.dataTables_info {
    font-size: 13px !important;
    color: #94a3b8 !important;
    padding-top: 0 !important;
}
.dataTables_length, .dataTables_filter {
    font-size: 13px !important;
    color: #94a3b8 !important;
}
.dataTables_paginate {
    font-size: 13px !important;
}
.dataTables_paginate .paginate_button {
    padding: 6px 12px !important;
    border-radius: 8px !important;
    border: 1px solid transparent !important;
    cursor: pointer !important;
    color: #94a3b8 !important;
}
.dataTables_paginate .paginate_button:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #e2e8f0 !important;
}
.dataTables_paginate .paginate_button.current, .dataTables_paginate .paginate_button.current:hover {
    background: rgba(99, 102, 241, 0.1) !important;
    color: #818cf8 !important;
    border-color: rgba(99, 102, 241, 0.2) !important;
}
table.dataTable tbody tr {
    background-color: transparent !important;
}
table.dataTable tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
}
table.dataTable.no-footer {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
}
</style>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#paymentsTable').DataTable({
        dom: '<"top"Bf>rt<"bottom"ilp><"clear">',
        buttons: [
            { 
                extend: 'excelHtml5', 
                text: '<i class="fa-solid fa-file-excel"></i> Download Excel', 
                className: 'dt-button',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            { 
                extend: 'pdfHtml5', 
                text: '<i class="fa-solid fa-file-pdf"></i> Download PDF', 
                className: 'dt-button', 
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'PayU Payment Logs',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function ( data, row, column, node ) {
                            // Strip HTML cleanly and replace with spaces where appropriate
                            return data.replace(/<br\s*\/?>/ig, "\n").replace(/<[^>]*>?/gm, ' ').replace(/\s\s+/g, ' ').trim();
                        }
                    }
                },
                customize: function (doc) {
                    doc.content[1].table.widths = ['10%', '15%', '15%', '15%', '15%', '15%', '15%'];
                    doc.styles.tableHeader.fillColor = '#6366f1';
                    doc.styles.tableHeader.color = 'white';
                    doc.styles.tableHeader.alignment = 'left';
                    doc.defaultStyle.alignment = 'left';
                    doc.defaultStyle.fontSize = 10;
                    
                    // Add some margins to the table cells
                    var objLayout = {};
                    objLayout.hLineWidth = function(i) { return 0.5; };
                    objLayout.vLineWidth = function(i) { return 0.5; };
                    objLayout.hLineColor = function(i) { return '#e2e8f0'; };
                    objLayout.vLineColor = function(i) { return '#e2e8f0'; };
                    objLayout.paddingLeft = function(i) { return 8; };
                    objLayout.paddingRight = function(i) { return 8; };
                    objLayout.paddingTop = function(i) { return 6; };
                    objLayout.paddingBottom = function(i) { return 6; };
                    doc.content[1].layout = objLayout;
                }
            }
        ],
        order: [], // Disable initial JS sorting so PHP order is kept
        pageLength: 25
    });
});
</script>


