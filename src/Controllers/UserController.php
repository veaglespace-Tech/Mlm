<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class UserController
{
    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['username'])) {
            header("Location: /");
            exit;
        }

        $db = Database::getInstance();
        $pdo = $db->connection();

        // --- DASHBOARD LOGIC EXTRACTED FROM User/dashboard.php ---
        $payto = $_SESSION['username'];
        $ref_chart_data = [];
        try {
            $ref_chart_data = $db->fetchAll("
                SELECT doj, COUNT(*) as signup_count
                FROM affiliateuser
                WHERE referedby = :payto AND doj IS NOT NULL AND doj != '0000-00-00'
                GROUP BY doj ORDER BY doj ASC LIMIT 15
            ", ['payto' => $payto]);
        } catch (\PDOException $e) {}

        $chart_labels   = [];
        $chart_signups  = [];
        $chart_earnings = [];
        $cumulative_earnings = 0.0;

        $pkg_comm_row = $db->fetch("SELECT level1 FROM packages ORDER BY id ASC LIMIT 1");
        $level1_comm = (float)($pkg_comm_row['level1'] ?? 200.0);

        if (!empty($ref_chart_data)) {
            foreach ($ref_chart_data as $data) {
                $chart_labels[]  = date('d M', strtotime($data['doj']));
                $count           = (int)$data['signup_count'];
                $chart_signups[] = $count;
                $cumulative_earnings += $count * $level1_comm;
                $chart_earnings[] = $cumulative_earnings;
            }
        } else {
            $chart_labels   = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
            $chart_signups  = [0, 0, 0, 0, 0];
            $chart_earnings = [0, 0, 0, 0, 0];
        }

        // Fetch user info
        $profile_row = $db->fetch("SELECT id, fname, email, doj, active, username, address, pcktaken, tamount, country, launch_time, is_binary_qualified FROM affiliateuser WHERE username = :u", ['u' => $_SESSION['username']]);
        
        // Settings
        $settings_row  = $db->fetch("SELECT header, wlink, fblink, twitterlink FROM settings WHERE sno = 0 LIMIT 1");
        
        // Pass data to View
        $data = [
            'pdo' => $pdo, // For layout_header legacy support
            'page_title' => 'Dashboard',
            'active_nav' => 'dashboard',
            'extra_head' => '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>',
            'profile_row' => $profile_row,
            'chart_labels' => $chart_labels,
            'chart_signups' => $chart_signups,
            'chart_earnings' => $chart_earnings,
            'settings_row' => $settings_row,
            'numrows' => count($ref_chart_data) // simplified for PoC
        ];

        View::render('user/dashboard', $data, 'app');
    }

    public function profile()
    {
        // ... (We can implement profile similarly)
    }
}
