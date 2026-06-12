<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';

try {
    $sql = "UPDATE settings SET 
            smtp_enabled = 1,
            smtp_host = 'smtp.gmail.com',
            smtp_port = 465,
            smtp_username = 'abhijeet.veaglespace@gmail.com',
            smtp_password = 'olkpfpodnlrsnkdi',
            smtp_encryption = 'ssl'
            WHERE sno = 0";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "SMTP settings updated to SSL port 465 successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
