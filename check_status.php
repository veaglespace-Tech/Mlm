<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
$stmt = $pdo->query("SELECT username, admin_approval_status FROM pending_registrations");
while($row = $stmt->fetch()) {
    echo $row['username'] . " - " . $row['admin_approval_status'] . "\n";
}
?>
