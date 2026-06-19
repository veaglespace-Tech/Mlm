<?php
require __DIR__ . '/User/z_db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM affiliateuser LIKE 'position'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
