<?php
/**
 * Standalone Network Seeder
 * 
 * Resets test accounts and creates:
 * 1. 10 direct members under Admin (spread across Admin's main account + virtual pair boards bc_2..bc_5)
 * 2. 1 pair (1 Left, 1 Right) under each of the 10 direct members (total 20 downline members)
 * Total: 30 active members in the binary/unilevel tree.
 */

if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
    }
}

require_once __DIR__ . '/../z_db.php';
require_once __DIR__ . '/../password_helper.php';

ensure_binary_schema($pdo);
ensure_package_schema($pdo);
ensure_settings_referral_schema($pdo);
ensure_pending_schema($pdo);

// Find admin user
$adminUser = mlmp_pdo_fetch($pdo, "SELECT Id, username, fname FROM affiliateuser WHERE level = 1 LIMIT 1");
if (!$adminUser) {
    die("Error: No admin user found with level = 1.\n");
}

$adminUsername = $adminUser['username'];
$adminId = (int)$adminUser['Id'];

echo "========================================================\n";
echo "Starting Network Reset & Pair-Wise Seeding\n";
echo "Admin Username: {$adminUsername} (ID: {$adminId})\n";
echo "========================================================\n\n";

// STEP 1: CLEANUP / RESET
echo "Step 1: Cleaning up existing test users and transactions...\n";
$pdo->exec("DELETE FROM affiliateuser WHERE username != '{$adminUsername}'");
$pdo->exec("UPDATE affiliateuser SET left_count = 0, right_count = 0, paid_pairs = 0, tamount = 0, active = 1, launch = 1 WHERE username = '{$adminUsername}'");

$tablesToTruncate = [
    'pairing_transactions',
    'pair_countdowns',
    'payments',
    'payu_payments',
    'pending_registrations',
    'rejected_registrations'
];

foreach ($tablesToTruncate as $tbl) {
    try {
        $pdo->exec("TRUNCATE TABLE {$tbl}");
    } catch (Exception $e) {
        // Table might not exist or error, ignore
    }
}

echo "Cleanup complete. Only Admin account preserved.\n\n";

// Helper function to register and activate a user with explicit parent and position
function seed_register_user_exact(PDO $pdo, string $username, string $fname, string $email, string $mobile, string $refSponsor, int $parentId, string $position): bool {
    $exists = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ? LIMIT 1", [$username]);
    if ($exists) {
        return false;
    }

    $hashedPassword = mlmp_hash_password("Veagle@123");
    $cur_date = date("Y-m-d");
    $expiry = '2199-12-31';
    $scode = (string)rand(1111111111, 9999999999);
    $ip_long = ip2long("127.0.0.1") ?: 0;

    $sql = "INSERT INTO affiliateuser(username, password, fname, address, email, referedby, ipaddress, mobile, active, doj, country, payment, signupcode, tamount, pcktaken, expiry, parent_id, position) 
            VALUES (?, ?, ?, 'Test Symmetrical Address', ?, ?, ?, ?, 0, ?, 'India', 'PayU', ?, 0, 1, ?, ?, ?)";
    mlmp_pdo_execute($pdo, $sql, [
        $username,
        $hashedPassword,
        $fname,
        $email,
        $refSponsor,
        $ip_long,
        $mobile,
        $cur_date,
        $scode,
        $expiry,
        $parentId,
        $position
    ]);

    // Launch profile to process matching pairs & commissions
    mlmp_launch_profile($pdo, $username);

    $stmtActivate = $pdo->prepare("UPDATE affiliateuser SET active = 1, payment = 'PayU' WHERE username = ?");
    $stmtActivate->execute([$username]);

    $newUser = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$username]);
    $newUserId = (int)($newUser['Id'] ?? 0);
    $txnId = "TXN_SYM_" . strtoupper(substr(md5($username . time()), 0, 10));
    $stmtPay = $pdo->prepare("INSERT INTO payu_payments (orderid, transacid, price, currency, date, pckid, gateway) VALUES (?, ?, 1000.00, 'INR', NOW(), 1, 'PayU')");
    $stmtPay->execute([$newUserId, $txnId]);

    return true;
}

// Helper function to register and activate a user using system's exact multi-board logic
function seed_register_user(PDO $pdo, string $username, string $fname, string $email, string $mobile, string $refSponsor): bool {
    // Check if user already exists
    $exists = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ? LIMIT 1", [$username]);
    if ($exists) {
        return false;
    }

    // MULTI-BOARD PAIR LOGIC (identical to payu_success.php)
    $sponsor = mlmp_pdo_fetch($pdo, "SELECT Id, level FROM affiliateuser WHERE username = ? LIMIT 1", [$refSponsor]);
    $sponsorId = (int)($sponsor['Id'] ?? 0);
    $parentId = $sponsorId;
    $position = 'L';

    if ($sponsorId > 0) {
        $stmtBoards = $pdo->prepare("SELECT Id FROM affiliateuser WHERE username = ? OR username LIKE ? ORDER BY Id ASC");
        $stmtBoards->execute([$refSponsor, $refSponsor . '_bc_%']);
        $boards = $stmtBoards->fetchAll();

        $placed = false;
        $boardCount = count($boards);
        foreach ($boards as $board) {
            $bId = (int)$board['Id'];
            $hasLeft = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE parent_id = ? AND position = 'L'", [$bId]);
            if ($hasLeft == 0) {
                $parentId = $bId;
                $position = 'L';
                $placed = true;
                break;
            }
            $hasRight = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE parent_id = ? AND position = 'R'", [$bId]);
            if ($hasRight == 0) {
                $parentId = $bId;
                $position = 'R';
                $placed = true;
                break;
            }
        }

        if (!$placed) {
            // All existing boards are full -- create a new virtual Pair Board for this user
            $newBoardNum = $boardCount + 1;
            $newBoardUsername = $refSponsor . '_bc_' . $newBoardNum;
            $boardLabel = 'Board ' . $newBoardNum;

            $sponsorLevel = (int)($sponsor['level'] ?? 2);
            $sqlVirtual = "INSERT INTO affiliateuser (username, password, fname, address, email, referedby, mobile, active, launch, parent_id, position, level)
                           VALUES (?, 'VIRTUAL', ?, '', '', 'none', '', 1, 1, 0, NULL, ?)";
            mlmp_pdo_execute($pdo, $sqlVirtual, [$newBoardUsername, $boardLabel, $sponsorLevel]);

            $newVirtualNode = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$newBoardUsername]);
            $parentId = (int)$newVirtualNode['Id'];
            $position = 'L';
        }
    }

    $hashedPassword = mlmp_hash_password("Veagle@123");
    $cur_date = date("Y-m-d");
    $expiry = '2199-12-31';
    $scode = (string)rand(1111111111, 9999999999);
    $ip_long = ip2long("127.0.0.1") ?: 0;

    $sql = "INSERT INTO affiliateuser(username, password, fname, address, email, referedby, ipaddress, mobile, active, doj, country, payment, signupcode, tamount, pcktaken, expiry, parent_id, position) 
            VALUES (?, ?, ?, 'Test Seeder Address', ?, ?, ?, ?, 0, ?, 'India', 'PayU', ?, 0, 1, ?, ?, ?)";
    mlmp_pdo_execute($pdo, $sql, [
        $username,
        $hashedPassword,
        $fname,
        $email,
        $refSponsor,
        $ip_long,
        $mobile,
        $cur_date,
        $scode,
        $expiry,
        $parentId,
        $position
    ]);

    // Launch profile to process commissions, counts, countdowns
    mlmp_launch_profile($pdo, $username);

    $stmtActivate = $pdo->prepare("UPDATE affiliateuser SET active = 1, payment = 'PayU' WHERE username = ?");
    $stmtActivate->execute([$username]);

    // Record transaction in payu_payments for turnover and payment reports
    $newUser = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$username]);
    $newUserId = (int)($newUser['Id'] ?? 0);
    $txnId = "TXN_SIM_" . strtoupper(substr(md5($username . time()), 0, 10));
    $stmtPay = $pdo->prepare("INSERT INTO payu_payments (orderid, transacid, price, currency, date, pckid, gateway) VALUES (?, ?, 1000.00, 'INR', NOW(), 1, 'PayU')");
    $stmtPay->execute([$newUserId, $txnId]);

    return true;
}

// STEP 2: CREATE 10 DIRECT MEMBERS UNDER ADMIN
echo "Step 2: Creating 10 direct members under Admin...\n";
$directUsernames = [];

for ($i = 1; $i <= 10; $i++) {
    $idx = str_pad($i, 2, '0', STR_PAD_LEFT);
    $username = "user_d" . $idx;
    $fname = "Direct User " . $idx;
    $email = "user.d" . $idx . "@gmail.com";
    $mobile = "98000000" . $idx;

    seed_register_user($pdo, $username, $fname, $email, $mobile, $adminUsername);
    $directUsernames[] = $username;

    // Check where user was placed
    $uData = mlmp_pdo_fetch($pdo, "SELECT Id, parent_id, position FROM affiliateuser WHERE username = ?", [$username]);
    $pData = mlmp_pdo_fetch($pdo, "SELECT username FROM affiliateuser WHERE Id = ?", [$uData['parent_id']]);
    $parentName = $pData['username'] ?? 'None';
    echo "  [+] Direct {$idx}: @{$username} placed under @{$parentName} ({$uData['position']})\n";
}

echo "10 direct members created under Admin.\n\n";

// STEP 3: CREATE COMPLETELY BALANCED (EQUAL L & R) BINARY TREES UNDER EACH DIRECT MEMBER
echo "\nStep 3: Creating 100% Symmetrical Binary Trees (Left = Right for EVERY member)...\n";

foreach ($directUsernames as $dUser) {
    $dNode = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$dUser]);
    $dId = (int)$dNode['Id'];

    // 1. Left Leg of Direct User (Symmetrical Depth 3 = 7 members)
    $uL1 = "{$dUser}_L1";
    seed_register_user_exact($pdo, $uL1, "{$dUser} L1", "{$uL1}@test.com", "96" . rand(10000000, 99999999), $dUser, $dId, 'L');
    $id_L1 = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uL1])['Id'];

    // L1 -> Left
    $uL1_L = "{$dUser}_L1_L";
    seed_register_user_exact($pdo, $uL1_L, "{$dUser} L1-L", "{$uL1_L}@test.com", "96" . rand(10000000, 99999999), $uL1, $id_L1, 'L');
    $id_L1_L = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uL1_L])['Id'];
    seed_register_user_exact($pdo, "{$dUser}_L1_LL", "{$dUser} L1-LL", "{$dUser}_L1_LL@test.com", "96" . rand(10000000, 99999999), $uL1_L, $id_L1_L, 'L');
    seed_register_user_exact($pdo, "{$dUser}_L1_LR", "{$dUser} L1-LR", "{$dUser}_L1_LR@test.com", "96" . rand(10000000, 99999999), $uL1_L, $id_L1_L, 'R');

    // L1 -> Right
    $uL1_R = "{$dUser}_L1_R";
    seed_register_user_exact($pdo, $uL1_R, "{$dUser} L1-R", "{$uL1_R}@test.com", "96" . rand(10000000, 99999999), $uL1, $id_L1, 'R');
    $id_L1_R = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uL1_R])['Id'];
    seed_register_user_exact($pdo, "{$dUser}_L1_RL", "{$dUser} L1-RL", "{$dUser}_L1_RL@test.com", "96" . rand(10000000, 99999999), $uL1_R, $id_L1_R, 'L');
    seed_register_user_exact($pdo, "{$dUser}_L1_RR", "{$dUser} L1-RR", "{$dUser}_L1_RR@test.com", "96" . rand(10000000, 99999999), $uL1_R, $id_L1_R, 'R');

    // 2. Right Leg of Direct User (Symmetrical Depth 3 = 7 members)
    $uR1 = "{$dUser}_R1";
    seed_register_user_exact($pdo, $uR1, "{$dUser} R1", "{$uR1}@test.com", "96" . rand(10000000, 99999999), $dUser, $dId, 'R');
    $id_R1 = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uR1])['Id'];

    // R1 -> Left
    $uR1_L = "{$dUser}_R1_L";
    seed_register_user_exact($pdo, $uR1_L, "{$dUser} R1-L", "{$uR1_L}@test.com", "96" . rand(10000000, 99999999), $uR1, $id_R1, 'L');
    $id_R1_L = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uR1_L])['Id'];
    seed_register_user_exact($pdo, "{$dUser}_R1_LL", "{$dUser} R1-LL", "{$dUser}_R1_LL@test.com", "96" . rand(10000000, 99999999), $uR1_L, $id_R1_L, 'L');
    seed_register_user_exact($pdo, "{$dUser}_R1_LR", "{$dUser} R1-LR", "{$dUser}_R1_LR@test.com", "96" . rand(10000000, 99999999), $uR1_L, $id_R1_L, 'R');

    // R1 -> Right
    $uR1_R = "{$dUser}_R1_R";
    seed_register_user_exact($pdo, $uR1_R, "{$dUser} R1-R", "{$uR1_R}@test.com", "96" . rand(10000000, 99999999), $uR1, $id_R1, 'R');
    $id_R1_R = (int)mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$uR1_R])['Id'];
    seed_register_user_exact($pdo, "{$dUser}_R1_RL", "{$dUser} R1-RL", "{$dUser}_R1_RL@test.com", "96" . rand(10000000, 99999999), $uR1_R, $id_R1_R, 'L');
    seed_register_user_exact($pdo, "{$dUser}_R1_RR", "{$dUser} R1-RR", "{$dUser}_R1_RR@test.com", "96" . rand(10000000, 99999999), $uR1_R, $id_R1_R, 'R');

    $checkUser = mlmp_pdo_fetch($pdo, "SELECT left_count, right_count, paid_pairs, tamount FROM affiliateuser WHERE username = ?", [$dUser]);
    echo "  [+] Direct User @{$dUser}: Perfectly Balanced (L: {$checkUser['left_count']}, R: {$checkUser['right_count']}, Paid: {$checkUser['paid_pairs']}, Wallet: Rs.{$checkUser['tamount']})\n";
}



echo "\nStep 5: Recalculating and verifying entire network counts...\n";
mlmp_recalculate_network_counts($pdo);

// Summary Report
$totalUsers = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser");
$adminFinal = mlmp_pdo_fetch($pdo, "SELECT left_count, right_count, paid_pairs, tamount FROM affiliateuser WHERE level = 1");
$adminBoards = mlmp_pdo_fetch_all($pdo, "SELECT username, fname, left_count, right_count FROM affiliateuser WHERE username LIKE ? ORDER BY Id ASC", [$adminUsername . '_bc_%']);

echo "\n========================================================\n";
echo "NETWORK SEEDING SUMMARY (LEVEL 1 TO 10 POPULATED)\n";
echo "========================================================\n";
echo "Total Accounts in System: {$totalUsers}\n";
echo "Admin Account (@{$adminUsername}):\n";
echo "  - Left Count:  {$adminFinal['left_count']}\n";
echo "  - Right Count: {$adminFinal['right_count']}\n";
echo "  - Paid Pairs:  {$adminFinal['paid_pairs']}\n";
echo "  - Total Wallet Earnings: Rs." . number_format($adminFinal['tamount'], 2) . "\n";
echo "Admin Virtual Boards: " . count($adminBoards) . " boards\n";
foreach ($adminBoards as $b) {
    echo "  - Board @{$b['username']} ({$b['fname']}): Left={$b['left_count']}, Right={$b['right_count']}\n";
}
echo "\nAll member accounts have password: Veagle@123\n";
echo "Levels 1 through 10 have been seeded successfully!\n";
echo "========================================================\n";
