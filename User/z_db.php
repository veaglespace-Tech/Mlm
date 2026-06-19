<?php
// Load configuration from .env file
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $envVars = parse_ini_file($envPath);
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
    $host = $_ENV['DB_HOST'] ?? "localhost";
    $user = $_ENV['DB_USER'] ?? "root";
    $pass = $_ENV['DB_PASS'] ?? "";
    $db   = $_ENV['DB_NAME'] ?? "mlmp";
} else {
    // Fallback to defaults if .env is missing
    $host = "localhost";
    $user = "root";
    $pass = "Abhi@123";
    $db   = "mlmp";
}


// PDO Connection for modern, secure queries
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode = ''",
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Legacy mysqli connection for backward compatibility
$con = new mysqli($host, $user, $pass, $db);
mysqli_query($con, "SET sql_mode = ''");
if ($con->connect_errno) {
    echo "Failed to connect to MySQL: (" . $con->connect_errno . ") " . $con->connect_error;
}
// Global Helper Functions for Secure Queries
function mlmp_pdo_count($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function mlmp_pdo_fetch($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function mlmp_pdo_fetch_all($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function mlmp_pdo_execute($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function mlmp_escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function mlmp_build_referral_url(string $baseUrl, string $username): string {
    $baseUrl = trim($baseUrl);

    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $signupPath = '/User/signup.php';

        $appRoot = null;
        $dir = __DIR__;
        while ($dir && $dir !== dirname($dir)) {
            if (is_file($dir . DIRECTORY_SEPARATOR . 'User' . DIRECTORY_SEPARATOR . 'signup.php')) {
                $appRoot = $dir;
                break;
            }
            $dir = dirname($dir);
        }

        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($appRoot && $documentRoot !== '') {
            $signupFile = $appRoot . DIRECTORY_SEPARATOR . 'User' . DIRECTORY_SEPARATOR . 'signup.php';
            $signupFileReal = str_replace('\\', '/', realpath($signupFile) ?: $signupFile);
            $documentRootReal = rtrim(str_replace('\\', '/', realpath($documentRoot) ?: $documentRoot), '/');

            if (stripos($signupFileReal, $documentRootReal) === 0) {
                $signupPath = '/' . ltrim(substr($signupFileReal, strlen($documentRootReal)), '/');
            } elseif (preg_match('#(?:^|/)(htdocs|www|html)/(.+)$#i', $signupFileReal, $matches)) {
                $signupPath = '/' . $matches[2];
            }
        }

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $signupPath . '?aff=' . rawurlencode($username);
    }

    if ($baseUrl === '') {
        $baseUrl = 'http://localhost';
    } elseif (!preg_match('#^https?://#i', $baseUrl)) {
        $baseUrl = 'https://' . $baseUrl;
    }

    return rtrim($baseUrl, '/') . '/User/signup.php?aff=' . rawurlencode($username);
}

function ensure_binary_schema(PDO $pdo): void {
    $required = [
        'parent_id' => "ALTER TABLE affiliateuser ADD parent_id INT NULL DEFAULT NULL AFTER referedby",
        'position' => "ALTER TABLE affiliateuser ADD position ENUM('L','R') NULL DEFAULT NULL AFTER parent_id",
        'default_leg' => "ALTER TABLE affiliateuser ADD default_leg ENUM('L','R','AUTO') NOT NULL DEFAULT 'AUTO' AFTER position",
        'left_count' => "ALTER TABLE affiliateuser ADD left_count INT NOT NULL DEFAULT 0 AFTER default_leg",
        'right_count' => "ALTER TABLE affiliateuser ADD right_count INT NOT NULL DEFAULT 0 AFTER left_count",
        'paid_pairs' => "ALTER TABLE affiliateuser ADD paid_pairs INT NOT NULL DEFAULT 0 AFTER right_count",
        'launch_time' => "ALTER TABLE affiliateuser ADD launch_time DATETIME NULL DEFAULT NULL AFTER launch",
        'is_binary_qualified' => "ALTER TABLE affiliateuser ADD is_binary_qualified INT NOT NULL DEFAULT 0 AFTER launch_time",
    ];

    $existing = [];
    $rows = mlmp_pdo_fetch_all($pdo, "SHOW COLUMNS FROM affiliateuser");
    foreach ($rows as $row) {
        $existing[$row['Field']] = true;
    }

    foreach ($required as $column => $ddl) {
        if (!isset($existing[$column])) {
            $pdo->exec($ddl);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS pairing_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        tds_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        net_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        pairs_count INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pair_countdowns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pair_no INT NOT NULL,
        first_member_joined_at DATETIME NOT NULL,
        completed_at DATETIME NULL DEFAULT NULL,
        qualified TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY idx_user_pair (user_id, pair_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_package_schema(PDO $pdo): void {
    $required = [
        'binary_percent' => "ALTER TABLE packages ADD binary_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00 AFTER validity",
        'sponsor_percent' => "ALTER TABLE packages ADD sponsor_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER binary_percent",
        'capping_status' => "ALTER TABLE packages ADD capping_status TINYINT(1) NOT NULL DEFAULT 1 AFTER sponsor_percent",
        'capping_limit' => "ALTER TABLE packages ADD capping_limit INT NOT NULL DEFAULT 10 AFTER capping_status",
    ];

    $existing = [];
    $rows = mlmp_pdo_fetch_all($pdo, "SHOW COLUMNS FROM packages");
    foreach ($rows as $row) {
        $existing[$row['Field']] = true;
    }

    foreach ($required as $column => $ddl) {
        if (!isset($existing[$column])) {
            $pdo->exec($ddl);
        }
    }
}

function ensure_payout_schema(PDO $pdo): void {
    $required = [
        'reviewed_at' => "ALTER TABLE payments ADD reviewed_at DATETIME NULL DEFAULT NULL AFTER createdtime",
        'review_note' => "ALTER TABLE payments ADD review_note VARCHAR(255) NULL DEFAULT NULL AFTER reviewed_at",
    ];

    $existing = [];
    $rows = mlmp_pdo_fetch_all($pdo, "SHOW COLUMNS FROM payments");
    foreach ($rows as $row) {
        $existing[$row['Field']] = true;
    }

    foreach ($required as $column => $ddl) {
        if (!isset($existing[$column])) {
            $pdo->exec($ddl);
        }
    }
}

function ensure_pending_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(15) NOT NULL UNIQUE,
        password TEXT NOT NULL,
        fname TEXT NOT NULL,
        address TEXT NOT NULL,
        email TEXT NOT NULL,
        referedby VARCHAR(15) NOT NULL,
        mobile BIGINT(10) NOT NULL,
        country TEXT NOT NULL,
        ipaddress INT(10) UNSIGNED NOT NULL,
        doj DATE NOT NULL,
        signupcode TEXT NOT NULL,
        pcktaken INT(10) NOT NULL DEFAULT '0',
        expiry DATE NOT NULL DEFAULT '2199-12-31',
        admin_approval_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rejected_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(15) NOT NULL,
        fname TEXT NOT NULL,
        email TEXT NOT NULL,
        referedby VARCHAR(15) NOT NULL,
        mobile BIGINT(10) NOT NULL,
        country TEXT NOT NULL,
        rejected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_settings_referral_schema(PDO $pdo): void {
    $existing = [];
    $rows = mlmp_pdo_fetch_all($pdo, "SHOW COLUMNS FROM settings");
    foreach ($rows as $row) {
        $existing[$row['Field']] = true;
    }

    $required = [
        'referral_bonus_referrer' => "ALTER TABLE settings ADD referral_bonus_referrer DECIMAL(10,2) NOT NULL DEFAULT 50.00",
        'referral_bonus_joinee'   => "ALTER TABLE settings ADD referral_bonus_joinee DECIMAL(10,2) NOT NULL DEFAULT 25.00",
        'admin_referral_bonus'    => "ALTER TABLE settings ADD admin_referral_bonus DECIMAL(10,2) NOT NULL DEFAULT 25.00",
    ];

    foreach ($required as $column => $ddl) {
        if (!isset($existing[$column])) {
            $pdo->exec($ddl);
        }
    }
}

function process_pair_payout(PDO $pdo, int $userId, float $commissionPerPair, float $tdsRate, int $dailyLimit): void {
    $user = mlmp_pdo_fetch($pdo, "SELECT left_count, right_count, paid_pairs, level FROM affiliateuser WHERE Id = ? LIMIT 1", [$userId]);
    if (!$user) {
        return;
    }

    $left = (int)$user['left_count'];
    $right = (int)$user['right_count'];
    $paidPairs = (int)$user['paid_pairs'];
    $totalPairsEarned = min($left, $right);

    if ($totalPairsEarned <= $paidPairs) {
        return;
    }

    $level = (int)($user['level'] ?? 2);

    // Loop through each new completed pair
    for ($p = $paidPairs + 1; $p <= $totalPairsEarned; $p++) {
        // Fetch or create pair countdown
        $existingPair = mlmp_pdo_fetch($pdo, "SELECT id, first_member_joined_at, completed_at, qualified FROM pair_countdowns WHERE user_id = ? AND pair_no = ? LIMIT 1", [$userId, $p]);
        
        if (!$existingPair) {
            mlmp_pdo_execute($pdo, "INSERT INTO pair_countdowns (user_id, pair_no, first_member_joined_at, completed_at, qualified) VALUES (?, ?, NOW(), NOW(), 1)", [$userId, $p]);
            $isQualified = 1;
        } else {
            // If it exists but is not completed, we complete it now!
            if (empty($existingPair['completed_at'])) {
                $firstJoined = strtotime($existingPair['first_member_joined_at']);
                $dbNow = mlmp_pdo_fetch($pdo, "SELECT NOW() as db_now")['db_now'];
                $now = strtotime($dbNow);
                $isQualified = (($now - $firstJoined) <= 86400) ? 1 : 0;
                
                mlmp_pdo_execute($pdo, "UPDATE pair_countdowns SET completed_at = ?, qualified = ? WHERE id = ?", [$dbNow, $isQualified, $existingPair['id']]);
            } else {
                $isQualified = (int)$existingPair['qualified'];
            }
        }

        // Admin level 1 is always qualified
        if ($level === 1) {
            $isQualified = 1;
        }

        if ($isQualified === 1) {
            // Apply Daily Capping Logic for this pair
            $stmtCapping = $pdo->prepare("SELECT SUM(pairs_count) as today_pairs FROM pairing_transactions WHERE user_id = ? AND DATE(created_at) = CURDATE()");
            $stmtCapping->execute([$userId]);
            $cappingResult = $stmtCapping->fetch();
            $pairsToday = (int)($cappingResult['today_pairs'] ?? 0);

            if ($pairsToday < $dailyLimit) {
                $gross = $commissionPerPair;
                $tds = round($gross * $tdsRate, 2);
                $net = $gross - $tds;

                mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET tamount = tamount + ? WHERE Id = ?", [$net, $userId]);
                mlmp_pdo_execute($pdo, "INSERT INTO pairing_transactions (user_id, gross_amount, tds_amount, net_amount, pairs_count, created_at) VALUES (?, ?, ?, ?, 1, NOW())", [$userId, $gross, $tds, $net]);
            }
        }

        // Increment paid_pairs on affiliateuser
        mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET paid_pairs = ? WHERE Id = ?", [$p, $userId]);
    }
}

function mlmp_launch_profile(PDO $pdo, string $username): bool {
    try {
        ensure_binary_schema($pdo);
        ensure_package_schema($pdo);
        ensure_settings_referral_schema($pdo);
        
        $user = mlmp_pdo_fetch($pdo, "SELECT Id, launch, parent_id, position, referedby, pcktaken FROM affiliateuser WHERE username = ? LIMIT 1", [$username]);
        if (!$user) {
            return false;
        }

        if ((int)$user['launch'] === 1) {
            return true; // Already launched, avoid duplicate payments
        }

        $userId = (int)$user['Id'];
        
        // Start transaction if not already started
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        // Activate user and set launch = 1, and record the exact launch time for the 24-hour timer
        mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET active = 1, launch = 1, launch_time = NOW() WHERE Id = ?", [$userId]);

        // Fetch dynamic package settings (for binary commission)
        $pkgId = (int)$user['pcktaken'];
        $pkg = mlmp_pdo_fetch($pdo, "SELECT price, binary_percent, capping_limit, capping_status FROM packages WHERE id = ?", [$pkgId]);
        
        $price = (float)($pkg['price'] ?? 1000);
        $binaryPercent = (float)($pkg['binary_percent'] ?? 30);
        $dailyLimit = (int)($pkg['capping_limit'] ?? 10);
        $cappingStatus = (int)($pkg['capping_status'] ?? 1);

        $commissionPerPair = round($price * ($binaryPercent / 100), 2);
        $tdsRate = 0.05;

        // ── Dynamic Referral Bonus (from admin settings) ──────────────────────
        // Fetch admin-configured referral bonus amounts
        $bonusSettings = mlmp_pdo_fetch($pdo, "SELECT referral_bonus_referrer, admin_referral_bonus FROM settings LIMIT 1");
        $referrerBonus = (float)($bonusSettings['referral_bonus_referrer'] ?? 50.00);
        $adminBonus    = (float)($bonusSettings['admin_referral_bonus']   ?? 25.00);

        // 1. Give referral bonus to the REFERRER (sponsor)
        $sponsorUsername = $user['referedby'];
        if (!empty($sponsorUsername) && $sponsorUsername !== 'none' && $referrerBonus > 0) {
            mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET tamount = tamount + ? WHERE username = ?", [$referrerBonus, $sponsorUsername]);
        }

        // 2. Give dynamic referral/joining bonus to the ADMIN
        if ($adminBonus > 0) {
            $adminUser = mlmp_pdo_fetch($pdo, "SELECT username FROM affiliateuser WHERE level = 1 LIMIT 1");
            $adminUsername = $adminUser['username'] ?? 'adminadmin';
            mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET tamount = tamount + ? WHERE username = ?", [$adminBonus, $adminUsername]);
        }
        // ─────────────────────────────────────────────────────────────────────

        // Propagate one active node to all uplines on corresponding leg.
        $ancestorId = (int)$user['parent_id'];
        $pathSide = $user['position'];

        while ($ancestorId > 0 && ($pathSide === 'L' || $pathSide === 'R')) {
            if ($pathSide === 'L') {
                mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET left_count = left_count + 1 WHERE Id = ?", [$ancestorId]);
            } else {
                mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET right_count = right_count + 1 WHERE Id = ?", [$ancestorId]);
            }

            // Fetch new counts to update/insert pair_countdowns
            $ancestorUser = mlmp_pdo_fetch($pdo, "SELECT left_count, right_count FROM affiliateuser WHERE Id = ? LIMIT 1", [$ancestorId]);
            $newL = (int)($ancestorUser['left_count'] ?? 0);
            $newR = (int)($ancestorUser['right_count'] ?? 0);
            $maxCount = max($newL, $newR);

            // Register countdown for the new pair started (if any)
            for ($p = 1; $p <= $maxCount; $p++) {
                $existingPair = mlmp_pdo_fetch($pdo, "SELECT id FROM pair_countdowns WHERE user_id = ? AND pair_no = ? LIMIT 1", [$ancestorId, $p]);
                if (!$existingPair) {
                    // Start countdown for pair p
                    mlmp_pdo_execute($pdo, "INSERT INTO pair_countdowns (user_id, pair_no, first_member_joined_at) VALUES (?, ?, NOW())", [$ancestorId, $p]);
                    
                    // If min count is already >= p, complete it immediately (e.g. bulk joins / both sides active)
                    if (min($newL, $newR) >= $p) {
                        mlmp_pdo_execute($pdo, "UPDATE pair_countdowns SET completed_at = NOW(), qualified = 1 WHERE user_id = ? AND pair_no = ?", [$ancestorId, $p]);
                    }
                }
            }

            $effectiveDailyLimit = ($cappingStatus === 0) ? 999999 : $dailyLimit;
            process_pair_payout($pdo, $ancestorId, $commissionPerPair, $tdsRate, $effectiveDailyLimit);

            $ancestor = mlmp_pdo_fetch($pdo, "SELECT parent_id, position FROM affiliateuser WHERE Id = ? LIMIT 1", [$ancestorId]);
            if (!$ancestor) {
                break;
            }
            $pathSide = $ancestor['position'];
            $ancestorId = (int)($ancestor['parent_id'] ?? 0);
        }

        if ($ownTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if (isset($ownTransaction) && $ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("mlmp_launch_profile Error for user {$username}: " . $e->getMessage());
        return false;
    }
}

/**
 * Recalculates the left_count and right_count for all users in the binary tree.
 * This is a permanent and proper fix for when DB entries are cut/deleted.
 */
function mlmp_recalculate_network_counts(PDO $pdo): void {
    // 1. Reset all counts
    mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET left_count = 0, right_count = 0");

    // 2. Fetch all nodes with a parent
    $users = mlmp_pdo_fetch_all($pdo, "SELECT Id, parent_id, position FROM affiliateuser WHERE parent_id IS NOT NULL AND parent_id > 0");

    $parent_map = [];
    $position_map = [];
    foreach($users as $u) {
        $parent_map[$u['Id']] = (int)$u['parent_id'];
        $position_map[$u['Id']] = $u['position'];
    }

    $updates_left = [];
    $updates_right = [];

    foreach($users as $u) {
        $parent_id = (int)$u['parent_id'];
        $position = $u['position'];

        while($parent_id > 0 && ($position === 'L' || $position === 'R')) {
            if ($position === 'L') {
                if (!isset($updates_left[$parent_id])) $updates_left[$parent_id] = 0;
                $updates_left[$parent_id]++;
            } else {
                if (!isset($updates_right[$parent_id])) $updates_right[$parent_id] = 0;
                $updates_right[$parent_id]++;
            }

            if (!isset($parent_map[$parent_id])) {
                break;
            }
            $position = $position_map[$parent_id];
            $parent_id = $parent_map[$parent_id];
        }
    }

    $pdo->beginTransaction();
    try {
        $stmtL = $pdo->prepare("UPDATE affiliateuser SET left_count = ? WHERE Id = ?");
        foreach($updates_left as $id => $count) {
            $stmtL->execute([$count, $id]);
        }
        $stmtR = $pdo->prepare("UPDATE affiliateuser SET right_count = ? WHERE Id = ?");
        foreach($updates_right as $id => $count) {
            $stmtR->execute([$count, $id]);
        }
        $pdo->commit();
    } catch(Exception $e) {
        $pdo->rollBack();
        error_log("mlmp_recalculate_network_counts Error: " . $e->getMessage());
    }
}
