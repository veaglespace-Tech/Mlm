<?php
include_once("z_db.php");
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

if (!headers_sent()) {
    header("refresh:2;url=users.php");
}

$username = trim($_GET['username'] ?? '');
if ($username === '' || !ctype_alnum($username)) {
    echo "<center>Invalid user<br/>Redirecting in 2 seconds...</center>";
    exit;
}

$user = mlmp_pdo_fetch($pdo, "SELECT launch FROM affiliateuser WHERE username = ? LIMIT 1", [$username]);
if (!$user) {
    echo "<center>User not found<br/>Redirecting in 2 seconds...</center>";
    exit;
}

if ((int)$user['launch'] === 1) {
    echo "<center>Already launched<br/>Redirecting in 2 seconds...</center>";
    exit;
}

if (mlmp_launch_profile($pdo, $username)) {
    echo "<center>Profile Activated + Binary Pair Commission Processed<br/>Redirecting in 2 seconds...</center>";
} else {
    echo "<center>Action failed due to a technical issue<br/>Redirecting in 2 seconds...</center>";
}
?>
