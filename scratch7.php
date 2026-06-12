<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$res = mlmp_launch_profile($pdo, 'abhijeet0905');
echo "Abhi launch result: " . ($res ? "true" : "false") . "\n";
$admin = mlmp_pdo_fetch($pdo, "SELECT username, left_count, right_count FROM affiliateuser WHERE level = 1");
print_r($admin);
?>
