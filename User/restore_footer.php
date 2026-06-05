<?php
include("z_db.php");
// Footer la punna "MLM Platform" karnyacha prayatna
$query = "UPDATE settings SET footer = 'Powered By - MLM Platform | Made With Love :)' WHERE sno = 0";
if(mysqli_query($con, $query)){
    echo "Footer Updated to MLM Platform";
} else {
    echo "Error: " . mysqli_error($con);
}
?>

