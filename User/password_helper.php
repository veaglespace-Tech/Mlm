<?php
function mlmp_hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function mlmp_password_matches($password, $storedPassword) {
    if (password_get_info($storedPassword)['algoName'] !== 'unknown') {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function mlmp_upgrade_password_hash($pdo, $username, $password, $storedPassword) {
    if (password_get_info($storedPassword)['algoName'] !== 'unknown') {
        return;
    }

    $hashedPassword = mlmp_hash_password($password);
    try {
        $stmt = $pdo->prepare("UPDATE affiliateuser SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
    } catch (Exception $e) {
        // Handle error or ignore
    }
}
?>
