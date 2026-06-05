<?php
include 'z_db.php';
$res = mysqli_query($con, 'SHOW COLUMNS FROM affiliateuser');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
