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

function mlmp_upgrade_password_hash($con, $username, $password, $storedPassword) {
    if (password_get_info($storedPassword)['algoName'] !== 'unknown') {
        return;
    }

    $hashedPassword = mlmp_hash_password($password);
    $stmt = mysqli_prepare($con, "UPDATE affiliateuser SET password = ? WHERE username = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
