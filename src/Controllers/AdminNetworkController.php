<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class AdminNetworkController
{
    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['adminidusername'])) {
            header("Location: /?route=admin");
            exit;
        }
    }

    public function downline()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $search_user = $_GET['user'] ?? $_SESSION['adminidusername'];
        
        $user_row = $db->fetch("SELECT username, fname, active, doj, pcktaken, left_count, right_count FROM affiliateuser WHERE username = :u LIMIT 1", ['u' => $search_user]);

        $data = [
            'page_title' => 'Binary Downline Tree',
            'active_nav' => 'downline',
            'search_user' => $search_user,
            'user_row' => $user_row,
            'pdo' => $db->connection() // passing pdo strictly for the recursive tree function in the view if needed
        ];

        View::render('admin/network/downline', $data, 'admin/layouts/admin');
    }

    public function referrals()
    {
        $this->checkAuth();
        $db = Database::getInstance();

        $search_user = $_GET['user'] ?? $_SESSION['adminidusername'];
        $referrals = $db->fetchAll("SELECT * FROM affiliateuser WHERE referedby = :u ORDER BY doj DESC", ['u' => $search_user]);

        $data = [
            'page_title' => 'Direct Referrals',
            'active_nav' => 'downline',
            'search_user' => $search_user,
            'referrals' => $referrals
        ];

        View::render('admin/network/referrals', $data, 'admin/layouts/admin');
    }
}
