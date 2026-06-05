<?php
include_once ("z_db.php");
$userid = $_GET["username"] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="dark app">
<head>
<style>html {
    overflow-y: scroll; 
}</style>
<meta charset="utf-8" />
<title>Select Payment Gateway</title>
<meta name="description" content="app, web app, responsive, admin dashboard, admin, flat, flat ui, ui kit, off screen nav" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<link rel="stylesheet" href="css/app.v1.css" type="text/css" />
</head>
<body>
<section id="content" class="m-t-lg wrapper-md animated fadeInDown">
  <div class="text-center">
    <h2>Redirecting to payment gateway...</h2>
  </div>
</section>

<!-- Bootstrap -->
<!-- App -->
<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
