<?php
session_start();

// Only unset User-specific session variables.
// Do NOT call session_destroy() - it would wipe the shared session,
// logging out the Admin if both are open simultaneously in the same browser.
unset($_SESSION['username']);
unset($_SESSION['signup_data']);
unset($_SESSION['reg_username']);
unset($_SESSION['selected_product']);
unset($_SESSION['payu_txn']);
unset($_SESSION['checkout_lock']);
unset($_SESSION['reg_email_sent']);

// Jump to login page
header('Location: index.php');
exit;
?>
