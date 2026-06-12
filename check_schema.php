<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM affiliateuser LIKE 'position'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
