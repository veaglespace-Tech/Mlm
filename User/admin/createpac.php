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

$pname = trim($_POST['pckname'] ?? '');
$pdetail = trim($_POST['pckdetail'] ?? '');
$pprice = (float)($_POST['pckprice'] ?? 0);
$pcurid = trim($_POST['currency'] ?? '');
$pckmpay = (float)($_POST['pckmpay'] ?? 0);
$pcksbonus = (float)($_POST['pcksbonus'] ?? 0);
$pcktax = (float)($_POST['pcktax'] ?? 0);
$renewdays = (int)($_POST['renewdays'] ?? 0);
$levels = [];

for ($i = 1; $i <= 20; $i++) {
    $levels[] = (float)($_POST["lev{$i}"] ?? 0);
}

$errors = [];
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

$sql = "INSERT INTO packages (
    name, price, currency, details, tax, mpay, sbonus, cdate, active,
    level1, level2, level3, level4, level5, level6, level7, level8, level9, level10,
    level11, level12, level13, level14, level15, level16, level17, level18, level19, level20,
    gateway, validity
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1,
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    0, ?
)";

$params = array_merge([$pname, $pprice, $pcurid, $pdetail, $pcktax, $pckmpay, $pcksbonus], $levels, [$renewdays]);
$created = mlmp_pdo_execute($pdo, $sql, $params);

redirect_with_message($created ? "Package created successfully." : "Could not create package. Please try again.");
?>
