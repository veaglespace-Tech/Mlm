<?php
require 'C:\Users\HP\Desktop\MLMP\User\z_db.php';
require 'C:\Users\HP\Desktop\MLMP\User\smtp_helper.php';

echo "Testing SMTP...\n";
$success = mlmp_send_mail('abhijeet.veaglespace@gmail.com', 'Test Email', 'This is a test email.');

if ($success) {
    echo "SMTP Email sent successfully!\n";
} else {
    echo "SMTP Email failed to send.\n";
}
?>
