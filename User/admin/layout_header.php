<?php
/**
 * layout_header.php - Shared modern header + sidebar for all admin pages.
 *
 * Usage in each page (BEFORE this include):
 *   $page_title = "Page Name";   // shown in <title> and topbar
 *   $active_nav = "dashboard";   // one of: dashboard, gensettings, emailsettings, pacsettings, productsettings, notifications, users, payments, payrequest, profile
 *   $extra_head = "";            // optional: extra <style> or <script> tags
 */

// Fetch shared layout data
$_layout_admin = mlmp_pdo_fetch($pdo, "SELECT fname, country FROM affiliateuser WHERE username = ?", [$_SESSION['adminidusername']]);
$_layout_fname    = $_layout_admin['fname'] ?? 'Administrator';
$_layout_country  = $_layout_admin['country'] ?? '';

$_layout_settings = mlmp_pdo_fetch($pdo, "SELECT header FROM settings WHERE sno = 0 LIMIT 1");
$_layout_brand    = $_layout_settings['header'] ?? 'MLM Platform';

$page_title  = $page_title  ?? 'Admin Dashboard';
$active_nav  = $active_nav  ?? 'dashboard';
$extra_head  = $extra_head  ?? '';

if (!function_exists('nav_active')) {
    function nav_active($key, $active_nav) {
        return ($key === $active_nav) 
            ? 'class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl mb-0.5 transition-colors bg-indigo-50 text-indigo-700 border-l-[3px] border-indigo-500 text-[13.5px] font-medium"' 
            : 'class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl mb-0.5 transition-colors text-slate-600 hover:bg-slate-100 hover:text-slate-900 text-[13.5px] font-medium"';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?php echo mlmp_escape($page_title); ?> - <?php echo mlmp_escape($_layout_brand); ?> Admin</title>
<meta name="description" content="<?php echo mlmp_escape($page_title); ?> - <?php echo mlmp_escape($_layout_brand); ?> admin portal."/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php echo $extra_head; ?>
<style>
/* Custom Scrollbar for a polished look */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }
</style>
</head>
<body class="font-['Inter'] bg-slate-50 text-slate-800 min-h-screen overflow-x-hidden selection:bg-indigo-500/30">

<!-- Mobile overlay -->
<div id="dashOverlay" class="fixed inset-0 bg-black/60 z-40 hidden backdrop-blur-sm transition-opacity" onclick="closeSidebar()"></div>

<div class="flex min-h-screen">

  <!-- ======== SIDEBAR ======== -->
  <aside id="dashSidebar" class="w-[260px] bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">

    <!-- Logo -->
    <div class="px-5 pt-6 pb-4 border-b border-slate-100 flex items-center gap-2.5">
      <span class="text-[17px] font-bold bg-gradient-to-r from-indigo-600 to-blue-500 bg-clip-text text-transparent whitespace-nowrap"><?php echo mlmp_escape($_layout_brand); ?></span>
    </div>

    <!-- User block -->
    <div class="px-5 py-4 flex items-center gap-3 border-b border-slate-100">
      <div class="relative shrink-0">
        <img src="images/a0.jpg" alt="Avatar"
             class="w-11 h-11 rounded-full border-2 border-indigo-100 object-cover"
             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_layout_fname); ?>&background=6366f1&color=fff'">
        <span class="absolute bottom-[1px] right-[1px] w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-white"></span>
      </div>
      <div class="flex flex-col">
        <div class="text-sm font-semibold text-slate-900 leading-tight"><?php echo mlmp_escape($_layout_fname); ?></div>
        <div class="text-[11px] text-indigo-600 font-medium bg-indigo-50 px-2 py-0.5 rounded-full inline-block mt-1 w-max">
          Administrator
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
      <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-[1.2px] px-2.5 pt-2 pb-1 mt-2">Main Menu</div>
      <a href="dashboard.php" <?php echo nav_active('dashboard', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-gauge-high"></i></span>
        Dashboard
      </a>
      <a href="downline.php" <?php echo nav_active('downline', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-network-wired"></i></span>
        My Network
      </a>
      <a href="my_referrals.php" <?php echo nav_active('my_referrals', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-user-plus"></i></span>
        My Direct Referrals
      </a>

      <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-[1.2px] px-2.5 pt-2 pb-1 mt-2">Configuration</div>
      <a href="gensettings.php" <?php echo nav_active('gensettings', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-sliders"></i></span>
        General Settings
      </a>
      <a href="emailsettings.php" <?php echo nav_active('emailsettings', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-envelope-open-text"></i></span>
        E-Mail Settings
      </a>
      <a href="pacsettings.php" <?php echo nav_active('pacsettings', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-cubes"></i></span>
        Packages Settings
      </a>
      <a href="productsettings.php" <?php echo nav_active('productsettings', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-store"></i></span>
        Products Settings
      </a>
      <a href="backups/backup.php" <?php echo nav_active('backup', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-database"></i></span>
        Generate Backup
      </a>

      <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-[1.2px] px-2.5 pt-2 pb-1 mt-2">Announcements</div>
      <a href="notifications.php" <?php echo nav_active('notifications', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-bullhorn"></i></span>
        Post Notifications
      </a>

      <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-[1.2px] px-2.5 pt-2 pb-1 mt-2">Users & Sales</div>
      <a href="approvals.php" <?php echo nav_active('approvals', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-user-check"></i></span>
        Approval Requests
      </a>
      <a href="rejected_users.php" <?php echo nav_active('rejected_users', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-user-xmark"></i></span>
        Rejected History
      </a>
      <a href="users.php" <?php echo nav_active('users', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-users"></i></span>
        Users Directory
      </a>
      <a href="payments.php" <?php echo nav_active('payments', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-credit-card"></i></span>
        PayU Payments
      </a>
      <a href="payrequest.php" <?php echo nav_active('payrequest', $active_nav); ?>>
        <span class="text-base w-5 text-center shrink-0"><i class="fa-solid fa-hand-holding-dollar"></i></span>
        Payment Requests
      </a>
    </nav>

    <!-- Sidebar footer -->
    <div class="p-4 border-t border-slate-100">
      <a href="logout.php" class="flex items-center gap-2 text-red-600 text-[13px] font-semibold px-3 py-2 rounded-lg hover:bg-red-50 transition-colors">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
        Logout
      </a>
    </div>

  </aside><!-- /sidebar -->

  <!-- ======== MAIN ======== -->
  <div class="flex-1 flex flex-col lg:ml-[260px] min-h-screen bg-slate-50 transition-all duration-300 w-full relative">

    <!-- Topbar -->
    <header class="h-16 bg-white/90 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-4 sm:px-7 sticky top-0 z-30">
      <div class="flex items-center gap-3.5">
        <button onclick="toggleSidebar()" class="lg:hidden text-slate-600 hover:text-slate-900 text-xl p-1 focus:outline-none" aria-label="Menu">
          <i class="fa-solid fa-bars"></i>
        </button>
        <span class="text-lg font-bold text-slate-900"><?php echo mlmp_escape($page_title); ?></span>
      </div>
      <div class="flex items-center gap-4">
        <span class="hidden sm:inline-block text-[12px] text-slate-600 font-medium mr-2">
          <i class="fa-solid fa-earth-americas text-indigo-500 mr-1"></i> <?php echo mlmp_escape($_layout_country); ?>
        </span>
        <a href="profile.php">
          <img class="w-9 h-9 rounded-full border-2 border-indigo-100 object-cover cursor-pointer hover:opacity-90 transition-opacity" src="images/a0.jpg" alt="Avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_layout_fname); ?>&background=6366f1&color=fff'">
        </a>
      </div>
    </header>

    <!-- Page Content starts here -->
    <main class="flex-1 p-4 sm:p-7 overflow-x-hidden">
