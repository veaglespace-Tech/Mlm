<?php
session_start();

// Only unset Admin-specific session variables.
// Do NOT call session_destroy() - it would wipe the shared session,
// logging out the User if both are open simultaneously in the same browser.
unset($_SESSION['adminidusername']);
unset($_SESSION['approval_msg']);
unset($_SESSION['product_message']);
unset($_SESSION['package_message']);

// Jump to admin login page
header('Location: index.php');
exit;
?>
