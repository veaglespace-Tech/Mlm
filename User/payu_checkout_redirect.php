<?php
if(!isset($_SESSION)){
    session_start();
}

// Check if a payment is already in progress
if (isset($_SESSION['checkout_lock']) && $_SESSION['checkout_lock'] > time()) {
    $remaining = $_SESSION['checkout_lock'] - time();
    $minutes = ceil($remaining / 60);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment in Progress</title>
        <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 font-sans text-slate-800">
        <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center border border-slate-200">
            <div class="w-16 h-16 bg-amber-100 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h2 class="text-xl font-bold mb-3 text-slate-900">Payment Already in Progress</h2>
            <p class="text-slate-600 mb-6 text-sm leading-relaxed">
                We detected that you recently initiated a payment session. To prevent duplicate charges, please complete your transaction in the other window. 
                <br><br>
                If your previous attempt failed or you closed the window, you can try again in <strong class="text-amber-600"><?php echo $minutes; ?> minutes</strong>.
            </p>
            <div class="flex flex-col gap-3">
                <button onclick="window.history.back()" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors">
                    Go Back
                </button>
                <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm transition-colors">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Lock the checkout for 10 minutes (600 seconds)
$_SESSION['checkout_lock'] = time() + 600;

$payu_url = $_POST['PAYU_BASE_URL'] ?? '';
if(empty($payu_url)) {
    die("Invalid request.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Secure Payment...</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .box { text-align: center; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    </style>
</head>
<body onload="document.forms['payuForm'].submit()">
    <div class="box">
        <div class="loader"></div>
        <h2 style="color: #0f172a; margin-bottom: 10px;">Redirecting to Secure Gateway</h2>
        <p style="color: #64748b; font-size: 14px;">Please do not refresh or close this window.</p>
    </div>
    
    <form name="payuForm" action="<?php echo htmlspecialchars($payu_url, ENT_QUOTES, 'UTF-8'); ?>" method="post">
        <?php
        foreach ($_POST as $key => $value) {
            if ($key !== 'PAYU_BASE_URL') {
                echo '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
            }
        }
        ?>
    </form>
</body>
</html>
