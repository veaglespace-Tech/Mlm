<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$packages = mlmp_pdo_fetch_all($pdo, "SELECT * FROM packages");
$users = mlmp_pdo_fetch_all($pdo, "SELECT * FROM affiliateuser");
echo "Packages Count: " . count($packages) . "\n";
echo "Users Count: " . count($users) . "\n";
?>
