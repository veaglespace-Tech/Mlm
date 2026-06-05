<?php
include_once("z_db.php");// database connection details stored here
include_once("../password_helper.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
        header("Location: index.php");
        exit;
}
// Collect the data from post method of form submission // 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$act=$_POST['act'] ?? '';
$username=$_POST['username'] ?? '';
$ear=$_POST['earnings'] ?? '';
$name=$_POST['fname'] ?? '';
$password=$_POST['password'] ?? '';
$email=$_POST['email'] ?? '';
$mobile=$_POST['mobile'] ?? '';
$ref=$_POST['refer'] ?? '';
$address=$_POST['address'] ?? '';
$country=$_POST['country'] ?? '';
$package=$_POST['package'] ?? '';
//collection ends
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sign Up Now</title>
<link rel="stylesheet" type="text/css" href="css/default.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
<script type="text/javascript" src="js/main.js"></script>
</head>
<?php
$check=1;
if($check==1){

$status = "OK";
$msg="";
//validation starts
// if userid is less than 6 char then status is not ok

$numrows = mlmp_pdo_count($pdo, "SELECT COUNT(*) FROM affiliateuser WHERE username = ?", [$ref]);
if ($numrows==0)
{
$msg=$msg."Sponsor/Referral Username Not found, Try Again.<BR>";
$status= "NOTOK";
}

if ( $password !== "" && strlen($password ?? '') < 8 ){
$msg=$msg."Password Must Be More Than 8 Char Length.<BR>";
$status= "NOTOK";}	

if ( strlen($address ?? '') < 8 ){
$msg=$msg."Please Provide The Correct Address, Your Cheque Will Be Send Here.<BR>";
$status= "NOTOK";}

if ( strlen($mobile ?? '') <> 10 ){
$msg=$msg."Please Enter 10 Digits Mobile No.<BR>";
$status= "NOTOK";}

if ( strlen($email ?? '') < 1 ){
$msg=$msg."Please Enter Your Email Id.<BR>";
$status= "NOTOK";}
			
if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email ?? '')){
$msg=$msg."Email Id Not Valid, Please Enter The Correct Email Id .<BR>";
$status= "NOTOK";}

if ( $country == "" ){
$msg=$msg."Please Enter Your Country Name.<BR>";
$status= "NOTOK";}	
}


if ($status=="OK") 
{
    $params = [
        $name,
        $address,
        $email,
        $ref,
        $mobile,
        $country,
        $ear,
        $package,
        $act
    ];
    
    if ($password !== "") {
        $hashedPassword = mlmp_hash_password($password);
        $sql = "UPDATE affiliateuser SET password = ?, fname = ?, address = ?, email = ?, referedby = ?, mobile = ?, country = ?, tamount = ?, pcktaken = ?, active = ? WHERE username = ?";
        $params[] = $username;
        array_unshift($params, $hashedPassword); // prepend password
    } else {
        $sql = "UPDATE affiliateuser SET fname = ?, address = ?, email = ?, referedby = ?, mobile = ?, country = ?, tamount = ?, pcktaken = ?, active = ? WHERE username = ?";
        $params[] = $username;
    }
    
    mlmp_pdo_execute($pdo, $sql, $params);

    print "
				<script language='javascript'>
					window.location = 'users.php';
				</script>
			";
}
else
{ 
echo "<font face='Verdana' size='2' color=red>" . mlmp_escape($msg) . "</font><br><input type='button' value='Retry' onClick='history.go(-1)'>"; //printing error if found in validation
}
} else {
    echo "Invalid request.";
}
?> 
</html>
