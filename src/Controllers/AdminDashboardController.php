<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminDashboardController
{
    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['adminidusername'])) {
            header("Location: /?route=admin");
            exit;
        }

        $db = Database::getInstance();
        $pdo = $db->connection();
        $username = $_SESSION['adminidusername'];

        // Extract stats from admin/dashboard.php
        $admin_row = $db->fetch("SELECT id, fname, country FROM affiliateuser WHERE username = :u", ['u' => $username]);
        
        // Admin Commission (sum of unilevel)
        $admin_comm_row = $db->fetch("SELECT SUM(tamount) as total_comm FROM affiliateuser WHERE username = :u OR username LIKE :u_like", [
            'u' => $username,
            'u_like' => $username . '_bc_%'
        ]);
        $admin_commission = (float)($admin_comm_row['total_comm'] ?? 0);
        
        // We will mock pair commission for now or use logic
        $admin_pair_commission = 0.0;
        $admin_direct_commission = max(0.0, $admin_commission - $admin_pair_commission);
        
        $admin_direct_refs = (int)($db->fetch("SELECT COUNT(*) as count FROM affiliateuser WHERE referedby = :u", ['u' => $username])['count'] ?? 0);
        $total_admin_collection = (float)($db->fetch("SELECT SUM(price) as total FROM paypalpayments")['total'] ?? 0);

        // Global stats
        $total_users = (int)($db->fetch("SELECT COUNT(*) as count FROM affiliateuser")['count'] ?? 0);
        $active_members_count = (int)($db->fetch("SELECT COUNT(*) as count FROM affiliateuser WHERE active = 1")['count'] ?? 0);
        $inactive_members_count = (int)($db->fetch("SELECT COUNT(*) as count FROM affiliateuser WHERE active = 0")['count'] ?? 0);
        $total_payments = (float)($db->fetch("SELECT SUM(price) as total FROM paypalpayments")['total'] ?? 0);

        // Ensure we fetch layout data (normally extracted to a View Composer or Middleware)
        $_layout_user = $db->fetch("SELECT fname, country, pcktaken FROM affiliateuser WHERE username = :u", ['u' => $username]);
        $_layout_fname    = $_layout_user['fname']    ?? '';
        $_layout_pcktaken = $_layout_user['pcktaken'] ?? 0;
        $_layout_pkname   = 'Admin';
        $_layout_brand    = 'MLM Admin';

        $data = [
            'pdo' => $pdo, // for legacy support
            '_layout_fname' => $_layout_fname,
            '_layout_pcktaken' => $_layout_pcktaken,
            '_layout_pkname' => $_layout_pkname,
            '_layout_brand' => $_layout_brand,
            'page_title' => 'Admin Dashboard',
            'active_nav' => 'dashboard',
            'admin_msg' => '',
            'admin_name' => $admin_row['fname'] ?? 'Admin',
            'admin_commission' => $admin_commission,
            'admin_pair_commission' => $admin_pair_commission,
            'admin_direct_commission' => $admin_direct_commission,
            'total_admin_collection' => $total_admin_collection,
            'admin_direct_refs' => $admin_direct_refs,
            'total_users' => $total_users,
            'active_members_count' => $active_members_count,
            'inactive_members_count' => $inactive_members_count,
            'total_payments' => $total_payments,
            'registration_trends' => [],
            'package_revenue_split' => [],
            'recent_payments' => [],
            'extra_head' => '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>'
        ];

        View::render('admin/dashboard', $data, 'admin/layouts/admin');
    }
}
