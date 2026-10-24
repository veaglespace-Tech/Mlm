<?php
/**
 * Clean & Reset MLM Database
 *
 * This script wipes all test members, virtual boards, pair transactions,
 * and countdowns, while preserving:
 * - Admin Account (@adminadmin)
 * - Platform Settings (SMTP, PayU, Branding)
 * - Packages, Products, Currencies & Gateways
 */

if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
    }
}

require_once __DIR__ . '/../z_db.php';

echo "========================================================\n";
echo "CLEANING & RESETTING MLM DATABASE\n";
echo "========================================================\n\n";

// 1. Delete all non-admin users and virtual boards
echo "1. Removing all test users and virtual boards...\n";
$deleted = $pdo->exec("DELETE FROM affiliateuser WHERE username != 'adminadmin'");
echo "   -> Deleted {$deleted} user records.\n\n";

// 2. Reset Admin account (@adminadmin / abhijeet ambhore)
echo "2. Resetting Admin balance and network counters...\n";
$pdo->exec("UPDATE affiliateuser SET 
    fname = 'abhijeet ambhore',
    email = 'abhijeetambhore4@gmail.com',
    left_count = 0, 
    right_count = 0, 
    paid_pairs = 0, 
    tamount = 0.00, 
    active = 1, 
    launch = 1, 
    parent_id = NULL, 
    position = NULL 
WHERE level = 1");
echo "   -> Admin counters reset: Left=0, Right=0, Paid Pairs=0, Wallet=Rs. 0.00\n\n";

// 3. Reset auto-increment on affiliateuser
$maxId = (int)mlmp_pdo_fetch($pdo, "SELECT COALESCE(MAX(Id), 1) as max_id FROM affiliateuser")['max_id'];
$nextId = $maxId + 1;
try {
    $pdo->exec("ALTER TABLE affiliateuser AUTO_INCREMENT = {$nextId}");
} catch (Exception $e) {
    // Ignore if not supported
}

// 4. Truncate all transaction and countdown tables
echo "3. Truncating transaction, payout, and temporary tables...\n";
$tablesToTruncate = [
    'pairing_transactions',
    'pair_countdowns',
    'payments',
    'payu_payments',
    'paypalpayments',
    'pending_registrations',
    'rejected_registrations',
    'notifications'
];

foreach ($tablesToTruncate as $tbl) {
    try {
        $pdo->exec("TRUNCATE TABLE `{$tbl}`");
        echo "   -> Truncated `{$tbl}`\n";
    } catch (Exception $e) {
        echo "   -> Skipped `{$tbl}` ({$e->getMessage()})\n";
    }
}

// 5. Verification Summary
$remainingUsers = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser");
$adminAccount = mlmp_pdo_fetch($pdo, "SELECT Id, username, left_count, right_count, paid_pairs, tamount FROM affiliateuser WHERE username = 'adminadmin'");

echo "\n========================================================\n";
echo "DATABASE CLEANUP COMPLETE!\n";
echo "========================================================\n";
echo "Remaining Users: {$remainingUsers} (Admin only)\n";
echo "Admin Username:  @{$adminAccount['username']}\n";
echo "Left Count:      {$adminAccount['left_count']}\n";
echo "Right Count:     {$adminAccount['right_count']}\n";
echo "Paid Pairs:      {$adminAccount['paid_pairs']}\n";
echo "Wallet Balance:  Rs. " . number_format($adminAccount['tamount'], 2) . "\n";
echo "Database is now 100% clean and ready for fresh registrations!\n";
echo "========================================================\n";
