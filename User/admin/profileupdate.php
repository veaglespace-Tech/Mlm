<?php
include_once("z_db.php");// database connection details stored here
include_once("../password_helper.php");
// Inialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
}
header( "refresh:3;url=profile.php" );

// Collect the data from post method of form submission // 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$name = $_POST['fullname'] ?? '';
$emaill = $_POST['email'] ?? '';
$addrs = $_POST['address'] ?? '';
$cntry = $_POST['country'] ?? '';
$p1 = $_POST['p1'] ?? '';
$p2 = $_POST['p2'] ?? '';
$bankname = $_POST['bankname'] ?? '';
$accname = $_POST['accname'] ?? '';
$accno = $_POST['accno'] ?? '';
$ifsccode = $_POST['ifsccode'] ?? '';
$acctype = $_POST['acctype'] ?? '';
//collection ends
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>update profile</title>
<link rel="stylesheet" type="text/css" href="css/default.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<div id="google_translate_element" align="right"></div><script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, multilanguagePage: true}, 'google_translate_element');
}
</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
        
</head>
<?php
$check=1;
if($check==1){

$status = "OK";
$msg="";
//validation starts
// if userid is less than 6 char then status is not ok

if ( ($p1 !== "" || $p2 !== "") && strlen($p1) < 8 ){
$msg=$msg."Password Must Be More Than 8 Char Length.<BR>";
$status= "NOTOK";}	

if ( strlen($cntry) < 2 ){
$msg=$msg."Country Must Be More Than 2 Char Length.<BR>";
$status= "NOTOK";}

if ( strlen($addrs) < 2 ){
$msg=$msg."Address Must Be More Than 5 Char Length.<BR>";
$status= "NOTOK";}

if ( strlen($emaill) < 3 ){
$msg=$msg."Email Must Be More Than 3 Char Length.<BR>";
$status= "NOTOK";}

if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $emaill)){
$msg=$msg."Email Id Not Valid, Please Enter The Correct Email Id. This id will be used in case of recovering of password<BR>";
$status= "NOTOK";}	

if ( ($p1 !== "" || $p2 !== "") && strlen($p2) < 8 ){
$msg=$msg."Password Must Be More Than 8 Char Length.<BR>";
$status= "NOTOK";}

if ( ($p1 !== "" || $p2 !== "") && $p1 !== $p2 ){
$msg=$msg."Password Does Not Match.<BR>";
$status= "NOTOK";}

if ( strlen($name) < 2 ){
$msg=$msg."Name should contain 2 chars.<BR>";
$status= "NOTOK";}
}

if ($status=="OK") 
{
    if ($p1 !== "") {
        $hashedPassword = mlmp_hash_password($p1);
        $query = mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET password = ?, fname = ?, email = ?, country = ?, address = ?, bankname = ?, accountname = ?, accountno = ?, accounttype = ?, ifsccode = ? WHERE username = ?", [
            $hashedPassword, $name, $emaill, $cntry, $addrs, $bankname, $accname, $accno, $acctype, $ifsccode, $_SESSION['adminidusername']
        ]);
    } else {
        $query = mlmp_pdo_execute($pdo, "UPDATE affiliateuser SET fname = ?, email = ?, country = ?, address = ?, bankname = ?, accountname = ?, accountno = ?, accounttype = ?, ifsccode = ? WHERE username = ?", [
            $name, $emaill, $cntry, $addrs, $bankname, $accname, $accno, $acctype, $ifsccode, $_SESSION['adminidusername']
        ]);
    }

print "<script>Swal.fire({title: 'Updated!', text: 'Redirecting back to profile...', icon: 'success', timer: 3000, showConfirmButton: false});</script>";

}

else
{ 
echo "<script>Swal.fire({title: 'Error!', html: '".$msg."', icon: 'error'}).then(function() { history.go(-1); });</script>"; //printing error if found in validation
}
} else {
    echo "Invalid request.";
}
?> 
</html>
