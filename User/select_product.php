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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_package'])) {
    $pkg_id = $_POST['selected_package'];
    $_SESSION['signup_data']['package'] = $pkg_id;
    
    // Fetch package details
    $stmt = $pdo->prepare("SELECT name FROM packages WHERE id = ?");
    $stmt->execute([$pkg_id]);
    $pkg = $stmt->fetch();
    if($pkg) {
        $_SESSION['selected_product'] = $pkg['name'];
    } else {
        $_SESSION['selected_product'] = 'Membership Package';
    }
    
    header("Location: payu_payment.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>MLM Platform - Select Your Membership Package</title>
<meta name="description" content="Select your premium membership package to proceed to checkout." />
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
    border: 2px solid rgba(226, 232, 240, 0.8);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    border-radius: 20px;
    padding: 24px 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    overflow: hidden;
}
.product-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 6px;
    background: transparent;
    transition: all 0.3s ease;
}
.product-card:hover {
    border-color: rgba(99, 102, 241, 0.5);
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 15px 35px rgba(99, 102, 241, 0.15);
}
.product-radio {
    display: none;
}
.product-card.selected {
    border-color: #6366f1 !important;
    background: #ffffff !important;
    box-shadow: 0 15px 40px rgba(99, 102, 241, 0.25) !important;
}
.product-card.selected::before {
    background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899);
}
.product-icon {
    font-size: 28px;
    color: #6366f1;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
    border-radius: 50%;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s;
}
.product-card.selected .product-icon {
    background: linear-gradient(135deg, #6366f1, #a855f7) !important;
    color: #ffffff !important;
    transform: scale(1.1);
    box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
}
.product-info {
    width: 100%;
}
.product-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 6px 12px;
    border-radius: 9999px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 10;
}
.product-card.selected .product-badge {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}
@media (max-width: 480px) {
    .product-card {
        padding: 20px 16px;
        gap: 12px;
    }
    .product-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    .product-badge {
        top: 12px;
        right: 12px;
        font-size: 9px;
        padding: 4px 10px;
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
      <span class="px-3 py-1 text-xs font-semibold text-purple-600 bg-purple-500/10 border border-purple-500/20 rounded-full inline-block mb-6 tracking-wide">MEMBERSHIP SELECTION</span>
      <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6 tracking-tight" style="color:#0d1b2a;">
        Choose your plan.
      </h1>
      <p class="text-base lg:text-lg leading-relaxed mb-8" style="color:#425f75;">
        Select the membership package that best fits your goals. Start your journey and accelerate your MLM network growth instantly.
      </p>

      <!-- Metrics -->
      <div class="grid grid-cols-3 gap-4 pt-4">
        <div class="p-4 rounded-xl border" style="background:rgba(255,255,255,0.5);border-color:rgba(121,163,193,0.34);backdrop-filter:blur(8px);">
          <div class="text-lg font-extrabold" style="color:#0d1b2a;">Premium</div>
          <div class="text-[10px] font-semibold tracking-wider uppercase mt-1" style="color:#748da1;">Plans</div>
        </div>
        <div class="p-4 rounded-xl border" style="background:rgba(255,255,255,0.5);border-color:rgba(121,163,193,0.34);backdrop-filter:blur(8px);">
          <div class="text-purple-600 text-lg font-extrabold">Instant</div>
          <div class="text-[10px] font-semibold tracking-wider uppercase mt-1" style="color:#748da1;">Activation</div>
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
  <div class="w-full lg:w-7/12 flex flex-col justify-center px-6 sm:px-12 lg:px-16 py-12 bg-transparent relative">
    <!-- Glowing background light for mobile -->
    <div class="lg:hidden absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl pointer-events-none" style="background-color:rgba(123,174,210,0.15);"></div>
    
    <!-- Mobile header logo -->
    <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
      <span class="text-md font-bold tracking-wider" style="color:#0d1b2a;">MLM Platform</span>
    </div>

    <div class="max-w-2xl w-full mx-auto relative z-10">
      <!-- Title Header -->
      <div class="mb-8 text-center lg:text-left">
        <h2 class="text-3xl font-extrabold tracking-tight mb-2" style="color:#0d1b2a;">Select Your Package</h2>
        <p class="text-base" style="color:#425f75;">Choose a membership package below to activate your premium license.</p>
      </div>

      <!-- Interactive Radio Cards Form -->
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post" class="space-y-6">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <?php
            $query = "SELECT id, name, price, currency FROM packages WHERE active = 1 ORDER BY price ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $first = true;
            $icons = ['fa-crown', 'fa-gem', 'fa-rocket', 'fa-bolt', 'fa-star'];
            $iconIndex = 0;

            foreach ($packages as $pkg) {
                $pkg_id = "pkg_" . $pkg['id'];
                $pkg_name_js = htmlspecialchars($pkg['name'], ENT_QUOTES, 'UTF-8');
                $pkg_price = number_format((float)$pkg['price'], 2);
                $pkg_currency = htmlspecialchars($pkg['currency'], ENT_QUOTES, 'UTF-8');
                $icon = $icons[$iconIndex % count($icons)];
                
                $selected_class = $first ? " selected" : "";
                $checked = $first ? " checked" : "";
                
                echo '<div class="product-card' . $selected_class . '" id="card_' . $pkg_id . '" onclick="selectProduct(\'' . $pkg_id . '\', \'' . addslashes($pkg_name_js) . '\')">';
                echo '  <input type="radio" name="selected_package" id="' . $pkg_id . '" value="' . htmlspecialchars($pkg['id'], ENT_QUOTES, 'UTF-8') . '"' . $checked . ' class="product-radio">';
                echo '  <span class="product-badge" id="badge_' . $pkg_id . '">';
                if ($first) {
                    echo '<i class="fa-solid fa-check"></i> SELECTED';
                } else {
                    echo 'SELECT';
                }
                echo '</span>';
                echo '  <div class="product-icon">';
                echo '    <i class="fa-solid ' . $icon . '"></i>';
                echo '  </div>';
                echo '  <div class="product-info">';
                echo '    <h3 class="font-bold text-lg m-0" style="color:#0d1b2a;">' . htmlspecialchars($pkg['name'], ENT_QUOTES, 'UTF-8') . '</h3>';
                echo '    <div class="mt-2 inline-block px-4 py-1 rounded-full bg-slate-100 text-slate-800 font-extrabold text-sm border border-slate-200">' . $pkg_currency . ' ' . $pkg_price . '</div>';
                echo '  </div>';
                echo '</div>';
                
                $first = false;
                $iconIndex++;
            }
            ?>
        </div>

        <div class="pt-8 flex flex-col sm:flex-row gap-4 max-w-lg mx-auto lg:mx-0">
          <a href="signup.php?back=1" 
             class="flex-1 sm:flex-none sm:w-1/3 text-center bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl py-3.5 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-sm transform active:scale-[0.98]" style="display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-arrow-left mr-2 text-xs"></i> Back
          </a>
          <button type="submit" 
                  class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl py-3.5 text-sm font-semibold tracking-wide transition duration-200 cursor-pointer shadow-lg shadow-purple-500/20 hover:shadow-purple-500/40 transform active:scale-[0.98]">
            Next: Complete Payment <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
function selectProduct(id, val) {
    // Reset all cards and badges
    document.querySelectorAll('.product-card').forEach(card => {
        card.classList.remove('selected');
        // Reset badge text
        const badge = card.querySelector('.product-badge');
        if (badge) badge.innerHTML = 'SELECT';
    });
    
    // Select clicked card and check radio
    document.getElementById('card_' + id).classList.add('selected');
    document.getElementById(id).checked = true;
    
    // Update badge for selected
    const selectedBadge = document.getElementById('badge_' + id);
    if (selectedBadge) selectedBadge.innerHTML = '<i class="fa-solid fa-check"></i> SELECTED';
}
</script>
<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
</body>
</html>
