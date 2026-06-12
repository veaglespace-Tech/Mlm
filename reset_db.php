<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';

try {
    // 1. Delete all users except Admin (level = 1)
    $stmt = $pdo->prepare("DELETE FROM affiliateuser WHERE level != 1");
    $stmt->execute();
    
    // Reset auto-increment so next ID is 2
    $pdo->exec("ALTER TABLE affiliateuser AUTO_INCREMENT = 1");

    // 2. Reset admin counters
    $stmt = $pdo->prepare("UPDATE affiliateuser SET left_count = 0, right_count = 0, paid_pairs = 0, tamount = 0 WHERE level = 1");
    $stmt->execute();

    // 3. Truncate transaction tables
    $pdo->exec("TRUNCATE TABLE payments");
    $pdo->exec("TRUNCATE TABLE payu_payments");
    $pdo->exec("TRUNCATE TABLE pending_registrations");
    $pdo->exec("TRUNCATE TABLE pairing_transactions");

    echo "Database successfully reset. Only Admin remains. All counters are 0.\n";

} catch (Exception $e) {
    echo "Failed to reset database: " . $e->getMessage() . "\n";
}
?>
