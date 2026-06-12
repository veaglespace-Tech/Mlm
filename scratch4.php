<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$admin = mlmp_pdo_fetch($pdo, "SELECT * FROM admin LIMIT 1");
print_r($admin);
?>
