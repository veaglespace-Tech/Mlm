<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$pdo->exec("DELETE FROM affiliateuser WHERE username != 'adminadmin'");
$pdo->exec("DELETE FROM payu_payments");
$pdo->exec("DELETE FROM pairing_transactions");
$pdo->exec("DELETE FROM pending_registrations");
$pdo->exec("UPDATE affiliateuser SET left_count=0, right_count=0, paid_pairs=0, tamount=0 WHERE username='adminadmin'");
// Delete the admin pair virtual nodes
$pdo->exec("DELETE FROM affiliateuser WHERE username LIKE 'adminadmin_bc_%'");
echo "Database Reset Completed!";
?>
