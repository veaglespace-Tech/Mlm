<?php
// schema_migrations.php - Handles dynamic table and column creation

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

function ensure_username_length_schema(PDO $pdo): void {
    try {
        $pdo->exec("ALTER TABLE affiliateuser MODIFY username VARCHAR(50) NOT NULL");
        $pdo->exec("ALTER TABLE affiliateuser MODIFY referedby VARCHAR(50) NOT NULL DEFAULT 'none'");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE pending_registrations MODIFY username VARCHAR(50) NOT NULL");
        $pdo->exec("ALTER TABLE pending_registrations MODIFY referedby VARCHAR(50) NOT NULL");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE rejected_registrations MODIFY username VARCHAR(50) NOT NULL");
        $pdo->exec("ALTER TABLE rejected_registrations MODIFY referedby VARCHAR(50) NOT NULL");
    } catch (Exception $e) {}
}

