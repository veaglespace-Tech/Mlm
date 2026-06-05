<?php
if(!isset($_SESSION)){
    session_start();
}
include_once("z_db.php");

// Safety check: if no registered username in session, redirect to signup
if (!isset($_SESSION['reg_username'])) {
    header("Location: signup.php");
    exit;
}

$username = $_SESSION['reg_username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_product'])) {
    $_SESSION['selected_product'] = $_POST['selected_product'];
    header("Location: payu_payment.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Select Your Product</title>
<meta name="description" content="Select your premium starter package to proceed to checkout." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style type="text/css">
html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(180deg, #8fc8ea 0%, #c9e7f9 58%, #eef7fd 100%);
    background-attachment: fixed;
    overflow-y: auto;
    color: #425f75;
}
.product-card {
    border: 1px solid rgba(121, 163, 193, 0.34);
    background: rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 18px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    backdrop-filter: blur(10px);
}
.product-card:hover {
    border-color: rgba(99, 102, 241, 0.4);
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.7);
    box-shadow: 0 10px 30px rgba(72, 114, 142, 0.2);
}
.product-radio {
    display: none;
}
.product-card.selected {
    border-color: #6366f1 !important;
    background: rgba(99, 102, 241, 0.08) !important;
    box-shadow: 0 0 25px rgba(99, 102, 241, 0.15) !important;
}
.product-icon {
    font-size: 20px;
    color: #6366f1;
    background: rgba(99, 102, 241, 0.12);
    border-radius: 12px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}
.product-card.selected .product-icon {
    background: rgba(99, 102, 241, 0.25) !important;
    color: #818cf8 !important;
    transform: scale(1.05);
}
.product-info {
    flex-grow: 1;
    padding-right: 75px;
}
.product-badge {
    position: absolute;
    top: 18px;
    right: 18px;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.25);
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    flex-shrink: 0;
    transition: all 0.2s;
}
@media (max-width: 480px) {
    .product-card {
        padding: 14px 12px;
        gap: 12px;
    }
    .product-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
        border-radius: 10px;
    }
    .product-info {
        padding-right: 64px;
    }
    .product-badge {
        top: 12px;
        right: 12px;
        font-size: 8px;
        padding: 2px 8px;
    }
}
</style>
</head>
<body class="flex flex-col justify-between min-h-screen relative overflow-x-hidden">

<!-- Global Glowing Background Elements -->
<div class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] pointer-events-none z-0" style="background-color:rgba(123,174,210,0.28);animation:float 8s ease-in-out infinite;"></div>
<div class="absolute -bottom-40 right-20 w-[600px] h-[600px] rounded-full blur-[120px] pointer-events-none z-0" style="background-color:rgba(173,212,238,0.28);animation:float 12s ease-in-out infinite;"></div>
<div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] rounded-full blur-[90px] pointer-events-none z-0 opacity-40" style="background-color:rgba(123,174,210,0.28);animation:float 8s ease-in-out infinite;"></div>
<main class="flex-grow flex flex-col lg:flex-row relative z-10">
  
  <!-- Left Side: Premium Branding & Starter Kit Info -->
  <div class="hidden lg:flex lg:w-7/12 relative bg-transparent flex-col justify-between p-16 border-r transition-all duration-500" style="border-color:rgba(121,163,193,0.34);">
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full blur-3xl" style="background-color:rgba(123,174,210,0.2);"></div>
    <div class="absolute -bottom-40 right-20 w-96 h-96 rounded-full blur-3xl" style="background-color:rgba(173,212,238,0.2);"></div>
    
    <!-- Branding Header -->
    <div class="flex items-center gap-3 relative z-10">
      <span class="text-xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 bg-clip-text text-transparent">MLM Platform</span>
    </div>

    <!-- Hero Content -->
    <div class="my-auto relative z-10 max-w-lg">
      <span class="px-3 py-1 text-xs font-semibold text-purple-600 bg-purple-500/10 border border-purple-500/20 rounded-full inline-block mb-6 tracking-wide">STARTER KIT SELECTION</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6 tracking-tight" style="color:#0d1b2a;">
        Customize your premium kit.
      </h1>
      <p class="text-base lg:text-lg leading-relaxed mb-8" style="color:#425f75;">
        Choose one complimentary digital starter package included directly with your membership register. Accelerate your MLM network growth instantly.
      </p>

      <!-- Metrics -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="p-4 rounded-xl border" style="background:rgba(255,255,255,0.5);border-color:rgba(121,163,193,0.34);backdrop-filter:blur(8px);">
          <div class="text-lg font-extrabold" style="color:#0d1b2a;">Included</div>
          <div class="text-[10px] font-semibold tracking-wider uppercase mt-1" style="color:#748da1;">Starter Kit</div>
        </div>
        <div class="p-4 rounded-xl border" style="background:rgba(255,255,255,0.5);border-color:rgba(121,163,193,0.34);backdrop-filter:blur(8px);">
          <div class="text-purple-600 text-lg font-extrabold">INR 1,000</div>
          <div class="text-[10px] font-semibold tracking-wider uppercase mt-1" style="color:#748da1;">Package Price</div>
        </div>
        <div class="p-4 rounded-xl border" style="background:rgba(255,255,255,0.5);border-color:rgba(121,163,193,0.34);backdrop-filter:blur(8px);">
          <div class="text-lg font-extrabold" style="color:#0d1b2a;">Instant</div>
          <div class="text-[10px] font-semibold tracking-wider uppercase mt-1" style="color:#748da1;">Product Access</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="text-xs relative z-10" style="color:#748da1;">
      &copy; 2026 MLM Platform Inc. All rights reserved. Onboarding System.
    </div>
  </div>

  <!-- Right Side: Interactive Selection Form -->
  <div class="w-full lg:w-5/12 flex flex-col justify-center px-6 sm:px-16 lg:px-20 py-12 bg-transparent relative">
    <!-- Glowing background light for mobile -->
    <div class="lg:hidden absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl pointer-events-none" style="background-color:rgba(123,174,210,0.15);"></div>
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2 mb-10">
      <span class="text-md font-bold tracking-wider" style="color:#0d1b2a;">MLM Platform</span>
    </div>

    <div class="max-w-md w-full mx-auto relative z-10">
      <!-- Title Header -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold tracking-tight mb-2" style="color:#0d1b2a;">Select Your Product</h2>
        <p class="text-sm" style="color:#425f75;">Choose one starter pack below to activate your premium license.</p>
      </div>

      <!-- Interactive Radio Cards Form -->
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post" class="space-y-4">
        
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php
            $query = "SELECT id, name, description, icon, emoji FROM products WHERE active = 1 ORDER BY id ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $first = true;
            foreach ($products as $prod) {
                $prod_id = "prod" . $prod['id'];
                $prod_name_js = htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8');
                $selected_class = $first ? " selected" : "";
                $checked = $first ? " checked" : "";
                
                echo '<div class="product-card' . $selected_class . '" id="card_' . $prod_id . '" onclick="selectProduct(\'' . $prod_id . '\', \'' . addslashes($prod_name_js) . '\')">';
                echo '  <input type="radio" name="selected_product" id="' . $prod_id . '" value="' . htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') . '"' . $checked . ' class="product-radio">';
                echo '  <div class="product-icon">';
                if ($prod['icon']) {
                    echo '    <i class="' . htmlspecialchars($prod['icon'], ENT_QUOTES, 'UTF-8') . '"></i>';
                }
                if ($prod['emoji']) {
                    echo '    <span class="ml-1">' . htmlspecialchars($prod['emoji'], ENT_QUOTES, 'UTF-8') . '</span>';
                }
                echo '  </div>';
                echo '  <div class="product-info">';
                echo '    <h3 class="font-semibold text-sm m-0" style="color:#0d1b2a;">' . htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8') . '</h3>';
                echo '    <p class="text-xs mt-1 leading-relaxed" style="color:#425f75;">' . htmlspecialchars($prod['description'], ENT_QUOTES, 'UTF-8') . '</p>';
                echo '  </div>';
                echo '  <span class="product-badge">INCLUDED</span>';
                echo '</div>';
                
                $first = false;
            }
            ?>
        </div>

        <div class="pt-6 flex gap-3">
          <a href="signup.php?back=1" 
             class="flex-[0.6] text-center bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-sm transform active:scale-[0.98]" style="display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-arrow-left mr-2 text-xs"></i> Back
          </a>
          <button type="submit" 
                  class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl py-3 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-purple-500/10 hover:shadow-purple-500/20 transform active:scale-[0.98]">
            Next: Complete Payment <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
function selectProduct(id, val) {
    // Deselect all cards
    document.querySelectorAll('.product-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select clicked card and check radio
    document.getElementById('card_' + id).classList.add('selected');
    document.getElementById(id).checked = true;
}
</script>
<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
