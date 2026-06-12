<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$stmt=$pdo->query("SELECT p.referedby, a.level FROM pending_registrations p LEFT JOIN affiliateuser a ON p.referedby = a.username WHERE p.username='abhijeet0905'");
var_dump($stmt->fetch());
?>
