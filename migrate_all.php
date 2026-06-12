<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
echo "--- Running binary & pending schemas ---\n";
try {
    ensure_binary_schema($pdo);
    echo "Binary schema ensured successfully.\n";
} catch (Exception $e) {
    echo "Binary schema error: " . $e->getMessage() . "\n";
}

try {
    ensure_pending_schema($pdo);
    echo "Pending schema ensured successfully.\n";
} catch (Exception $e) {
    echo "Pending schema error: " . $e->getMessage() . "\n";
}

echo "\nAll migrations completed!\n";
?>
