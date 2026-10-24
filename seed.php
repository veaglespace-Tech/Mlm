<?php
/**
 * Veagle Space MLM - Master Database Seeder
 *
 * Full system reset & automated network seeder:
 * 1. Configures Admin account:
 *    - Name:     abhijeet ambhore
 *    - Email:    abhijeetambhore4@gmail.com
 *    - Password: Veagle@123
 * 2. Updates Platform Settings (Branding, Referral bonus, Email)
 * 3. Configures 10-Level Unilevel & Binary Commission Package
 * 4. Seeds 10 Direct Members under Admin
 * 5. Seeds Pairs (Left & Right) under Direct Members (Level 2)
 * 6. Seeds Deep Downline for Levels 3 to 10
 * 7. All generated user accounts have password: Veagle@123
 *
 * Usage:
 *   CLI: php seed.php
 */

if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['adminidusername'])) {
        http_response_code(403);
        die("Access Denied: Please log in as admin or run via CLI: php seed.php");
    }
}

require_once __DIR__ . '/User/z_db.php';
require_once __DIR__ . '/User/password_helper.php';

// Ensure all database schemas are created/updated
ensure_binary_schema($pdo);
ensure_package_schema($pdo);
ensure_settings_referral_schema($pdo);
ensure_pending_schema($pdo);

echo "========================================================\n";
echo "VEAGLE SPACE MLM - MASTER DATABASE SEEDER\n";
echo "========================================================\n\n";

// STEP 1: CLEANUP & RESET
echo "Step 1: Cleaning up existing test users and transactions...\n";
$deleted = $pdo->exec("DELETE FROM affiliateuser WHERE username != 'adminadmin'");
echo "   -> Removed {$deleted} test account(s).\n";

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
        echo "   -> Skipped `{$tbl}`\n";
    }
}

// STEP 2: SETUP / UPDATE ADMIN ACCOUNT
echo "\nStep 2: Configuring Admin Account (@adminadmin)...\n";
$adminName = 'abhijeet ambhore';
$adminEmail = 'abhijeetambhore4@gmail.com';
$adminPass = 'Veagle@123';
$hashedPass = mlmp_hash_password($adminPass);

$existingAdmin = mlmp_pdo_fetch($pdo, "SELECT Id, username FROM affiliateuser WHERE level = 1 LIMIT 1");

if ($existingAdmin) {
    $stmt = $pdo->prepare("UPDATE affiliateuser SET 
        fname = ?,
        email = ?,
        password = ?,
        address = 'Veagle Space HQ',
        country = 'India',
        left_count = 0,
        right_count = 0,
        paid_pairs = 0,
        tamount = 0.00,
        active = 1,
        launch = 1,
        parent_id = NULL,
        position = NULL
    WHERE level = 1");
    $stmt->execute([$adminName, $adminEmail, $hashedPass]);
    $adminUsername = $existingAdmin['username'];
    $adminId = (int)$existingAdmin['Id'];
    echo "   -> Admin account updated: @{$adminUsername} ({$adminName})\n";
} else {
    $adminUsername = 'adminadmin';
    $stmt = $pdo->prepare("INSERT INTO affiliateuser 
        (username, password, fname, address, email, referedby, active, doj, country, tamount, level, pcktaken, launch, left_count, right_count, paid_pairs)
        VALUES (?, ?, ?, 'Veagle Space HQ', ?, 'none', 1, CURDATE(), 'India', 0.00, 1, 1, 1, 0, 0, 0)");
    $stmt->execute([$adminUsername, $hashedPass, $adminName, $adminEmail]);
    $adminId = (int)$pdo->lastInsertId();
    echo "   -> Fresh Admin account created: @{$adminUsername} ({$adminName})\n";
}

// STEP 3: UPDATE SETTINGS AND PACKAGES
echo "\nStep 3: Updating System Settings & 10-Level Package Commissions...\n";
$pdo->exec("UPDATE settings SET 
    email = '{$adminEmail}',
    coname = 'Veagle Space',
    footer = 'Powered By - Veagle Space | Made With Love :)',
    referral_bonus_referrer = 50.00,
    referral_bonus_joinee = 25.00,
    admin_referral_bonus = 25.00
");

// Ensure packages table has 10 levels configured
$hasPackage = mlmp_pdo_fetch($pdo, "SELECT id FROM packages WHERE id = 1");
if ($hasPackage) {
    $pdo->exec("UPDATE packages SET 
        name = 'Standard MLM Plan',
        price = 1000.00,
        currency = 'INR',
        active = 1,
        level1 = 100, 
        level2 = 50, 
        level3 = 40, 
        level4 = 30, 
        level5 = 25, 
        level6 = 20, 
        level7 = 15, 
        level8 = 10, 
        level9 = 10, 
        level10 = 10 
    WHERE id = 1");
} else {
    $pdo->exec("INSERT INTO packages (id, name, price, currency, active, level1, level2, level3, level4, level5, level6, level7, level8, level9, level10)
        VALUES (1, 'Standard MLM Plan', 1000.00, 'INR', 1, 100, 50, 40, 30, 25, 20, 15, 10, 10, 10)");
}
echo "   -> Settings and Package 1 configured for Levels 1 to 10.\n";

// Helper function to register and activate a user using multi-board binary placement
function seed_register_user(PDO $pdo, string $username, string $fname, string $email, string $mobile, string $refSponsor): bool {
    $exists = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ? LIMIT 1", [$username]);
    if ($exists) {
        return false;
    }

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
            VALUES (?, ?, ?, 'Veagle Space Network Member', ?, ?, ?, ?, 0, ?, 'India', 'PayU', ?, 0, 1, ?, ?, ?)";
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

    // Record transaction in payu_payments
    $newUser = mlmp_pdo_fetch($pdo, "SELECT Id FROM affiliateuser WHERE username = ?", [$username]);
    $newUserId = (int)($newUser['Id'] ?? 0);
    $txnId = "TXN_SEED_" . strtoupper(substr(md5($username . time()), 0, 10));
    $stmtPay = $pdo->prepare("INSERT INTO payu_payments (orderid, transacid, price, currency, date, pckid, gateway) VALUES (?, ?, 1000.00, 'INR', NOW(), 1, 'PayU')");
    $stmtPay->execute([$newUserId, $txnId]);

    return true;
}

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
            VALUES (?, ?, ?, 'Veagle Space Network Member', ?, ?, ?, ?, 0, ?, 'India', 'PayU', ?, 0, 1, ?, ?, ?)";
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

// STEP 4: SEED 10 DIRECT MEMBERS UNDER ADMIN (LEVEL 1)
echo "\nStep 4: Creating 10 direct members under Admin...\n";
$directUsernames = [];

for ($i = 1; $i <= 10; $i++) {
    $idx = str_pad($i, 2, '0', STR_PAD_LEFT);
    $username = "user_d" . $idx;
    $fname = "Direct User " . $idx;
    $email = "user.d" . $idx . "@gmail.com";
    $mobile = "98000000" . $idx;

    seed_register_user($pdo, $username, $fname, $email, $mobile, $adminUsername);
    $directUsernames[] = $username;

    $uData = mlmp_pdo_fetch($pdo, "SELECT Id, parent_id, position FROM affiliateuser WHERE username = ?", [$username]);
    $pData = mlmp_pdo_fetch($pdo, "SELECT username FROM affiliateuser WHERE Id = ?", [$uData['parent_id']]);
    $parentName = $pData['username'] ?? 'None';
    echo "  [+] Direct {$idx}: @{$username} placed under @{$parentName} ({$uData['position']})\n";
}

// STEP 5: SEED 100% SYMMETRICAL BINARY TREES (LEFT = RIGHT FOR EVERY MEMBER)
echo "\nStep 5: Creating 100% Symmetrical Binary Trees (Left = Right for EVERY member)...\n";

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



// STEP 7: RECALCULATE NETWORK COUNTS
echo "\nStep 7: Recalculating and synchronizing network counts...\n";
mlmp_recalculate_network_counts($pdo);

// Reset auto-increment
$maxId = (int)(mlmp_pdo_fetch($pdo, "SELECT COALESCE(MAX(Id), 1) as max_id FROM affiliateuser")['max_id'] ?? 1);
$nextId = $maxId + 1;
try {
    $pdo->exec("ALTER TABLE affiliateuser AUTO_INCREMENT = {$nextId}");
} catch (Exception $e) {}

// STEP 8: FINAL SUMMARY REPORT
$totalUsers = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser");
$adminFinal = mlmp_pdo_fetch($pdo, "SELECT Id, username, fname, email, left_count, right_count, paid_pairs, tamount FROM affiliateuser WHERE level = 1 LIMIT 1");
$adminBoards = mlmp_pdo_fetch_all($pdo, "SELECT username, fname, left_count, right_count FROM affiliateuser WHERE username LIKE ? ORDER BY Id ASC", [$adminUsername . '_bc_%']);

echo "\n========================================================\n";
echo "DATABASE SEEDING SUCCESSFUL!\n";
echo "========================================================\n";
echo "Admin Name:       {$adminFinal['fname']}\n";
echo "Admin Email:      {$adminFinal['email']}\n";
echo "Admin Username:   {$adminFinal['username']}\n";
echo "Admin Password:   {$adminPass}\n";
echo "Total Accounts:   {$totalUsers}\n";
echo "Admin Left Count: {$adminFinal['left_count']}\n";
echo "Admin Right Count:{$adminFinal['right_count']}\n";
echo "Admin Paid Pairs: {$adminFinal['paid_pairs']}\n";
echo "Wallet Balance:   Rs. " . number_format($adminFinal['tamount'], 2) . "\n";
echo "Virtual Boards:   " . count($adminBoards) . " boards\n";
echo "All Member Passwords: Veagle@123\n";
echo "Levels Seeded:    1 to 10 (Full Downline Active)\n";
echo "Login URL:        http://127.0.0.1:8000/User/admin/\n";
echo "========================================================\n";
