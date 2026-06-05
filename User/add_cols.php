<?php
include 'z_db.php';
mysqli_query($con, 'ALTER TABLE affiliateuser ADD launch_time DATETIME NULL DEFAULT NULL');
mysqli_query($con, 'ALTER TABLE affiliateuser ADD is_binary_qualified TINYINT(1) NOT NULL DEFAULT 0');
echo "Columns added.";
?>
