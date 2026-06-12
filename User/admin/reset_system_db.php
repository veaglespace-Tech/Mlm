<?php
include_once ("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    echo "<script>window.location='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_confirm']) && $_POST['reset_confirm'] === 'YES') {
    try {
        $pdo->beginTransaction();

        // 1. Delete all users except Admin (level = 1)
        $stmt = $pdo->prepare("DELETE FROM affiliateuser WHERE level != 1");
        $stmt->execute();
        
        // Reset auto-increment
        $pdo->exec("ALTER TABLE affiliateuser AUTO_INCREMENT = 1");

        // 2. Reset admin counters
        $stmt = $pdo->prepare("UPDATE affiliateuser SET left_count = 0, right_count = 0, paid_pairs = 0, tamount = 0 WHERE level = 1");
        $stmt->execute();

        // 3. Truncate transaction tables
        $pdo->exec("TRUNCATE TABLE payments");
        $pdo->exec("TRUNCATE TABLE payu_payments");
        $pdo->exec("TRUNCATE TABLE pending_registrations");
        $pdo->exec("TRUNCATE TABLE pairing_transactions");

        $pdo->commit();

        $_SESSION['admin_msg'] = "<div class='bg-emerald-50 text-emerald-700 border border-emerald-200 p-4 rounded-xl mb-6'><strong>Success!</strong> Database successfully reset. All test users and payments have been cleared.</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_msg'] = "<div class='bg-red-50 text-red-700 border border-red-200 p-4 rounded-xl mb-6'><strong>Error!</strong> Failed to reset database: " . $e->getMessage() . "</div>";
    }
    
    header("Location: dashboard.php");
    exit;
}

$page_title = 'Factory Reset System';
$active_nav = 'gensettings';
include("layout_header.php");
?>

<div class="bg-red-50 border border-red-200 rounded-xl p-6 text-red-700">
    <h2 class="text-xl font-bold mb-4"><i class="fa-solid fa-triangle-exclamation"></i> DANGER ZONE: Factory Reset Database</h2>
    <p class="mb-4">This action will completely wipe all member accounts, all payment records, all matching commissions, and all system data.</p>
    <p class="mb-6"><strong>Only the core Admin account and system settings will be preserved. This action CANNOT be undone!</strong></p>
    
    <form method="POST" action="reset_system_db.php" onsubmit="return confirm('Are you ABSOLUTELY sure you want to permanently delete all data?');">
        <div class="mb-4">
            <label class="block font-bold mb-2">Type "YES" to confirm:</label>
            <input type="text" name="reset_confirm" required class="border border-red-300 rounded px-3 py-2 w-full max-w-xs focus:outline-none focus:ring-1 focus:ring-red-500">
        </div>
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded transition">Permanently Reset Database</button>
        <a href="gensettings.php" class="ml-4 text-slate-600 hover:text-slate-800 underline">Cancel</a>
    </form>
</div>

<?php include("layout_footer.php"); ?>
