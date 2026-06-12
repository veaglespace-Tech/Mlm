<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$admin = mlmp_pdo_fetch($pdo, "SELECT username, left_count, right_count, paid_pairs FROM affiliateuser WHERE level = 1");
$users = mlmp_pdo_fetch_all($pdo, "SELECT username, referedby, parent_id, position, left_count, right_count FROM affiliateuser WHERE level != 1");
print_r($admin);
print_r($users);
?>
