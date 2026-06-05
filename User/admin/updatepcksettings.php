<?php
session_start();
include('z_db.php');

if (!isset($_SESSION['adminidusername'])) {
    header("Location: index.php");
    exit;
}

function redirect_with_message($message) {
    $_SESSION['package_message'] = $message;
    header("Location: pacsettings.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message("Invalid request.");
}

$pidmain = (int)($_POST['pckmainid'] ?? 0);
$pname = trim($_POST['pckname'] ?? '');
$pdetail = trim($_POST['pckdetail'] ?? '');
$pprice = (float)($_POST['pckprice'] ?? 0);
$pcurid = trim($_POST['currency'] ?? '');
$pckmpay = (float)($_POST['pckmpay'] ?? 0);
$pcksbonus = (float)($_POST['pcksbonus'] ?? 0);
$pcktax = (float)($_POST['pcktax'] ?? 0);
$pact = (int)($_POST['pckact'] ?? 0);
$renewdays = (int)($_POST['renewdays'] ?? 0);

$binary_percent = (float)($_POST['binary_percent'] ?? 30);
$sponsor_percent = (float)($_POST['sponsor_percent'] ?? 10);
$capping_limit = (int)($_POST['capping_limit'] ?? 10);

$levels = [];

for ($i = 1; $i <= 20; $i++) {
    $levels[] = (float)($_POST["lev{$i}"] ?? 0);
}

$pact = $pact === 1 ? 1 : 0;

$errors = [];
if ($pidmain <= 0) {
    $errors[] = "Invalid package selected.";
}
if (strlen($pname) < 2) {
    $errors[] = "Package name should have minimum 2 characters.";
}
if (strlen($pdetail) < 4) {
    $errors[] = "Package details should have minimum 4 characters.";
}
if ($pcurid === '') {
    $errors[] = "Please select a currency.";
}
if ($pprice < 0 || $pcktax < 0 || $pckmpay < 0 || $pcksbonus < 0 || $renewdays <= 0) {
    $errors[] = "Package amount and validity values are invalid.";
}

if ($errors) {
    redirect_with_message(implode("<br>", $errors));
}

$sql = "UPDATE packages SET
    name = ?, price = ?, currency = ?, details = ?, tax = ?, mpay = ?, sbonus = ?, active = ?,
    binary_percent = ?, sponsor_percent = ?, capping_limit = ?,
    level1 = ?, level2 = ?, level3 = ?, level4 = ?, level5 = ?, level6 = ?, level7 = ?, level8 = ?, level9 = ?, level10 = ?,
    level11 = ?, level12 = ?, level13 = ?, level14 = ?, level15 = ?, level16 = ?, level17 = ?, level18 = ?, level19 = ?, level20 = ?,
    gateway = 0, validity = ?
    WHERE id = ?";

$params = array_merge([$pname, $pprice, $pcurid, $pdetail, $pcktax, $pckmpay, $pcksbonus, $pact, $binary_percent, $sponsor_percent, $capping_limit], $levels, [$renewdays, $pidmain]);
$updated = mlmp_pdo_execute($pdo, $sql, $params);

redirect_with_message($updated ? "Package updated successfully." : "Could not update package. Please try again.");
?>
