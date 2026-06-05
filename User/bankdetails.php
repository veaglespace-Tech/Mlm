<?php
include_once ("z_db.php");
// Initialize session
session_start();

// Check if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['userid'])) {
    print "
        <script language='javascript'>
            window.location = 'index.php';
        </script>
    ";
    exit;
}

$page_title = "Bank Details & KYC";
$active_nav = "bankdetails";
include("layout_header.php");

$username = $_SESSION['userid'];

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bank'])) {
    $bankname = mlmp_escape($_POST['bankname']);
    $accountname = mlmp_escape($_POST['accountname']);
    $accountno = mlmp_escape($_POST['accountno']);
    $ifsccode = mlmp_escape($_POST['ifsccode']);
    $accounttype = mlmp_escape($_POST['accounttype']); // 1 for Current, 2 for Savings

    $query = "UPDATE affiliateuser SET bankname = ?, accountname = ?, accountno = ?, ifsccode = ?, accounttype = ? WHERE username = ?";
    $stmt = $pdo->prepare($query);
    if($stmt->execute([$bankname, $accountname, $accountno, $ifsccode, $accounttype, $username])) {
        $msg = "<div class='dash-alert' style='background:rgba(16,185,129,0.1); color:#059669; border:1px solid #34d399;'><i class='fa-solid fa-circle-check'></i> Bank details updated successfully!</div>";
    } else {
        $msg = "<div class='dash-alert' style='background:rgba(239,68,68,0.1); color:#dc2626; border:1px solid #f87171;'><i class='fa-solid fa-triangle-exclamation'></i> Failed to update bank details.</div>";
    }
}

// Fetch current details
$stmtUser = $pdo->prepare("SELECT bankname, accountname, accountno, ifsccode, accounttype FROM affiliateuser WHERE username = ? LIMIT 1");
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();

$c_bankname = $user['bankname'] !== 'Not Available' ? $user['bankname'] : '';
$c_accountname = $user['accountname'] !== 'Not Available' ? $user['accountname'] : '';
$c_accountno = $user['accountno'] != 0 ? $user['accountno'] : '';
$c_ifsccode = $user['ifsccode'] !== 'Not Available' ? $user['ifsccode'] : '';
$c_accounttype = $user['accounttype']; // 1 = Current, 2 = Savings, 0 = Not set
?>

<div class="dash-content">
    <?php if(isset($msg)) echo "<div style='margin-bottom:16px;'>$msg</div>"; ?>
    
    <div class="dash-panel">
        <div class="dash-panel-header">
            <div class="panel-title">
                <span class="panel-icon"><i class="fa-solid fa-building-columns"></i></span>
                Update Bank Information
            </div>
        </div>
        <div class="dash-panel-body">
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">
                Please ensure your bank details are accurate. Withdrawals will be credited to this account.
            </p>
            
            <form action="bankdetails.php" method="post">
                <div class="dash-row col-2" style="margin-bottom:16px;">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" class="form-control" name="bankname" placeholder="e.g. State Bank of India" value="<?php echo htmlspecialchars($c_bankname); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Account Holder Name</label>
                        <input type="text" class="form-control" name="accountname" placeholder="Name on the account" value="<?php echo htmlspecialchars($c_accountname); ?>" required>
                    </div>
                </div>
                
                <div class="dash-row col-2" style="margin-bottom:16px;">
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" name="accountno" placeholder="Enter Account Number" value="<?php echo htmlspecialchars($c_accountno); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>IFSC Code</label>
                        <input type="text" class="form-control" name="ifsccode" placeholder="e.g. SBIN0001234" value="<?php echo htmlspecialchars($c_ifsccode); ?>" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:24px;">
                    <label>Account Type</label>
                    <select class="form-control" name="accounttype" required>
                        <option value="2" <?php if($c_accounttype == 2) echo 'selected'; ?>>Savings Account</option>
                        <option value="1" <?php if($c_accounttype == 1) echo 'selected'; ?>>Current Account</option>
                    </select>
                </div>
                
                <button type="submit" name="update_bank" class="dash-btn dash-btn-primary" style="width:100%;">
                    Save Bank Details
                </button>
            </form>
        </div>
    </div>
</div>

<?php include("layout_footer.php"); ?>
