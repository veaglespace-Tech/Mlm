<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
try {
    $pdo->exec("ALTER TABLE packages ADD COLUMN capping_status INT NOT NULL DEFAULT 1 AFTER capping_limit");
    echo "Added capping_status\n";
} catch (PDOException $e) { echo "capping_status exists or error: " . $e->getMessage() . "\n"; }
?>
