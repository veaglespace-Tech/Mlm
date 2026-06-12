<?php
require 'C:\xampp\htdocs\MLMP\User\password_helper.php';
require 'C:\xampp\htdocs\MLMP\User\z_db.php';

$h = mlmp_hash_password('123123123');
$stmt = $pdo->prepare("UPDATE affiliateuser SET password = ? WHERE username = 'adminadmin'");
$stmt->execute([$h]);
echo 'Admin Password Hashed!';
