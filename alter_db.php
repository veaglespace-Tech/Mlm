<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';

try {
    $pdo->exec("ALTER TABLE pending_registrations ADD COLUMN admin_approval_status ENUM('Pending', 'Approved') DEFAULT 'Pending'");
    echo "Column added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
